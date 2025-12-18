<?php

namespace App\Services;

use App\Models\KycVerification;
use App\Models\User;
use App\Notifications\KycApprovedNotification;
use App\Notifications\KycExpiringNotification;
use App\Notifications\KycRejectedNotification;
use App\Notifications\KycSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * WKR-001: KYC Service
 *
 * Handles comprehensive KYC verification flow including document uploads,
 * provider integrations (Onfido, Jumio, Veriff), and admin review workflow.
 */
class KycService
{
    /**
     * Active KYC provider.
     */
    protected string $provider;

    /**
     * Provider configurations.
     */
    protected array $providerConfig;

    public function __construct()
    {
        $this->provider = config('kyc.provider', 'manual');
        $this->providerConfig = config('kyc.providers.'.$this->provider, []);
    }

    // ==================== INITIATION & SUBMISSION ====================

    /**
     * Initiate a new KYC verification for a user.
     *
     * @param  array  $documents  Contains document_type, document_country, document_front, document_back, selfie, document_number, document_expiry
     * @return array{success: bool, verification_id?: int, error?: string, message?: string}
     */
    public function initiateVerification(User $user, array $documents): array
    {
        // Check for existing pending verification
        $existingPending = KycVerification::where('user_id', $user->id)
            ->whereIn('status', [KycVerification::STATUS_PENDING, KycVerification::STATUS_IN_REVIEW])
            ->first();

        if ($existingPending) {
            return [
                'success' => false,
                'error' => 'You already have a pending KYC verification. Please wait for it to be reviewed.',
                'verification_id' => $existingPending->id,
            ];
        }

        // Check retry eligibility
        $lastRejected = KycVerification::where('user_id', $user->id)
            ->where('status', KycVerification::STATUS_REJECTED)
            ->latest()
            ->first();

        if ($lastRejected && ! $lastRejected->canRetry()) {
            return [
                'success' => false,
                'error' => 'Maximum verification attempts exceeded. Please contact support.',
            ];
        }

        try {
            DB::beginTransaction();

            // Upload documents to secure storage
            $documentPaths = $this->uploadDocuments($user, $documents);

            // Create verification record
            $verification = KycVerification::create([
                'user_id' => $user->id,
                'status' => KycVerification::STATUS_PENDING,
                'document_type' => $documents['document_type'],
                'document_number' => $documents['document_number'] ?? null,
                'document_country' => $documents['document_country'],
                'document_expiry' => $documents['document_expiry'] ?? null,
                'document_front_path' => $documentPaths['front'],
                'document_back_path' => $documentPaths['back'] ?? null,
                'selfie_path' => $documentPaths['selfie'] ?? null,
                'provider' => $this->provider,
                'attempt_count' => $lastRejected ? $lastRejected->attempt_count + 1 : 1,
                'max_attempts' => config('kyc.max_attempts', 3),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // If using automated provider, initiate check
            if ($this->provider !== 'manual') {
                $providerResult = $this->initiateProviderVerification($verification);

                if (! $providerResult['success']) {
                    DB::rollBack();
                    // Clean up uploaded files
                    $this->deleteDocuments($documentPaths);

                    return $providerResult;
                }

                $verification->update([
                    'provider_applicant_id' => $providerResult['applicant_id'] ?? null,
                    'provider_reference' => $providerResult['reference'] ?? null,
                ]);
            }

            DB::commit();

            // Send notification
            try {
                $user->notify(new KycSubmittedNotification($verification));
            } catch (\Exception $e) {
                Log::warning('Failed to send KYC submitted notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'verification_id' => $verification->id,
                'message' => 'KYC verification submitted successfully.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate KYC verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to submit verification. Please try again.',
            ];
        }
    }

    /**
     * Submit verification for admin review.
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function submitForReview(KycVerification $verification): array
    {
        if (! $verification->isPending()) {
            return [
                'success' => false,
                'error' => 'Only pending verifications can be submitted for review.',
            ];
        }

        try {
            $verification->submitForReview();

            return [
                'success' => true,
                'message' => 'Verification submitted for review.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to submit verification for review', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to submit for review.',
            ];
        }
    }

    // ==================== PROVIDER INTEGRATION ====================

    /**
     * Process webhook from KYC provider.
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function processProviderWebhook(string $provider, array $data): array
    {
        Log::info('Processing KYC provider webhook', [
            'provider' => $provider,
            'event' => $data['event'] ?? $data['action'] ?? 'unknown',
        ]);

        try {
            return match ($provider) {
                'onfido' => $this->processOnfidoWebhook($data),
                'jumio' => $this->processJumioWebhook($data),
                'veriff' => $this->processVeriffWebhook($data),
                default => [
                    'success' => false,
                    'error' => 'Unknown provider: '.$provider,
                ],
            };
        } catch (\Exception $e) {
            Log::error('Failed to process provider webhook', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process webhook.',
            ];
        }
    }

    /**
     * Process Onfido webhook.
     */
    protected function processOnfidoWebhook(array $data): array
    {
        $resourceType = $data['payload']['resource_type'] ?? null;
        $action = $data['payload']['action'] ?? null;
        $object = $data['payload']['object'] ?? [];

        if ($resourceType !== 'check' || $action !== 'check.completed') {
            return ['success' => true, 'message' => 'Webhook received but no action taken.'];
        }

        $verification = KycVerification::where('provider_check_id', $object['id'])->first();

        if (! $verification) {
            return ['success' => false, 'error' => 'Verification not found.'];
        }

        $result = $object['result'] ?? 'unknown';

        return $this->processProviderResult($verification, [
            'result' => $result,
            'details' => $object,
        ]);
    }

    /**
     * Process Jumio webhook.
     */
    protected function processJumioWebhook(array $data): array
    {
        $scanReference = $data['scanReference'] ?? null;
        $verification = KycVerification::where('provider_reference', $scanReference)->first();

        if (! $verification) {
            return ['success' => false, 'error' => 'Verification not found.'];
        }

        $verificationStatus = $data['verificationStatus'] ?? 'UNKNOWN';

        $result = match ($verificationStatus) {
            'APPROVED_VERIFIED' => 'clear',
            'DENIED_FRAUD', 'DENIED_UNSUPPORTED_ID_TYPE', 'DENIED_UNSUPPORTED_ID_COUNTRY' => 'reject',
            default => 'consider',
        };

        return $this->processProviderResult($verification, [
            'result' => $result,
            'details' => $data,
        ]);
    }

    /**
     * Process Veriff webhook.
     */
    protected function processVeriffWebhook(array $data): array
    {
        $sessionId = $data['verification']['id'] ?? null;
        $verification = KycVerification::where('provider_reference', $sessionId)->first();

        if (! $verification) {
            return ['success' => false, 'error' => 'Verification not found.'];
        }

        $status = $data['verification']['status'] ?? 'unknown';
        $code = $data['verification']['code'] ?? null;

        $result = match ($status) {
            'approved' => 'clear',
            'declined' => 'reject',
            'resubmission_requested' => 'consider',
            default => 'unknown',
        };

        return $this->processProviderResult($verification, [
            'result' => $result,
            'code' => $code,
            'details' => $data,
        ]);
    }

    /**
     * Process provider verification result.
     */
    protected function processProviderResult(KycVerification $verification, array $result): array
    {
        try {
            DB::beginTransaction();

            $verification->storeVerificationResult($result);

            $providerResult = $result['result'] ?? 'unknown';

            if ($providerResult === 'clear') {
                $verification->approve();
                $this->sendApprovalNotification($verification);
            } elseif ($providerResult === 'reject') {
                $reason = $this->extractRejectionReason($result['details'] ?? []);
                $verification->reject($reason, $result['details']['rejection_codes'] ?? null);
                $this->sendRejectionNotification($verification);
            } else {
                // Mark for manual review
                $verification->markInReview();
            }

            DB::commit();

            return [
                'success' => true,
                'status' => $verification->fresh()->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process provider result', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process verification result.',
            ];
        }
    }

    // ==================== ADMIN REVIEW ====================

    /**
     * Approve a verification (admin action).
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function approveVerification(KycVerification $verification, User $admin, ?string $notes = null): array
    {
        if (! in_array($verification->status, [KycVerification::STATUS_PENDING, KycVerification::STATUS_IN_REVIEW])) {
            return [
                'success' => false,
                'error' => 'Only pending or in-review verifications can be approved.',
            ];
        }

        try {
            $verification->approve($admin->id, $notes);
            $this->sendApprovalNotification($verification);

            return [
                'success' => true,
                'message' => 'Verification approved successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to approve verification', [
                'verification_id' => $verification->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to approve verification.',
            ];
        }
    }

    /**
     * Reject a verification (admin action).
     *
     * @return array{success: bool, error?: string, message?: string}
     */
    public function rejectVerification(KycVerification $verification, string $reason, User $admin): array
    {
        if (! in_array($verification->status, [KycVerification::STATUS_PENDING, KycVerification::STATUS_IN_REVIEW])) {
            return [
                'success' => false,
                'error' => 'Only pending or in-review verifications can be rejected.',
            ];
        }

        try {
            $verification->reject($reason, null, $admin->id);
            $this->sendRejectionNotification($verification);

            return [
                'success' => true,
                'message' => 'Verification rejected.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reject verification', [
                'verification_id' => $verification->id,
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to reject verification.',
            ];
        }
    }

    // ==================== EXPIRY MANAGEMENT ====================

    /**
     * Check for expiring documents and send notifications.
     *
     * @return array{processed: int, notifications_sent: int}
     */
    public function checkDocumentExpiry(): array
    {
        $warningDays = config('kyc.expiry_warning_days', 30);
        $processed = 0;
        $notificationsSent = 0;

        // Find verifications expiring soon
        $expiringVerifications = KycVerification::expiringSoon($warningDays)
            ->with('user')
            ->get();

        foreach ($expiringVerifications as $verification) {
            $processed++;

            // Check if we already sent a notification recently
            $recentNotification = $verification->metadata['expiry_notification_sent'] ?? null;

            if ($recentNotification && Carbon::parse($recentNotification)->diffInDays(now()) < 7) {
                continue;
            }

            try {
                $verification->user->notify(new KycExpiringNotification($verification));
                $verification->addMetadata('expiry_notification_sent', now()->toIso8601String());
                $notificationsSent++;
            } catch (\Exception $e) {
                Log::warning('Failed to send expiry notification', [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Mark actually expired verifications
        $expiredVerifications = KycVerification::where('status', KycVerification::STATUS_APPROVED)
            ->where(function ($q) {
                $q->where('document_expiry', '<', now())
                    ->orWhere('expires_at', '<', now());
            })
            ->get();

        foreach ($expiredVerifications as $verification) {
            $verification->markExpired();
            $processed++;
        }

        Log::info('KYC expiry check completed', [
            'processed' => $processed,
            'notifications_sent' => $notificationsSent,
        ]);

        return [
            'processed' => $processed,
            'notifications_sent' => $notificationsSent,
        ];
    }

    // ==================== REQUIREMENTS ====================

    /**
     * Get verification requirements for a specific country.
     *
     * @param  string  $country  ISO 3166-1 alpha-2 country code
     */
    public function getVerificationRequirements(string $country): array
    {
        $defaults = [
            'document_types' => config('kyc.document_types', [
                'passport',
                'drivers_license',
                'national_id',
            ]),
            'selfie_required' => config('kyc.selfie_required', true),
            'document_back_required' => true,
            'address_verification' => false,
        ];

        // Country-specific requirements
        $countryRequirements = config('kyc.country_requirements.'.strtoupper($country), []);

        return array_merge($defaults, $countryRequirements);
    }

    /**
     * Get the user's current KYC status.
     */
    public function getKycStatus(User $user): array
    {
        $latestVerification = KycVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        return [
            'is_verified' => $user->kyc_verified,
            'kyc_level' => $user->kyc_level,
            'verified_at' => $user->kyc_verified_at?->toIso8601String(),
            'latest_verification' => $latestVerification ? [
                'id' => $latestVerification->id,
                'status' => $latestVerification->status,
                'status_name' => $latestVerification->status_name,
                'document_type' => $latestVerification->document_type,
                'document_type_name' => $latestVerification->document_type_name,
                'created_at' => $latestVerification->created_at->toIso8601String(),
                'expires_at' => $latestVerification->expires_at?->toIso8601String(),
                'rejection_reason' => $latestVerification->rejection_reason,
                'can_retry' => $latestVerification->canRetry(),
            ] : null,
            'can_submit' => ! $latestVerification || $latestVerification->canRetry()
                || in_array($latestVerification->status, [
                    KycVerification::STATUS_EXPIRED,
                    KycVerification::STATUS_REJECTED,
                ]),
        ];
    }

    // ==================== DOCUMENT MANAGEMENT ====================

    /**
     * Upload documents to secure storage.
     */
    protected function uploadDocuments(User $user, array $documents): array
    {
        $paths = [];
        $disk = config('kyc.storage_disk', 'private');
        $basePath = "kyc/{$user->id}/".now()->format('Y/m');

        // Upload front of document (required)
        if (isset($documents['document_front']) && $documents['document_front'] instanceof UploadedFile) {
            $paths['front'] = $documents['document_front']->store($basePath, $disk);
        } elseif (is_string($documents['document_front'])) {
            $paths['front'] = $documents['document_front'];
        } else {
            throw new \InvalidArgumentException('Document front is required.');
        }

        // Upload back of document (optional)
        if (isset($documents['document_back'])) {
            if ($documents['document_back'] instanceof UploadedFile) {
                $paths['back'] = $documents['document_back']->store($basePath, $disk);
            } elseif (is_string($documents['document_back'])) {
                $paths['back'] = $documents['document_back'];
            }
        }

        // Upload selfie (optional)
        if (isset($documents['selfie'])) {
            if ($documents['selfie'] instanceof UploadedFile) {
                $paths['selfie'] = $documents['selfie']->store($basePath, $disk);
            } elseif (is_string($documents['selfie'])) {
                $paths['selfie'] = $documents['selfie'];
            }
        }

        return $paths;
    }

    /**
     * Delete uploaded documents.
     */
    protected function deleteDocuments(array $paths): void
    {
        $disk = config('kyc.storage_disk', 'private');

        foreach ($paths as $path) {
            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * Get secure URL for viewing a document.
     *
     * @param  string  $type  'front', 'back', or 'selfie'
     */
    public function getDocumentUrl(KycVerification $verification, string $type): ?string
    {
        $path = match ($type) {
            'front' => $verification->document_front_path,
            'back' => $verification->document_back_path,
            'selfie' => $verification->selfie_path,
            default => null,
        };

        if (! $path) {
            return null;
        }

        $disk = config('kyc.storage_disk', 'private');

        // Generate temporary signed URL (valid for 15 minutes)
        return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(15));
    }

    // ==================== PROVIDER HELPERS ====================

    /**
     * Initiate verification with external provider.
     */
    protected function initiateProviderVerification(KycVerification $verification): array
    {
        return match ($this->provider) {
            'onfido' => $this->initiateOnfidoVerification($verification),
            'jumio' => $this->initiateJumioVerification($verification),
            'veriff' => $this->initiateVeriffVerification($verification),
            default => ['success' => true], // Manual provider needs no external initiation
        };
    }

    /**
     * Initiate Onfido verification.
     */
    protected function initiateOnfidoVerification(KycVerification $verification): array
    {
        $apiKey = $this->providerConfig['api_key'] ?? null;

        if (! $apiKey) {
            return ['success' => true]; // Fall back to manual review
        }

        try {
            $user = $verification->user;

            // Create applicant
            $response = Http::withHeaders([
                'Authorization' => 'Token token='.$apiKey,
            ])->post('https://api.onfido.com/v3.6/applicants', [
                'first_name' => $user->first_name ?? 'Unknown',
                'last_name' => $user->last_name ?? 'Unknown',
                'email' => $user->email,
            ]);

            if (! $response->successful()) {
                Log::error('Onfido applicant creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['success' => true]; // Fall back to manual review
            }

            $applicantId = $response->json('id');

            return [
                'success' => true,
                'applicant_id' => $applicantId,
            ];
        } catch (\Exception $e) {
            Log::error('Onfido API error', ['error' => $e->getMessage()]);

            return ['success' => true]; // Fall back to manual review
        }
    }

    /**
     * Initiate Jumio verification.
     */
    protected function initiateJumioVerification(KycVerification $verification): array
    {
        // Jumio implementation would go here
        return ['success' => true];
    }

    /**
     * Initiate Veriff verification.
     */
    protected function initiateVeriffVerification(KycVerification $verification): array
    {
        // Veriff implementation would go here
        return ['success' => true];
    }

    /**
     * Extract rejection reason from provider response.
     */
    protected function extractRejectionReason(array $details): string
    {
        // Check for common rejection reasons in provider data
        if (isset($details['breakdown'])) {
            foreach ($details['breakdown'] as $check => $result) {
                if (isset($result['result']) && $result['result'] !== 'clear') {
                    return ucfirst(str_replace('_', ' ', $check)).' verification failed.';
                }
            }
        }

        if (isset($details['reason'])) {
            return $details['reason'];
        }

        return 'Document verification failed. Please ensure your documents are clear and valid.';
    }

    // ==================== NOTIFICATIONS ====================

    /**
     * Send approval notification.
     */
    protected function sendApprovalNotification(KycVerification $verification): void
    {
        try {
            $verification->user->notify(new KycApprovedNotification($verification));
        } catch (\Exception $e) {
            Log::warning('Failed to send KYC approval notification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send rejection notification.
     */
    protected function sendRejectionNotification(KycVerification $verification): void
    {
        try {
            $verification->user->notify(new KycRejectedNotification($verification));
        } catch (\Exception $e) {
            Log::warning('Failed to send KYC rejection notification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Get verifications pending review.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPendingReviews(array $filters = [])
    {
        $query = KycVerification::query()
            ->with('user')
            ->requiringReview()
            ->orderBy('created_at', 'asc');

        if (isset($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        if (isset($filters['country'])) {
            $query->where('document_country', $filters['country']);
        }

        if (isset($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Bulk approve verifications.
     *
     * @return array{approved: int, failed: int, errors: array}
     */
    public function bulkApprove(array $ids, User $admin): array
    {
        $approved = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            $verification = KycVerification::find($id);

            if (! $verification) {
                $failed++;
                $errors[] = "Verification #{$id} not found.";

                continue;
            }

            $result = $this->approveVerification($verification, $admin);

            if ($result['success']) {
                $approved++;
            } else {
                $failed++;
                $errors[] = "Verification #{$id}: ".($result['error'] ?? 'Unknown error');
            }
        }

        return [
            'approved' => $approved,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk reject verifications.
     *
     * @return array{rejected: int, failed: int, errors: array}
     */
    public function bulkReject(array $ids, string $reason, User $admin): array
    {
        $rejected = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            $verification = KycVerification::find($id);

            if (! $verification) {
                $failed++;
                $errors[] = "Verification #{$id} not found.";

                continue;
            }

            $result = $this->rejectVerification($verification, $reason, $admin);

            if ($result['success']) {
                $rejected++;
            } else {
                $failed++;
                $errors[] = "Verification #{$id}: ".($result['error'] ?? 'Unknown error');
            }
        }

        return [
            'rejected' => $rejected,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }
}
