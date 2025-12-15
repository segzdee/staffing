<?php

namespace App\Services;

use App\Models\WorkerCertification;
use App\Models\WorkerSkill;
use App\Notifications\DocumentExpiryReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing document expiry, notifications, and skill deactivation.
 * WKR-006: Document Expiry Management
 */
class DocumentExpiryService
{
    /**
     * Check for expiring documents and send notifications.
     * Runs daily at 02:00.
     *
     * @return array Summary of actions taken
     */
    public function checkExpiringDocuments(): array
    {
        $summary = [
            'notifications_sent' => 0,
            'documents_expired' => 0,
            'skills_deactivated' => 0,
            'workers_affected' => [],
        ];

        // Define notification intervals (days before expiry)
        $notificationIntervals = [60, 30, 14, 7];

        foreach ($notificationIntervals as $days) {
            $count = $this->sendExpiryReminders($days);
            $summary['notifications_sent'] += $count;
        }

        // Process expired documents
        $expiredResults = $this->processExpiredDocuments();
        $summary['documents_expired'] = $expiredResults['expired_count'];
        $summary['skills_deactivated'] = $expiredResults['skills_deactivated'];
        $summary['workers_affected'] = $expiredResults['workers_affected'];

        return $summary;
    }

    /**
     * Send expiry reminders for documents expiring in X days.
     *
     * @param int $daysBeforeExpiry
     * @return int Number of notifications sent
     */
    protected function sendExpiryReminders(int $daysBeforeExpiry): int
    {
        $targetDate = Carbon::today()->addDays($daysBeforeExpiry);

        $expiringCertifications = WorkerCertification::whereDate('expiry_date', $targetDate)
            ->where('verified', true)
            ->where(function ($query) use ($daysBeforeExpiry) {
                // Only send if we haven't sent this specific reminder yet
                $query->where('expiry_reminder_sent', '<', $this->getReminderLevel($daysBeforeExpiry))
                      ->orWhereNull('expiry_reminder_sent');
            })
            ->with(['worker', 'certification'])
            ->get();

        $notificationsSent = 0;

        foreach ($expiringCertifications as $cert) {
            try {
                // Send notification to worker
                $cert->worker->notify(new DocumentExpiryReminderNotification(
                    $cert,
                    $daysBeforeExpiry
                ));

                // Update reminder level
                $cert->update([
                    'expiry_reminder_sent' => $this->getReminderLevel($daysBeforeExpiry)
                ]);

                $notificationsSent++;

                Log::info("Expiry reminder sent for certification #{$cert->id}", [
                    'worker_id' => $cert->worker_id,
                    'certification' => $cert->certification->name ?? 'Unknown',
                    'days_before_expiry' => $daysBeforeExpiry,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send expiry reminder for certification #{$cert->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $notificationsSent;
    }

    /**
     * Process expired documents: deactivate skills and update worker status.
     *
     * @return array Results of processing
     */
    public function processExpiredDocuments(): array
    {
        $results = [
            'expired_count' => 0,
            'skills_deactivated' => 0,
            'workers_affected' => [],
        ];

        // Find all certifications that expired today or earlier and are still verified
        $expiredCertifications = WorkerCertification::where('expiry_date', '<=', Carbon::today())
            ->where('verified', true)
            ->with(['worker', 'certification'])
            ->get();

        foreach ($expiredCertifications as $cert) {
            DB::beginTransaction();

            try {
                // Mark certification as expired (unverified)
                $cert->update([
                    'verified' => false,
                    'verification_status' => 'expired',
                    'verification_notes' => 'Automatically expired on ' . Carbon::today()->format('Y-m-d'),
                ]);

                $results['expired_count']++;

                // Deactivate associated skills if certification is required
                if ($cert->certification && $cert->certification->is_required_for_skills) {
                    $deactivatedCount = $this->deactivateSkillsForExpiredCertification($cert);
                    $results['skills_deactivated'] += $deactivatedCount;
                }

                // Track affected worker
                if (!in_array($cert->worker_id, $results['workers_affected'])) {
                    $results['workers_affected'][] = $cert->worker_id;
                }

                // Update worker's shift eligibility
                $this->updateWorkerShiftEligibility($cert->worker);

                // Send final expiry notification
                $cert->worker->notify(new DocumentExpiryReminderNotification(
                    $cert,
                    0,
                    true // isExpired flag
                ));

                DB::commit();

                Log::info("Document expired and processed", [
                    'certification_id' => $cert->id,
                    'worker_id' => $cert->worker_id,
                    'certification' => $cert->certification->name ?? 'Unknown',
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to process expired certification #{$cert->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Deactivate skills associated with an expired certification.
     *
     * @param WorkerCertification $certification
     * @return int Number of skills deactivated
     */
    protected function deactivateSkillsForExpiredCertification(WorkerCertification $certification): int
    {
        // Find skills that require this certification
        $skillsToDeactivate = WorkerSkill::where('worker_id', $certification->worker_id)
            ->whereHas('skill', function ($query) use ($certification) {
                $query->where('required_certification_id', $certification->certification_id);
            })
            ->where('verified', true)
            ->get();

        $deactivatedCount = 0;

        foreach ($skillsToDeactivate as $workerSkill) {
            $workerSkill->update([
                'verified' => false,
                'verification_notes' => 'Deactivated due to expired certification: ' .
                    ($certification->certification->name ?? 'Unknown') .
                    ' (expired on ' . $certification->expiry_date->format('Y-m-d') . ')',
            ]);
            $deactivatedCount++;

            Log::info("Skill deactivated due to expired certification", [
                'worker_id' => $certification->worker_id,
                'skill_id' => $workerSkill->skill_id,
                'certification_id' => $certification->certification_id,
            ]);
        }

        return $deactivatedCount;
    }

    /**
     * Update worker's shift eligibility based on expired documents.
     *
     * @param \App\Models\User $worker
     * @return void
     */
    protected function updateWorkerShiftEligibility($worker): void
    {
        // Get active shift assignments that require expired certifications
        $activeAssignments = $worker->workerProfile->shiftAssignments()
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->with('shift')
            ->get();

        foreach ($activeAssignments as $assignment) {
            // Check if worker still meets shift requirements
            if (!$this->workerMeetsShiftRequirements($worker, $assignment->shift)) {
                // Flag assignment for review
                $assignment->update([
                    'status' => 'eligibility_review',
                    'notes' => 'Worker certification expired. Requires review. ' . ($assignment->notes ?? ''),
                ]);

                Log::warning("Shift assignment flagged for eligibility review", [
                    'assignment_id' => $assignment->id,
                    'worker_id' => $worker->id,
                    'shift_id' => $assignment->shift_id,
                ]);
            }
        }
    }

    /**
     * Check if worker meets shift requirements.
     *
     * @param \App\Models\User $worker
     * @param \App\Models\Shift $shift
     * @return bool
     */
    protected function workerMeetsShiftRequirements($worker, $shift): bool
    {
        if (!$shift->required_certifications) {
            return true;
        }

        // Get worker's valid (verified and not expired) certifications
        $validCertifications = WorkerCertification::where('worker_id', $worker->id)
            ->where('verified', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>', Carbon::today());
            })
            ->pluck('certification_id')
            ->toArray();

        // Check if all required certifications are valid
        foreach ($shift->required_certifications as $requiredCertId) {
            if (!in_array($requiredCertId, $validCertifications)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get reminder level based on days before expiry.
     *
     * @param int $daysBeforeExpiry
     * @return int
     */
    protected function getReminderLevel(int $daysBeforeExpiry): int
    {
        return match ($daysBeforeExpiry) {
            60 => 1,
            30 => 2,
            14 => 3,
            7 => 4,
            default => 0,
        };
    }

    /**
     * Process renewal for a certification.
     *
     * @param WorkerCertification $certification
     * @param array $renewalData
     * @return bool
     */
    public function renewCertification(WorkerCertification $certification, array $renewalData): bool
    {
        DB::beginTransaction();

        try {
            // Update certification with new data
            $certification->update([
                'certification_number' => $renewalData['certification_number'] ?? $certification->certification_number,
                'issue_date' => $renewalData['issue_date'] ?? Carbon::today(),
                'expiry_date' => $renewalData['expiry_date'],
                'document_url' => $renewalData['document_url'] ?? $certification->document_url,
                'verified' => false, // Needs re-verification
                'verification_status' => 'pending',
                'verification_notes' => 'Renewal submitted on ' . Carbon::today()->format('Y-m-d'),
                'expiry_reminder_sent' => 0, // Reset reminder counter
            ]);

            // Reactivate previously deactivated skills (pending verification)
            $this->reactivateSkillsForRenewal($certification);

            DB::commit();

            Log::info("Certification renewed", [
                'certification_id' => $certification->id,
                'worker_id' => $certification->worker_id,
                'new_expiry_date' => $renewalData['expiry_date'],
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to renew certification #{$certification->id}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Reactivate skills after certification renewal (pending verification).
     *
     * @param WorkerCertification $certification
     * @return void
     */
    protected function reactivateSkillsForRenewal(WorkerCertification $certification): void
    {
        WorkerSkill::where('worker_id', $certification->worker_id)
            ->whereHas('skill', function ($query) use ($certification) {
                $query->where('required_certification_id', $certification->certification_id);
            })
            ->where('verified', false)
            ->update([
                'verification_notes' => 'Pending verification after certification renewal on ' .
                    Carbon::today()->format('Y-m-d'),
            ]);
    }

    /**
     * Get expiry dashboard data for a worker.
     *
     * @param int $workerId
     * @return array
     */
    public function getExpiryDashboard(int $workerId): array
    {
        $certifications = WorkerCertification::where('worker_id', $workerId)
            ->whereNotNull('expiry_date')
            ->with('certification')
            ->get();

        $dashboard = [
            'expiring_soon' => [],
            'expired' => [],
            'valid' => [],
        ];

        foreach ($certifications as $cert) {
            $daysUntilExpiry = Carbon::today()->diffInDays($cert->expiry_date, false);

            if ($daysUntilExpiry < 0) {
                $dashboard['expired'][] = $cert;
            } elseif ($daysUntilExpiry <= 60) {
                $dashboard['expiring_soon'][] = $cert;
            } else {
                $dashboard['valid'][] = $cert;
            }
        }

        return $dashboard;
    }
}
