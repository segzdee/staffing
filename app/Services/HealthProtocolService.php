<?php

namespace App\Services;

use App\Models\HealthDeclaration;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\VaccinationRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SAF-005: COVID/Health Protocols - Health Protocol Service
 *
 * Handles health declarations, vaccination verification, and outbreak exposure tracking.
 */
class HealthProtocolService
{
    /**
     * PPE requirement types.
     */
    public const PPE_TYPES = [
        'mask' => 'Face Mask',
        'n95_mask' => 'N95 Respirator',
        'gloves' => 'Disposable Gloves',
        'face_shield' => 'Face Shield',
        'gown' => 'Protective Gown',
        'goggles' => 'Safety Goggles',
        'hairnet' => 'Hair Net',
        'shoe_covers' => 'Shoe Covers',
    ];

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Submit a health declaration for a user.
     */
    public function submitHealthDeclaration(User $user, ?Shift $shift, array $data): HealthDeclaration
    {
        $declaration = HealthDeclaration::create([
            'user_id' => $user->id,
            'shift_id' => $shift?->id,
            'fever_free' => $data['fever_free'] ?? false,
            'no_symptoms' => $data['no_symptoms'] ?? false,
            'no_exposure' => $data['no_exposure'] ?? false,
            'fit_for_work' => $data['fit_for_work'] ?? false,
            'declared_at' => now(),
            'ip_address' => $data['ip_address'] ?? request()->ip(),
        ]);

        // Log health concerns if any
        if (! $declaration->isClearedToWork()) {
            Log::warning('Health declaration flagged', [
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'concerns' => $declaration->getHealthConcerns(),
            ]);

            // If this is for a specific shift, notify the business
            if ($shift) {
                $this->notifyBusinessOfHealthConcern($shift, $user, $declaration);
            }
        }

        return $declaration;
    }

    /**
     * Check if a user has health clearance for a shift.
     */
    public function checkHealthClearance(User $user, Shift $shift): bool
    {
        // If shift doesn't require health declaration, return true
        if (! $shift->requires_health_declaration) {
            return true;
        }

        // Get recent declaration for this user
        $declaration = $this->getRecentDeclaration($user);

        if (! $declaration) {
            return false;
        }

        // Check if declaration is valid for this shift
        if (! $declaration->isValidForShift($shift)) {
            return false;
        }

        // Check vaccination requirements if applicable
        if ($shift->requires_vaccination && ! $this->hasRequiredVaccinations($user, $shift)) {
            return false;
        }

        return true;
    }

    /**
     * Get the most recent health declaration for a user.
     */
    public function getRecentDeclaration(User $user): ?HealthDeclaration
    {
        return HealthDeclaration::forUser($user->id)
            ->recent(24)
            ->orderBy('declared_at', 'desc')
            ->first();
    }

    /**
     * Record an outbreak exposure for a shift and notify affected workers.
     */
    public function recordOutbreakExposure(Shift $shift, User $reportingUser, ?string $exposureDetails = null): void
    {
        DB::transaction(function () use ($shift, $reportingUser, $exposureDetails) {
            // Log the exposure event
            Log::alert('Outbreak exposure reported', [
                'shift_id' => $shift->id,
                'reported_by' => $reportingUser->id,
                'shift_date' => $shift->shift_date,
                'details' => $exposureDetails,
            ]);

            // Get all workers who were assigned to this shift
            $assignments = ShiftAssignment::where('shift_id', $shift->id)
                ->whereIn('status', ['assigned', 'checked_in', 'checked_out', 'completed'])
                ->with('worker')
                ->get();

            // Flag all declarations for this shift
            HealthDeclaration::forShift($shift->id)
                ->update(['no_exposure' => false]);

            // Notify all exposed workers
            $this->notifyExposedWorkers($shift, $assignments, $reportingUser, $exposureDetails);

            // Notify the business
            $this->notifyBusinessOfOutbreak($shift, $reportingUser, $exposureDetails);
        });
    }

