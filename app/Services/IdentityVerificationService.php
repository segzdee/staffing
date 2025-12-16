<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\LivenessCheck;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\WorkerProfile;
use App\Notifications\IdentityVerificationInitiatedNotification;
use App\Notifications\IdentityVerifiedNotification;
use App\Notifications\IdentityVerificationFailedNotification;
use App\Notifications\ManualReviewRequiredNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * IdentityVerificationService - STAFF-REG-004
 *
 * Handles KYC verification flow with Onfido/Jumio integration.
 */
class IdentityVerificationService
{
    /**
     * Onfido API base URL.
     */
    protected string $onfidoBaseUrl;

    /**
     * Onfido API token.
     */
    protected string $onfidoApiToken;

    /**
     * Webhook secret for Onfido.
     */
    protected string $webhookSecret;

    public function __construct()
    {
        $this->onfidoBaseUrl = config('services.onfido.api_url', 'https://api.onfido.com/v3.6');
        $this->onfidoApiToken = config('services.onfido.api_token', '');
        $this->webhookSecret = config('services.onfido.webhook_secret', '');
    }

    /**
     * Initiate a new KYC verification session.
     *
     * @param User $user
     * @param string $level Verification level (basic, standard, enhanced)
     * @return array
     */
    public function initiateVerification(User $user, string $level = 'standard'): array
    {
        // Check for existing pending verification
        $existingVerification = IdentityVerification::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'awaiting_input', 'processing'])
            ->first();

        if ($existingVerification) {
            if ($existingVerification->haValidSdkToken()) {
                return [
                    'success' => true,
                    'verification_id' => $existingVerification->id,
                    'sdk_token' => $existingVerification->sdk_token,
                    'status' => $existingVerification->status,
                    'message' => 'Existing verification session found.',
                ];
            }

            // Generate new SDK token for existing verification
            $tokenResult = $this->generateSdkToken($existingVerification);
            if ($tokenResult['success']) {
                return [
                    'success' => true,
                    'verification_id' => $existingVerification->id,
                    'sdk_token' => $tokenResult['sdk_token'],
                    'status' => $existingVerification->status,
                    'message' => 'New SDK token generated.',
                ];
            }
        }

        // Check if user can retry (max attempts not exceeded)
        $previousVerification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->latest()
            ->first();

        if ($previousVerification && !$previousVerification->canRetry()) {
            return [
                'success' => false,
                'error' => 'Maximum verification attempts exceeded. Please contact support.',
            ];
        }

        try {
            DB::beginTransaction();

            // Create applicant in Onfido
            $applicantResult = $this->createOnfidoApplicant($user);

            if (!$applicantResult['success']) {
                DB::rollBack();
                return $applicantResult;
            }

            // Create local verification record
            $verification = IdentityVerification::create([
                'user_id' => $user->id,
                'provider' => 'onfido',
                'provider_applicant_id' => $applicantResult['applicant_id'],
                'status' => IdentityVerification::STATUS_PENDING,
                'verification_level' => $level,
                'attempt_count' => $previousVerification ? $previousVerification->attempt_count + 1 : 1,
                'max_attempts' => 3,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Generate SDK token
            $tokenResult = $this->generateSdkToken($verification);

            if (!$tokenResult['success']) {
                DB::rollBack();
                return $tokenResult;
            }

            // Update worker profile KYC status
            $user->workerProfile?->update([
                'kyc_status' => 'pending',
            ]);

            DB::commit();

            // Send notification
            try {
                $user->notify(new IdentityVerificationInitiatedNotification($verification));
            } catch (\Exception $e) {
                Log::warning('Failed to send verification initiated notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'verification_id' => $verification->id,
                'sdk_token' => $tokenResult['sdk_token'],
                'status' => $verification->status,
                'level' => $level,
                'message' => 'Verification session initiated successfully.',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate verification. Please try again.',
            ];
        }
    }

    /**
     * Create an applicant in Onfido.
     *
     * @param User $user
     * @return array
     */
    protected function createOnfidoApplicant(User $user): array
    {
        if (!$this->onfidoApiToken) {
            // Return mock data for development
            return [
                'success' => true,
                'applicant_id' => 'mock_applicant_' . $user->id . '_' . time(),
            ];
        }

        try {
            $profile = $user->workerProfile;

            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $this->onfidoApiToken,
                'Content-Type' => 'application/json',
            ])->post($this->onfidoBaseUrl . '/applicants', [
                'first_name' => $profile?->first_name ?? $user->first_name ?? 'Unknown',
                'last_name' => $profile?->last_name ?? $user->last_name ?? 'Unknown',
                'email' => $user->email,
                'dob' => $profile?->date_of_birth?->format('Y-m-d'),
                'address' => [
                    'street' => $profile?->address,
                    'town' => $profile?->city,
                    'state' => $profile?->state,
                    'postcode' => $profile?->zip_code,
                    'country' => $this->convertCountryToIso3($profile?->country ?? 'US'),
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'applicant_id' => $data['id'],
                ];
            }

            Log::error('Onfido applicant creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create verification applicant.',
            ];
        } catch (\Exception $e) {
            Log::error('Onfido API error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Verification service unavailable.',
            ];
        }
    }

    /**
     * Generate SDK token for Onfido.
     *
     * @param IdentityVerification $verification
     * @return array
     */
    protected function generateSdkToken(IdentityVerification $verification): array
    {
        if (!$this->onfidoApiToken) {
            // Return mock token for development
            $mockToken = base64_encode(json_encode([
                'verification_id' => $verification->id,
                'applicant_id' => $verification->provider_applicant_id,
                'expires_at' => now()->addMinutes(90)->toIso8601String(),
            ]));

            $verification->storeSdkToken($mockToken, 90);

            return [
                'success' => true,
                'sdk_token' => $mockToken,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $this->onfidoApiToken,
                'Content-Type' => 'application/json',
            ])->post($this->onfidoBaseUrl . '/sdk_token', [
                'applicant_id' => $verification->provider_applicant_id,
                'referrer' => config('app.url') . '/*',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $verification->storeSdkToken($data['token'], 90);

                return [
                    'success' => true,
                    'sdk_token' => $data['token'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate verification token.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate SDK token', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Verification service unavailable.',
            ];
        }
    }

    /**
     * Create a verification check in Onfido.
     *
     * @param IdentityVerification $verification
     * @return array
     */
    public function createVerificationCheck(IdentityVerification $verification): array
    {
        if (!$this->onfidoApiToken) {
            // Mock response for development
            $verification->update([
                'status' => IdentityVerification::STATUS_PROCESSING,
                'provider_check_id' => 'mock_check_' . $verification->id . '_' . time(),
            ]);

            return ['success' => true, 'check_id' => $verification->provider_check_id];
        }

        try {
            $reportTypes = $this->getReportTypesForLevel($verification->verification_level);

            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $this->onfidoApiToken,
                'Content-Type' => 'application/json',
            ])->post($this->onfidoBaseUrl . '/checks', [
                'applicant_id' => $verification->provider_applicant_id,
                'report_names' => $reportTypes,
                'consider' => [],
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $verification->update([
                    'status' => IdentityVerification::STATUS_PROCESSING,
                    'provider_check_id' => $data['id'],
                    'last_attempt_at' => now(),
                ]);

                return [
                    'success' => true,
                    'check_id' => $data['id'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create verification check.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create Onfido check', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Verification service unavailable.',
            ];
        }
    }

    /**
     * Get report types based on verification level.
     *
     * @param string $level
     * @return array
     */
    protected function getReportTypesForLevel(string $level): array
    {
        return match ($level) {
            'basic' => ['document'],
            'standard' => ['document', 'facial_similarity_photo'],
            'enhanced' => ['document', 'facial_similarity_video', 'known_faces'],
            default => ['document', 'facial_similarity_photo'],
        };
    }

    /**
     * Process webhook from Onfido.
     *
     * @param array $payload
     * @return array
     */
    public function processWebhook(array $payload): array
    {
        $action = $payload['payload']['action'] ?? null;
        $resourceType = $payload['payload']['resource_type'] ?? null;

        Log::info('Processing Onfido webhook', [
            'action' => $action,
            'resource_type' => $resourceType,
        ]);

        if ($resourceType === 'check' && $action === 'check.completed') {
            return $this->processCheckCompleted($payload['payload']['object']);
        }

        if ($resourceType === 'report' && $action === 'report.completed') {
            return $this->processReportCompleted($payload['payload']['object']);
        }

        return ['success' => true, 'message' => 'Webhook received but no action taken.'];
    }

    /**
     * Process check completed webhook.
     *
     * @param array $checkData
     * @return array
     */
    protected function processCheckCompleted(array $checkData): array
    {
        $verification = IdentityVerification::where('provider_check_id', $checkData['id'])->first();

        if (!$verification) {
            Log::warning('Verification not found for check', ['check_id' => $checkData['id']]);
            return ['success' => false, 'error' => 'Verification not found.'];
        }

        $result = $checkData['result'] ?? null;
        $status = $checkData['status'] ?? null;

        if ($status !== 'complete') {
            return ['success' => true, 'message' => 'Check not yet complete.'];
        }

        return $this->processVerificationResult($verification, [
            'result' => $result,
            'details' => $checkData,
        ]);
    }

    /**
     * Process report completed webhook.
     *
     * @param array $reportData
     * @return array
     */
    protected function processReportCompleted(array $reportData): array
    {
        // Find verification by check ID
        $verification = IdentityVerification::where('provider_check_id', $reportData['check_id'] ?? null)->first();

        if (!$verification) {
            return ['success' => true, 'message' => 'Report received but no matching verification.'];
        }

        // Store report ID
        $verification->update(['provider_report_id' => $reportData['id']]);

        // Process based on report name
        $reportName = $reportData['name'] ?? '';

        if (str_contains($reportName, 'facial_similarity')) {
            $this->processFacialSimilarityReport($verification, $reportData);
        }

        return ['success' => true];
    }

    /**
     * Process facial similarity report.
     *
     * @param IdentityVerification $verification
     * @param array $reportData
     * @return void
     */
    protected function processFacialSimilarityReport(IdentityVerification $verification, array $reportData): void
    {
        $result = $reportData['result'] ?? null;
        $breakdown = $reportData['breakdown'] ?? [];

        $faceMatchScore = $breakdown['face_comparison']['result'] ?? null;

        $verification->storeFaceMatchResults(
            $result === 'clear' ? 'match' : 'no_match',
            $this->normalizeScore($faceMatchScore)
        );
    }

    /**
     * Process verification result and update status.
     *
     * @param IdentityVerification $verification
     * @param array $result
     * @return array
     */
    public function processVerificationResult(IdentityVerification $verification, array $result): array
    {
        try {
            DB::beginTransaction();

            $verificationResult = $result['result'] ?? null;
            $details = $result['details'] ?? [];

            // Store results
            $verification->storeResults([
                'result' => $verificationResult,
                'details' => $details,
                'confidence_score' => $this->calculateConfidenceScore($details),
                'sub_results' => $details['breakdown'] ?? null,
            ]);

            // Extract and store verified data
            $extractedData = $this->extractVerifiedData($details);
            if ($extractedData) {
                $verification->storeExtractedData($extractedData);
            }

            // Determine final status
            if ($verificationResult === 'clear') {
                $verification->approve();
                $this->onVerificationApproved($verification);
            } elseif ($verificationResult === 'consider') {
                $verification->markForManualReview('Verification requires manual review.');
                $this->onManualReviewRequired($verification);
            } else {
                $rejectionReason = $this->determineRejectionReason($details);
                $verification->reject($rejectionReason, $details);
                $this->onVerificationRejected($verification);
            }

            DB::commit();

            return [
                'success' => true,
                'status' => $verification->status,
                'result' => $verificationResult,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process verification result', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to process verification result.',
            ];
        }
    }

    /**
     * Extract verified data from provider response.
     *
     * @param array $details
     * @return array|null
     */
    public function extractVerifiedData(array $details): ?array
    {
        $documentData = $details['documents'][0] ?? $details['document'] ?? null;

        if (!$documentData) {
            return null;
        }

        return [
            'first_name' => $documentData['first_name'] ?? null,
            'last_name' => $documentData['last_name'] ?? null,
            'date_of_birth' => $documentData['date_of_birth'] ?? null,
            'document_number' => $documentData['document_number'] ?? null,
            'expiry_date' => $documentData['expiry_date'] ?? null,
            'nationality' => $documentData['nationality'] ?? null,
            'gender' => $documentData['gender'] ?? null,
            'address' => isset($documentData['address']) ? json_encode($documentData['address']) : null,
        ];
    }

    /**
     * Handle successful verification.
     *
     * @param IdentityVerification $verification
     * @return void
     */
    protected function onVerificationApproved(IdentityVerification $verification): void
    {
        try {
            $verification->user->notify(new IdentityVerifiedNotification($verification));
        } catch (\Exception $e) {
            Log::warning('Failed to send verification approved notification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed verification.
     *
     * @param IdentityVerification $verification
     * @return void
     */
    protected function onVerificationRejected(IdentityVerification $verification): void
    {
        // Update worker profile
        $verification->user->workerProfile?->update([
            'kyc_status' => 'rejected',
        ]);

        try {
            $verification->user->notify(new IdentityVerificationFailedNotification($verification));
        } catch (\Exception $e) {
            Log::warning('Failed to send verification failed notification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle manual review required.
     *
     * @param IdentityVerification $verification
     * @return void
     */
    protected function onManualReviewRequired(IdentityVerification $verification): void
    {
        // Update worker profile
        $verification->user->workerProfile?->update([
            'kyc_status' => 'manual_review',
        ]);

        try {
            $verification->user->notify(new ManualReviewRequiredNotification($verification));
        } catch (\Exception $e) {
            Log::warning('Failed to send manual review notification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify admins (if configured)
        $this->notifyAdminsOfManualReview($verification);
    }

    /**
     * Notify admins of manual review required.
     *
     * @param IdentityVerification $verification
     * @return void
     */
    protected function notifyAdminsOfManualReview(IdentityVerification $verification): void
    {
        // Implementation depends on admin notification system
        Log::info('Manual review required for verification', [
            'verification_id' => $verification->id,
            'user_id' => $verification->user_id,
        ]);
    }

    /**
     * Get verification status for user.
     *
     * @param User $user
     * @return array
     */
    public function getVerificationStatus(User $user): array
    {
        $verification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$verification) {
            return [
                'status' => 'not_started',
                'can_initiate' => true,
                'verification' => null,
            ];
        }

        $canRetry = $verification->isRejected() && $verification->canRetry();

        return [
            'status' => $verification->status,
            'verification_id' => $verification->id,
            'verification_level' => $verification->verification_level,
            'can_initiate' => $canRetry,
            'can_continue' => $verification->isPending() && $verification->haValidSdkToken(),
            'sdk_token' => $verification->haValidSdkToken() ? $verification->sdk_token : null,
            'result' => $verification->result,
            'rejection_reason' => $verification->rejection_reason,
            'attempt_count' => $verification->attempt_count,
            'max_attempts' => $verification->max_attempts,
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'created_at' => $verification->created_at->toIso8601String(),
        ];
    }

    /**
     * Retry verification after rejection.
     *
     * @param User $user
     * @return array
     */
    public function retryVerification(User $user): array
    {
        $previousVerification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'rejected')
            ->latest()
            ->first();

        if (!$previousVerification) {
            return [
                'success' => false,
                'error' => 'No previous rejected verification found.',
            ];
        }

        if (!$previousVerification->canRetry()) {
            return [
                'success' => false,
                'error' => 'Maximum verification attempts exceeded.',
            ];
        }

        return $this->initiateVerification($user, $previousVerification->verification_level);
    }

    /**
     * Admin: Approve verification manually.
     *
     * @param IdentityVerification $verification
     * @param User $admin
     * @param string|null $notes
     * @return array
     */
    public function approveManually(IdentityVerification $verification, User $admin, ?string $notes = null): array
    {
        if (!$verification->requiresManualReview()) {
            return [
                'success' => false,
                'error' => 'Verification is not in manual review status.',
            ];
        }

        try {
            $verification->approve($admin->id, $notes);
            $this->onVerificationApproved($verification);

            return [
                'success' => true,
                'message' => 'Verification approved successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to approve verification manually', [
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
     * Admin: Reject verification manually.
     *
     * @param IdentityVerification $verification
     * @param User $admin
     * @param string $reason
     * @param array $details
     * @return array
     */
    public function rejectManually(
        IdentityVerification $verification,
        User $admin,
        string $reason,
        array $details = []
    ): array {
        if (!$verification->requiresManualReview()) {
            return [
                'success' => false,
                'error' => 'Verification is not in manual review status.',
            ];
        }

        try {
            $verification->reject($reason, $details, $admin->id);
            $this->onVerificationRejected($verification);

            return [
                'success' => true,
                'message' => 'Verification rejected.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reject verification manually', [
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

    /**
     * Calculate confidence score from verification details.
     *
     * @param array $details
     * @return float|null
     */
    protected function calculateConfidenceScore(array $details): ?float
    {
        $scores = [];

        // Extract various confidence scores
        if (isset($details['breakdown'])) {
            foreach ($details['breakdown'] as $check) {
                if (isset($check['result'])) {
                    $scores[] = $check['result'] === 'clear' ? 1.0 : ($check['result'] === 'consider' ? 0.5 : 0.0);
                }
            }
        }

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 4);
    }

    /**
     * Determine rejection reason from details.
     *
     * @param array $details
     * @return string
     */
    protected function determineRejectionReason(array $details): string
    {
        $breakdown = $details['breakdown'] ?? [];

        foreach ($breakdown as $name => $result) {
            if (isset($result['result']) && $result['result'] !== 'clear') {
                return ucfirst(str_replace('_', ' ', $name)) . ' check failed.';
            }
        }

        return 'Verification failed due to document quality or authenticity issues.';
    }

    /**
     * Normalize score to 0-1 range.
     *
     * @param mixed $score
     * @return float|null
     */
    protected function normalizeScore($score): ?float
    {
        if ($score === null) {
            return null;
        }

        if (is_string($score)) {
            return match ($score) {
                'clear', 'match' => 1.0,
                'consider' => 0.5,
                default => 0.0,
            };
        }

        if (is_numeric($score)) {
            // If score is percentage (0-100), normalize to 0-1
            return $score > 1 ? $score / 100 : (float) $score;
        }

        return null;
    }

    /**
     * Convert country name/code to ISO 3166-1 alpha-3.
     *
     * @param string|null $country
     * @return string
     */
    protected function convertCountryToIso3(?string $country): string
    {
        $mapping = [
            'US' => 'USA', 'USA' => 'USA', 'United States' => 'USA',
            'GB' => 'GBR', 'UK' => 'GBR', 'United Kingdom' => 'GBR',
            'CA' => 'CAN', 'Canada' => 'CAN',
            'AU' => 'AUS', 'Australia' => 'AUS',
            'DE' => 'DEU', 'Germany' => 'DEU',
            'FR' => 'FRA', 'France' => 'FRA',
            'ES' => 'ESP', 'Spain' => 'ESP',
            'IT' => 'ITA', 'Italy' => 'ITA',
            'NL' => 'NLD', 'Netherlands' => 'NLD',
            'BE' => 'BEL', 'Belgium' => 'BEL',
            'IN' => 'IND', 'India' => 'IND',
            'BR' => 'BRA', 'Brazil' => 'BRA',
            'MX' => 'MEX', 'Mexico' => 'MEX',
            'ZA' => 'ZAF', 'South Africa' => 'ZAF',
            'NG' => 'NGA', 'Nigeria' => 'NGA',
            'KE' => 'KEN', 'Kenya' => 'KEN',
        ];

        return $mapping[$country ?? 'US'] ?? 'USA';
    }

    /**
     * Verify webhook signature from Onfido.
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (!$this->webhookSecret) {
            // In development, skip signature verification
            return app()->environment('local', 'development');
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
