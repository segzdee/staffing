<?php

namespace App\Services;

use App\Models\ConsentRecord;
use App\Models\DataRetentionPolicy;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * GLO-005: GDPR/CCPA Compliance - Privacy Compliance Service
 *
 * Handles all privacy-related operations including:
 * - Data Subject Requests (DSR)
 * - Consent Management
 * - Data Export
 * - Data Erasure
 * - Retention Policy Enforcement
 */
class PrivacyComplianceService
{
    protected DataExportService $exportService;

    public function __construct(DataExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    // ========================================
    // DATA SUBJECT REQUEST METHODS
    // ========================================

    /**
     * Create a data access request (GDPR Article 15).
     */
    public function createAccessRequest(string $email): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_ACCESS);
    }

    /**
     * Create a data erasure request (GDPR Article 17 - Right to be Forgotten).
     */
    public function createErasureRequest(string $email): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_ERASURE);
    }

    /**
     * Create a data portability request (GDPR Article 20).
     */
    public function createPortabilityRequest(string $email): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_PORTABILITY);
    }

    /**
     * Create a data rectification request (GDPR Article 16).
     */
    public function createRectificationRequest(string $email, string $description): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_RECTIFICATION, $description);
    }

    /**
     * Create a processing restriction request (GDPR Article 18).
     */
    public function createRestrictionRequest(string $email, string $description): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_RESTRICTION, $description);
    }

    /**
     * Create a processing objection (GDPR Article 21).
     */
    public function createObjectionRequest(string $email, string $description): DataSubjectRequest
    {
        return $this->createRequest($email, DataSubjectRequest::TYPE_OBJECTION, $description);
    }

    /**
     * Create a data subject request.
     */
    protected function createRequest(string $email, string $type, ?string $description = null): DataSubjectRequest
    {
        $user = User::where('email', $email)->first();

        $request = DataSubjectRequest::create([
            'email' => $email,
            'user_id' => $user?->id,
            'type' => $type,
            'description' => $description,
            'requester_ip' => request()->ip(),
            'requester_user_agent' => request()->userAgent(),
            'metadata' => [
                'submitted_at' => now()->toIso8601String(),
                'source' => 'web_form',
            ],
        ]);

        // Send verification email
        $this->sendVerificationEmail($request);

        Log::info('Data subject request created', [
            'request_id' => $request->id,
            'request_number' => $request->request_number,
            'type' => $type,
            'email' => $email,
        ]);

        return $request;
    }

    /**
     * Send verification email for a data subject request.
     */
    protected function sendVerificationEmail(DataSubjectRequest $request): void
    {
        $verificationUrl = route('privacy.verify-request', [
            'request' => $request->request_number,
            'token' => $request->verification_token,
        ]);

        // Send verification email
        // In production, use a proper Mailable class
        Mail::send('emails.privacy.verify-request', [
            'request' => $request,
            'verificationUrl' => $verificationUrl,
        ], function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Verify Your Data Request - '.config('app.name'));
        });

        Log::info('DSR verification email sent', [
            'request_number' => $request->request_number,
            'email' => $request->email,
        ]);
    }

    /**
     * Verify a data subject request with the given token.
     */
    public function verifyRequest(DataSubjectRequest $request, string $token): bool
    {
        if ($request->verify($token)) {
            Log::info('Data subject request verified', [
                'request_number' => $request->request_number,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Process a data access request (GDPR Article 15).
     *
     * @return string Path to the export file
     */
    public function processAccessRequest(DataSubjectRequest $request): string
    {
        if (! $request->canProcess()) {
            throw new \Exception('Request cannot be processed in its current state.');
        }

        $request->startProcessing();

        $user = $request->user;
        if (! $user) {
            $user = User::where('email', $request->email)->first();
        }

        if (! $user) {
            $request->complete('No user data found for this email address.');

            return '';
        }

        // Generate data export
        $exportData = $this->exportService->generateDataExport($user);
        $filePath = $this->exportService->saveExportToFile($user, $exportData);

        $request->complete(
            'Data export generated and available for download.',
            $filePath
        );

        // Send notification with download link
        $this->notifyExportReady($request, $filePath);

        Log::info('Access request processed', [
            'request_number' => $request->request_number,
            'user_id' => $user->id,
            'export_path' => $filePath,
        ]);

        return $filePath;
    }

    /**
     * Process a data erasure request (GDPR Article 17).
     */
    public function processErasureRequest(DataSubjectRequest $request): void
    {
        if (! $request->canProcess()) {
            throw new \Exception('Request cannot be processed in its current state.');
        }

        $request->startProcessing();

        $user = $request->user;
        if (! $user) {
            $user = User::where('email', $request->email)->first();
        }

        if (! $user) {
            $request->complete('No user data found for this email address.');

            return;
        }

        // Perform data erasure
        DB::beginTransaction();
        try {
            $this->deleteUserData($user);
            DB::commit();

            $request->complete('All personal data has been erased in compliance with GDPR Article 17.');

            Log::info('Erasure request processed', [
                'request_number' => $request->request_number,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erasure request failed', [
                'request_number' => $request->request_number,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process a data portability request (GDPR Article 20).
     *
     * @return string Path to the export file
     */
    public function processPortabilityRequest(DataSubjectRequest $request): string
    {
        if (! $request->canProcess()) {
            throw new \Exception('Request cannot be processed in its current state.');
        }

        $request->startProcessing();

        $user = $request->user;
        if (! $user) {
            $user = User::where('email', $request->email)->first();
        }

        if (! $user) {
            $request->complete('No user data found for this email address.');

            return '';
        }

        // Generate portable data export (machine-readable format)
        $exportData = $this->exportService->generatePortableExport($user);
        $filePath = $this->exportService->savePortableExportToFile($user, $exportData);

        $request->complete(
            'Portable data export generated in machine-readable format (JSON).',
            $filePath
        );

        $this->notifyExportReady($request, $filePath);

        Log::info('Portability request processed', [
            'request_number' => $request->request_number,
            'user_id' => $user->id,
        ]);

        return $filePath;
    }

    /**
     * Notify user that their export is ready.
     */
    protected function notifyExportReady(DataSubjectRequest $request, string $filePath): void
    {
        $downloadUrl = route('privacy.download-export', [
            'request' => $request->request_number,
            'token' => $request->verification_token,
        ]);

        Mail::send('emails.privacy.export-ready', [
            'request' => $request,
            'downloadUrl' => $downloadUrl,
        ], function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Your Data Export is Ready - '.config('app.name'));
        });
    }

    // ========================================
    // DATA EXPORT METHODS
    // ========================================

    /**
     * Generate a complete data export for a user.
     */
    public function generateDataExport(User $user): array
    {
        return $this->exportService->generateDataExport($user);
    }

    // ========================================
    // DATA DELETION/ANONYMIZATION METHODS
    // ========================================

    /**
     * Anonymize user data (soft deletion alternative).
     */
    public function anonymizeUserData(User $user): void
    {
        DB::beginTransaction();
        try {
            $anonymizedId = 'ANON_'.Str::random(10);

            // Anonymize user profile
            $user->update([
                'name' => 'Anonymous User',
                'email' => $anonymizedId.'@anonymized.local',
                'username' => $anonymizedId,
                'password' => bcrypt(Str::random(32)),
                'bio' => null,
                'avatar' => null,
                'status' => 'anonymized',
            ]);

            // Anonymize worker profile
            if ($user->workerProfile) {
                $user->workerProfile->update([
                    'phone' => null,
                    'address' => null,
                    'emergency_contact' => null,
                    'bank_details' => null,
                    'national_insurance' => null,
                ]);
            }

            // Anonymize business profile
            if ($user->businessProfile) {
                $user->businessProfile->update([
                    'contact_phone' => null,
                    'contact_email' => null,
                    'address' => null,
                ]);
            }

            // Anonymize messages
            $user->sentMessages()->update(['content' => '[Message removed for privacy]']);

            Log::info('User data anonymized', ['user_id' => $user->id]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User anonymization failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete user data completely (hard deletion).
     */
    public function deleteUserData(User $user): void
    {
        DB::beginTransaction();
        try {
            // Delete related data in order of dependencies

            // Delete messages
            $user->sentMessages()->delete();
            $user->receivedMessages()->delete();

            // Delete conversations
            $user->conversationsAsWorker()->delete();
            $user->conversationsAsBusiness()->delete();

            // Delete ratings
            $user->ratingsGiven()->delete();
            $user->ratingsReceived()->delete();

            // Delete shift-related data
            $user->shiftApplications()->delete();
            $user->shiftInvitations()->delete();

            // Handle shift assignments carefully - preserve for financial records
            // but anonymize personal data
            $user->shiftAssignments()->update([
                'worker_id' => null, // Or a placeholder anonymous user
            ]);

            // Delete availability data
            if (method_exists($user, 'availabilityBroadcasts')) {
                $user->availabilityBroadcasts()->delete();
            }

            // Delete badges
            if (method_exists($user, 'badges')) {
                $user->badges()->delete();
            }

            // Delete profiles
            if ($user->workerProfile) {
                $user->workerProfile->delete();
            }
            if ($user->businessProfile) {
                $user->businessProfile->delete();
            }
            if ($user->agencyProfile) {
                $user->agencyProfile->delete();
            }

            // Delete consent records
            ConsentRecord::where('user_id', $user->id)->delete();

            // Delete notifications
            $user->notifications()->delete();

            // Delete stored files (avatars, documents, etc.)
            $this->deleteUserFiles($user);

            // Finally, delete the user
            $user->delete();

            Log::info('User data deleted', ['user_id' => $user->id]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete user files from storage.
     */
    protected function deleteUserFiles(User $user): void
    {
        // Delete avatar
        if ($user->avatar && Storage::exists($user->avatar)) {
            Storage::delete($user->avatar);
        }

        // Delete documents
        $documentsPath = 'users/'.$user->id;
        if (Storage::exists($documentsPath)) {
            Storage::deleteDirectory($documentsPath);
        }
    }

    // ========================================
    // CONSENT MANAGEMENT METHODS
    // ========================================

    /**
     * Record consent for a user or session.
     *
     * @param  User|string  $userOrSession  User model or session ID
     */
    public function recordConsent(User|string $userOrSession, string $type, bool $consented, ?string $source = null): ConsentRecord
    {
        if ($userOrSession instanceof User) {
            return ConsentRecord::recordForUser(
                $userOrSession->id,
                $type,
                $consented,
                $source
            );
        }

        return ConsentRecord::recordForSession(
            $userOrSession,
            $type,
            $consented,
            $source
        );
    }

    /**
     * Withdraw consent for a user.
     */
    public function withdrawConsent(User $user, string $type): void
    {
        $consent = ConsentRecord::where('user_id', $user->id)
            ->where('consent_type', $type)
            ->first();

        if ($consent) {
            $consent->withdraw();

            Log::info('Consent withdrawn', [
                'user_id' => $user->id,
                'consent_type' => $type,
            ]);
        }
    }

    /**
     * Check if a user has given consent for a specific type.
     */
    public function hasConsent(User $user, string $type): bool
    {
        return ConsentRecord::hasUserConsent($user->id, $type);
    }

    /**
     * Get all active consents for a user.
     */
    public function getActiveConsents(User $user): array
    {
        return ConsentRecord::getActiveConsentsForUser($user->id);
    }

    /**
     * Update multiple consents at once.
     */
    public function updateConsents(User $user, array $consents, ?string $source = null): void
    {
        foreach ($consents as $type => $consented) {
            $this->recordConsent($user, $type, (bool) $consented, $source);
        }
    }

    // ========================================
    // RETENTION POLICY METHODS
    // ========================================

    /**
     * Apply all active retention policies.
     *
     * @return int Total number of affected records
     */
    public function applyRetentionPolicies(): int
    {
        $totalAffected = 0;
        $policies = DataRetentionPolicy::where('is_active', true)->get();

        foreach ($policies as $policy) {
            try {
                $affected = $policy->execute();
                $totalAffected += $affected;

                Log::info('Retention policy applied', [
                    'policy_id' => $policy->id,
                    'data_type' => $policy->data_type,
                    'affected' => $affected,
                ]);

            } catch (\Exception $e) {
                Log::error('Retention policy failed', [
                    'policy_id' => $policy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $totalAffected;
    }

    /**
     * Get retention policy status report.
     */
    public function getRetentionPolicyReport(): array
    {
        $policies = DataRetentionPolicy::all();

        return $policies->map(function ($policy) {
            return [
                'id' => $policy->id,
                'data_type' => $policy->data_type,
                'model_class' => $policy->model_class,
                'retention_days' => $policy->retention_days,
                'action' => $policy->action,
                'is_active' => $policy->is_active,
                'last_executed' => $policy->last_executed_at?->toIso8601String(),
                'last_affected_count' => $policy->last_affected_count,
                'pending_count' => $policy->getAffectedCount(),
            ];
        })->toArray();
    }

    // ========================================
    // COMPLIANCE REPORTING
    // ========================================

    /**
     * Get compliance dashboard statistics.
     */
    public function getComplianceStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'dsr_stats' => [
                'total' => DataSubjectRequest::count(),
                'pending' => DataSubjectRequest::pending()->count(),
                'processing' => DataSubjectRequest::processing()->count(),
                'completed_30d' => DataSubjectRequest::where('completed_at', '>=', $thirtyDaysAgo)->count(),
                'overdue' => DataSubjectRequest::overdue()->count(),
                'average_response_days' => $this->calculateAverageResponseTime(),
            ],
            'consent_stats' => [
                'total_records' => ConsentRecord::count(),
                'active_consents' => ConsentRecord::active()->count(),
                'consent_rates' => $this->calculateConsentRates(),
            ],
            'retention_stats' => [
                'active_policies' => DataRetentionPolicy::active()->count(),
                'last_execution' => DataRetentionPolicy::max('last_executed_at'),
                'total_cleaned_30d' => DataRetentionPolicy::where('last_executed_at', '>=', $thirtyDaysAgo)
                    ->sum('last_affected_count'),
            ],
        ];
    }

    /**
     * Calculate average response time for DSRs.
     */
    protected function calculateAverageResponseTime(): float
    {
        $completedRequests = DataSubjectRequest::whereNotNull('completed_at')
            ->whereNotNull('verified_at')
            ->get();

        if ($completedRequests->isEmpty()) {
            return 0;
        }

        $totalDays = $completedRequests->sum(function ($request) {
            return $request->verified_at->diffInDays($request->completed_at);
        });

        return round($totalDays / $completedRequests->count(), 1);
    }

    /**
     * Calculate consent rates by type.
     */
    protected function calculateConsentRates(): array
    {
        $types = ConsentRecord::getTypes();
        $rates = [];

        foreach ($types as $type => $info) {
            $total = ConsentRecord::where('consent_type', $type)->count();
            $consented = ConsentRecord::where('consent_type', $type)
                ->where('consented', true)
                ->whereNull('withdrawn_at')
                ->count();

            $rates[$type] = [
                'label' => $info['label'],
                'total' => $total,
                'consented' => $consented,
                'rate' => $total > 0 ? round(($consented / $total) * 100, 1) : 0,
            ];
        }

        return $rates;
    }

    /**
     * Generate a compliance audit report.
     */
    public function generateAuditReport(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return [
            'period' => [
                'start' => $startDate->toIso8601String(),
                'end' => $endDate->toIso8601String(),
            ],
            'data_subject_requests' => [
                'received' => DataSubjectRequest::whereBetween('created_at', [$startDate, $endDate])->count(),
                'completed' => DataSubjectRequest::whereBetween('completed_at', [$startDate, $endDate])->count(),
                'by_type' => DataSubjectRequest::whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
                'average_response_days' => $this->calculateAverageResponseTime(),
            ],
            'consent_changes' => [
                'new_consents' => ConsentRecord::whereBetween('consented_at', [$startDate, $endDate])->count(),
                'withdrawals' => ConsentRecord::whereBetween('withdrawn_at', [$startDate, $endDate])->count(),
            ],
            'data_retention' => [
                'policies_executed' => DataRetentionPolicy::whereBetween('last_executed_at', [$startDate, $endDate])->count(),
                'records_affected' => DataRetentionPolicy::whereBetween('last_executed_at', [$startDate, $endDate])
                    ->sum('last_affected_count'),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