    /**
     * Notify workers who were exposed at a shift.
     *
     * @param  \Illuminate\Database\Eloquent\Collection|null  $assignments
     */
    public function notifyExposedWorkers(
        Shift $shift,
        $assignments = null,
        ?User $reportingUser = null,
        ?string $details = null
    ): void {
        if ($assignments === null) {
            $assignments = ShiftAssignment::where('shift_id', $shift->id)
                ->whereIn('status', ['assigned', 'checked_in', 'checked_out', 'completed'])
                ->with('worker')
                ->get();
        }

        foreach ($assignments as $assignment) {
            $worker = $assignment->worker;

            if (! $worker) {
                continue;
            }

            $this->notificationService->send(
                $worker,
                'health_exposure_alert',
                'Health Exposure Alert',
                "You may have been exposed to an illness at shift \"{$shift->title}\" on {$shift->shift_date->format('M j, Y')}. ".
                'Please monitor your health and consider getting tested if you develop symptoms.',
                [
                    'shift_id' => $shift->id,
                    'shift_title' => $shift->title,
                    'shift_date' => $shift->shift_date->toDateString(),
                    'exposure_details' => $details,
                ],
                ['push', 'email', 'sms']
            );
        }
    }

    /**
     * Validate PPE requirements for a shift.
     *
     * @return array{valid: bool, requirements: array, missing: array}
     */
    public function validatePPERequirements(Shift $shift): array
    {
        $ppeRequirements = $shift->ppe_requirements ?? [];
        $validPPE = [];
        $invalidPPE = [];

        foreach ($ppeRequirements as $ppe) {
            if (array_key_exists($ppe, self::PPE_TYPES)) {
                $validPPE[] = [
                    'code' => $ppe,
                    'name' => self::PPE_TYPES[$ppe],
                ];
            } else {
                $invalidPPE[] = $ppe;
            }
        }

        return [
            'valid' => empty($invalidPPE),
            'requirements' => $validPPE,
            'missing' => $invalidPPE,
        ];
    }

    /**
     * Check if a user has the required vaccinations for a shift.
     */
    public function hasRequiredVaccinations(User $user, Shift $shift): bool
    {
        if (! $shift->requires_vaccination) {
            return true;
        }

        $requiredVaccinations = $shift->required_vaccinations ?? ['COVID-19'];

        foreach ($requiredVaccinations as $vaccineType) {
            $hasValidRecord = VaccinationRecord::forUser($user->id)
                ->ofType($vaccineType)
                ->valid()
                ->exists();

            if (! $hasValidRecord) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing vaccinations for a user for a specific shift.
     */
    public function getMissingVaccinations(User $user, Shift $shift): array
    {
        if (! $shift->requires_vaccination) {
            return [];
        }

        $requiredVaccinations = $shift->required_vaccinations ?? ['COVID-19'];
        $missing = [];

        foreach ($requiredVaccinations as $vaccineType) {
            $hasValidRecord = VaccinationRecord::forUser($user->id)
                ->ofType($vaccineType)
                ->valid()
                ->exists();

            if (! $hasValidRecord) {
                $missing[] = $vaccineType;
            }
        }

        return $missing;
    }

    /**
     * Get health clearance summary for a user and shift.
     */
    public function getHealthClearanceSummary(User $user, Shift $shift): array
    {
        $summary = [
            'cleared' => true,
            'requires_declaration' => $shift->requires_health_declaration,
            'requires_vaccination' => $shift->requires_vaccination,
            'declaration_status' => 'not_required',
            'vaccination_status' => 'not_required',
            'ppe_requirements' => [],
            'issues' => [],
        ];

        // Check declaration requirement
        if ($shift->requires_health_declaration) {
            $declaration = $this->getRecentDeclaration($user);

            if (! $declaration) {
                $summary['cleared'] = false;
                $summary['declaration_status'] = 'missing';
                $summary['issues'][] = 'Health declaration required';
            } elseif (! $declaration->isClearedToWork()) {
                $summary['cleared'] = false;
                $summary['declaration_status'] = 'flagged';
                $summary['issues'][] = 'Health declaration indicates concerns';
            } elseif (! $declaration->isValid()) {
                $summary['cleared'] = false;
                $summary['declaration_status'] = 'expired';
                $summary['issues'][] = 'Health declaration expired (over 24 hours old)';
            } else {
                $summary['declaration_status'] = 'valid';
            }
        }

        // Check vaccination requirements
        if ($shift->requires_vaccination) {
            $missingVaccinations = $this->getMissingVaccinations($user, $shift);

            if (! empty($missingVaccinations)) {
                $summary['cleared'] = false;
                $summary['vaccination_status'] = 'incomplete';
                $summary['issues'][] = 'Missing required vaccinations: '.implode(', ', $missingVaccinations);
            } else {
                $summary['vaccination_status'] = 'complete';
            }
        }

        // Add PPE requirements info
        $summary['ppe_requirements'] = $this->validatePPERequirements($shift)['requirements'];

        return $summary;
    }

    /**
     * Get expiring vaccinations for a user (within 30 days).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringVaccinations(User $user, int $days = 30)
    {
        return VaccinationRecord::forUser($user->id)
            ->verified()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get vaccination records for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVaccinationRecords(User $user)
    {
        return VaccinationRecord::forUser($user->id)
            ->orderBy('vaccination_date', 'desc')
            ->get();
    }

    /**
     * Add a vaccination record for a user.
     */
    public function addVaccinationRecord(User $user, array $data): VaccinationRecord
    {
        return VaccinationRecord::create([
            'user_id' => $user->id,
            'vaccine_type' => $data['vaccine_type'],
            'vaccination_date' => $data['vaccination_date'] ?? null,
            'document_url' => $data['document_url'] ?? null,
            'lot_number' => $data['lot_number'] ?? null,
            'provider_name' => $data['provider_name'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'is_booster' => $data['is_booster'] ?? false,
            'dose_number' => $data['dose_number'] ?? null,
            'verification_status' => 'pending',
        ]);
    }

    /**
     * Verify a vaccination record.
     */
    public function verifyVaccinationRecord(VaccinationRecord $record, User $verifier): void
    {
        $record->verify($verifier->id);

        // Notify the user
        $this->notificationService->send(
            $record->user,
            'vaccination_verified',
            'Vaccination Record Verified',
            "Your {$record->getVaccineDisplayName()} vaccination record has been verified.",
            ['vaccination_record_id' => $record->id],
            ['push']
        );
    }

    /**
     * Reject a vaccination record.
     */
    public function rejectVaccinationRecord(VaccinationRecord $record, User $verifier, string $reason): void
    {
        $record->reject($verifier->id, $reason);

        // Notify the user
        $this->notificationService->send(
            $record->user,
            'vaccination_rejected',
            'Vaccination Record Rejected',
            "Your {$record->getVaccineDisplayName()} vaccination record was rejected. Reason: {$reason}",
            [
                'vaccination_record_id' => $record->id,
                'rejection_reason' => $reason,
            ],
            ['push', 'email']
        );
    }

    /**
     * Notify business of a health concern from a worker.
     */
    protected function notifyBusinessOfHealthConcern(Shift $shift, User $worker, HealthDeclaration $declaration): void
    {
        $business = $shift->business;

        if (! $business) {
            return;
        }

        $this->notificationService->send(
            $business,
            'worker_health_concern',
            'Worker Health Declaration Flagged',
            "Worker {$worker->name} has reported health concerns for shift \"{$shift->title}\". ".
            'Please review their assignment.',
            [
                'shift_id' => $shift->id,
                'worker_id' => $worker->id,
                'concerns' => $declaration->getHealthConcerns(),
            ],
            ['push', 'email']
        );
    }

    /**
     * Notify business of an outbreak report.
     */
    protected function notifyBusinessOfOutbreak(Shift $shift, User $reportingUser, ?string $details): void
    {
        $business = $shift->business;

        if (! $business) {
            return;
        }

        $this->notificationService->send(
            $business,
            'outbreak_reported',
            'Health Outbreak Reported',
            "A potential health exposure has been reported for shift \"{$shift->title}\" on {$shift->shift_date->format('M j, Y')}. ".
            'All affected workers have been notified.',
            [
                'shift_id' => $shift->id,
                'reported_by' => $reportingUser->id,
                'details' => $details,
            ],
            ['push', 'email', 'sms']
        );
    }

    /**
     * Get statistics for health declarations.
     */
    public function getDeclarationStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $total = HealthDeclaration::where('declared_at', '>=', $startDate)->count();
        $cleared = HealthDeclaration::where('declared_at', '>=', $startDate)->cleared()->count();
        $flagged = HealthDeclaration::where('declared_at', '>=', $startDate)->flagged()->count();

        return [
            'total' => $total,
            'cleared' => $cleared,
            'flagged' => $flagged,
            'cleared_percentage' => $total > 0 ? round(($cleared / $total) * 100, 1) : 0,
            'period_days' => $days,
        ];
    }

    /**
     * Get statistics for vaccination records.
     */
    public function getVaccinationStats(): array
    {
        return [
            'total' => VaccinationRecord::count(),
            'pending' => VaccinationRecord::pending()->count(),
            'verified' => VaccinationRecord::verified()->count(),
            'expired' => VaccinationRecord::expired()->count(),
            'by_type' => VaccinationRecord::selectRaw('vaccine_type, count(*) as count')
                ->groupBy('vaccine_type')
                ->pluck('count', 'vaccine_type')
                ->toArray(),
        ];
    }
}
