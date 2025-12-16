<?php

namespace App\Services;

use App\Models\RightToWorkVerification;
use App\Models\RTWDocument;
use App\Models\User;
use App\Notifications\RTWDocumentsRequestedNotification;
use App\Notifications\RTWVerifiedNotification;
use App\Notifications\RTWExpiryReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * STAFF-REG-005: Right-to-Work Verification Service
 *
 * Handles all RTW verification logic including:
 * - Jurisdiction-specific requirements
 * - Document validation and combination checking
 * - Online verification integrations (UK share code, AU VEVO)
 * - Expiry tracking and reminders
 */
class RightToWorkService
{
    /**
     * Jurisdiction configuration with document requirements.
     */
    protected array $jurisdictionConfig = [
        'US' => [
            'name' => 'United States',
            'verification_type' => 'i9',
            'document_combinations' => [
                'list_a' => [
                    'description' => 'One document from List A (Identity + Work Authorization)',
                    'required_count' => 1,
                    'documents' => [
                        'us_passport', 'us_passport_card', 'permanent_resident_card',
                        'employment_auth_doc', 'foreign_passport_i94'
                    ],
                ],
                'list_b_c' => [
                    'description' => 'One document from List B (Identity) AND one from List C (Work Authorization)',
                    'list_b' => [
                        'required_count' => 1,
                        'documents' => [
                            'drivers_license', 'state_id', 'school_id_photo',
                            'voter_registration', 'military_id'
                        ],
                    ],
                    'list_c' => [
                        'required_count' => 1,
                        'documents' => [
                            'social_security_card', 'birth_certificate', 'native_american_tribal_doc'
                        ],
                    ],
                ],
            ],
            'online_verification' => false,
            'retention_years' => 3, // After employment ends or 1 year, whichever is later
            'expiry_check' => true,
        ],
        'UK' => [
            'name' => 'United Kingdom',
            'verification_type' => 'rtw_check',
            'document_options' => [
                [
                    'description' => 'UK or Irish Passport',
                    'documents' => ['uk_passport', 'irish_passport'],
                ],
                [
                    'description' => 'Biometric Residence Permit',
                    'documents' => ['brp'],
                ],
                [
                    'description' => 'Share Code (online verification)',
                    'documents' => ['share_code'],
                    'online_verification' => true,
                ],
                [
                    'description' => 'Settled or Pre-Settled Status',
                    'documents' => ['settled_status'],
                    'online_verification' => true,
                ],
            ],
            'online_verification' => true,
            'online_verification_url' => 'https://www.gov.uk/view-right-to-work',
            'retention_years' => 2, // 2 years after employment ends
            'expiry_check' => true,
        ],
        'EU' => [
            'name' => 'European Union',
            'verification_type' => 'work_permit',
            'document_options' => [
                [
                    'description' => 'EU/EEA Passport or National ID',
                    'documents' => ['eu_passport', 'national_id'],
                    'for_eu_citizens' => true,
                ],
                [
                    'description' => 'Work Permit + Passport (Non-EU)',
                    'documents' => ['work_permit', 'passport'],
                    'for_non_eu' => true,
                ],
                [
                    'description' => 'EU Blue Card',
                    'documents' => ['blue_card'],
                ],
            ],
            'online_verification' => false,
            'retention_years' => 5, // Varies by country
            'expiry_check' => true,
        ],
        'AU' => [
            'name' => 'Australia',
            'verification_type' => 'vevo',
            'document_options' => [
                [
                    'description' => 'Australian Passport',
                    'documents' => ['au_passport'],
                ],
                [
                    'description' => 'Visa Grant Notice + Passport (VEVO check)',
                    'documents' => ['visa_grant_notice', 'foreign_passport_visa'],
                    'online_verification' => true,
                ],
                [
                    'description' => 'ImmiCard',
                    'documents' => ['immicard'],
                ],
            ],
            'online_verification' => true,
            'online_verification_url' => 'https://immi.homeaffairs.gov.au/visas/already-have-a-visa/check-visa-details-and-conditions/check-conditions-online',
            'retention_years' => 7, // ATO requirement
            'expiry_check' => true,
        ],
        'UAE' => [
            'name' => 'United Arab Emirates',
            'verification_type' => 'emirates_id',
            'document_options' => [
                [
                    'description' => 'Emirates ID + Work Permit',
                    'documents' => ['emirates_id', 'work_permit'],
                ],
                [
                    'description' => 'Residence Visa + Passport',
                    'documents' => ['residence_visa', 'passport'],
                ],
            ],
            'online_verification' => false,
            'retention_years' => 5,
            'expiry_check' => true,
        ],
        'SG' => [
            'name' => 'Singapore',
            'verification_type' => 'employment_pass',
            'document_options' => [
                [
                    'description' => 'NRIC (Citizens/PRs)',
                    'documents' => ['nric'],
                ],
                [
                    'description' => 'Employment Pass',
                    'documents' => ['employment_pass'],
                ],
                [
                    'description' => 'S Pass',
                    'documents' => ['s_pass'],
                ],
                [
                    'description' => 'Work Permit',
                    'documents' => ['work_permit'],
                ],
            ],
            'online_verification' => true, // MOM verification
            'online_verification_url' => 'https://www.mom.gov.sg/eservices/services/wp-online',
            'retention_years' => 5,
            'expiry_check' => true,
        ],
    ];

    /**
     * Get RTW requirements for a jurisdiction.
     */
    public function getRTWRequirements(string $jurisdiction): array
    {
        $config = $this->jurisdictionConfig[$jurisdiction] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Unsupported jurisdiction: {$jurisdiction}");
        }

        return [
            'jurisdiction' => $jurisdiction,
            'jurisdiction_name' => $config['name'],
            'verification_type' => $config['verification_type'],
            'document_options' => $config['document_options'] ?? null,
            'document_combinations' => $config['document_combinations'] ?? null,
            'online_verification_available' => $config['online_verification'],
            'online_verification_url' => $config['online_verification_url'] ?? null,
            'retention_years' => $config['retention_years'],
            'document_types' => RTWDocument::DOCUMENT_TYPES[$jurisdiction] ?? [],
        ];
    }

    /**
     * Initiate RTW verification for a user.
     */
    public function initiateVerification(User $user, string $jurisdiction): RightToWorkVerification
    {
        $config = $this->jurisdictionConfig[$jurisdiction] ?? null;

        if (!$config) {
            throw new \InvalidArgumentException("Unsupported jurisdiction: {$jurisdiction}");
        }

        // Check for existing pending verification
        $existing = RightToWorkVerification::where('user_id', $user->id)
            ->where('jurisdiction', $jurisdiction)
            ->whereIn('status', [
                RightToWorkVerification::STATUS_PENDING,
                RightToWorkVerification::STATUS_DOCUMENTS_SUBMITTED,
                RightToWorkVerification::STATUS_UNDER_REVIEW,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        $verification = RightToWorkVerification::create([
            'user_id' => $user->id,
            'jurisdiction' => $jurisdiction,
            'verification_type' => $config['verification_type'],
            'status' => RightToWorkVerification::STATUS_PENDING,
            'audit_log' => [[
                'action' => 'initiated',
                'timestamp' => now()->toIso8601String(),
                'user_id' => auth()->id(),
            ]],
        ]);

        // Send notification with document requirements
        try {
            $user->notify(new RTWDocumentsRequestedNotification($verification));
        } catch (\Exception $e) {
            Log::warning("Failed to send RTW documents requested notification", [
                'user_id' => $user->id,
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("RTW verification initiated", [
            'user_id' => $user->id,
            'jurisdiction' => $jurisdiction,
            'verification_id' => $verification->id,
        ]);

        return $verification;
    }

    /**
     * Upload and store a document for RTW verification.
     */
    public function uploadDocument(
        RightToWorkVerification $verification,
        UploadedFile $file,
        string $documentType,
        array $metadata = []
    ): RTWDocument {
        // Validate document type for jurisdiction
        $validTypes = array_keys(RTWDocument::DOCUMENT_TYPES[$verification->jurisdiction] ?? []);
        if (!in_array($documentType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid document type '{$documentType}' for jurisdiction {$verification->jurisdiction}");
        }

        // Get document list (for US I-9)
        $documentList = RTWDocument::DOCUMENT_TYPES[$verification->jurisdiction][$documentType]['list'] ?? null;

        // Store file securely
        $filePath = $this->storeDocumentFile($file, $verification);
        $fileHash = hash_file('sha256', $file->getPathname());

        $document = RTWDocument::create([
            'rtw_verification_id' => $verification->id,
            'user_id' => $verification->user_id,
            'document_type' => $documentType,
            'document_list' => $documentList,
            'issuing_country' => $metadata['issuing_country'] ?? null,
            'issue_date' => $metadata['issue_date'] ?? null,
            'expiry_date' => $metadata['expiry_date'] ?? null,
            'file_hash' => $fileHash,
            'file_mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => RTWDocument::STATUS_PENDING,
            'upload_ip' => request()->ip(),
            'upload_user_agent' => request()->userAgent(),
        ]);

        // Set encrypted fields
        $document->file_path = $filePath;
        if (!empty($metadata['document_number'])) {
            $document->document_number = $metadata['document_number'];
        }
        if (!empty($metadata['issuing_authority'])) {
            $document->issuing_authority = $metadata['issuing_authority'];
        }
        $document->save();

        // Update verification status
        if ($verification->status === RightToWorkVerification::STATUS_PENDING) {
            $verification->update(['status' => RightToWorkVerification::STATUS_DOCUMENTS_SUBMITTED]);
        }

        $verification->addAuditLog('document_uploaded', [
            'document_id' => $document->id,
            'document_type' => $documentType,
        ]);

        Log::info("RTW document uploaded", [
            'verification_id' => $verification->id,
            'document_id' => $document->id,
            'document_type' => $documentType,
        ]);

        return $document;
    }

    /**
     * Store document file securely.
     */
    protected function storeDocumentFile(UploadedFile $file, RightToWorkVerification $verification): string
    {
        $disk = config('rtw.storage_disk', 's3');
        $path = "rtw-documents/{$verification->user_id}/{$verification->id}";
        $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Store with private visibility
        Storage::disk($disk)->putFileAs($path, $file, $filename, 'private');

        return "{$path}/{$filename}";
    }

    /**
     * Validate document combination for I-9 (US).
     */
    public function validateDocumentCombination(RightToWorkVerification $verification): array
    {
        if ($verification->jurisdiction !== 'US') {
            return $this->validateNonUSDocuments($verification);
        }

        $documents = $verification->documents()->where('status', 'verified')->get();

        // Check List A
        $listADocs = $documents->where('document_list', RTWDocument::LIST_A);
        if ($listADocs->count() >= 1) {
            return [
                'valid' => true,
                'combination' => 'list_a',
                'message' => 'Valid List A document provided',
            ];
        }

        // Check List B + List C
        $listBDocs = $documents->where('document_list', RTWDocument::LIST_B);
        $listCDocs = $documents->where('document_list', RTWDocument::LIST_C);

        if ($listBDocs->count() >= 1 && $listCDocs->count() >= 1) {
            return [
                'valid' => true,
                'combination' => 'list_b_c',
                'message' => 'Valid List B and List C documents provided',
            ];
        }

        // Determine what's missing
        $missing = [];
        if ($listADocs->count() === 0 && $listBDocs->count() === 0) {
            $missing[] = 'Identity document (List A or List B)';
        }
        if ($listADocs->count() === 0 && $listCDocs->count() === 0) {
            $missing[] = 'Work authorization document (List A or List C)';
        }

        return [
            'valid' => false,
            'combination' => null,
            'message' => 'Missing required documents',
            'missing' => $missing,
        ];
    }

    /**
     * Validate documents for non-US jurisdictions.
     */
    protected function validateNonUSDocuments(RightToWorkVerification $verification): array
    {
        $documents = $verification->documents()->where('status', 'verified')->get();
        $config = $this->jurisdictionConfig[$verification->jurisdiction] ?? null;

        if (!$config || empty($config['document_options'])) {
            return ['valid' => $documents->count() > 0, 'message' => 'Documents provided'];
        }

        foreach ($config['document_options'] as $option) {
            $requiredDocs = $option['documents'];
            $hasAll = collect($requiredDocs)->every(function ($docType) use ($documents) {
                return $documents->contains('document_type', $docType);
            });

            if ($hasAll) {
                return [
                    'valid' => true,
                    'combination' => implode('_', $requiredDocs),
                    'message' => $option['description'],
                ];
            }
        }

        return [
            'valid' => false,
            'combination' => null,
            'message' => 'Valid document combination not found',
            'options' => collect($config['document_options'])->pluck('description')->toArray(),
        ];
    }

    /**
     * Verify a document (manual review by admin).
     */
    public function verifyDocument(RTWDocument $document, ?int $verifierId = null, ?string $notes = null): void
    {
        DB::beginTransaction();

        try {
            $document->markVerified($verifierId, $notes);

            // Check if all required documents are now verified
            $verification = $document->verification;
            $combinationResult = $this->validateDocumentCombination($verification);

            if ($combinationResult['valid']) {
                $verification->update([
                    'document_combination' => $combinationResult['combination'],
                    'status' => RightToWorkVerification::STATUS_UNDER_REVIEW,
                ]);
            }

            DB::commit();

            Log::info("RTW document verified", [
                'document_id' => $document->id,
                'verification_id' => $verification->id,
                'verified_by' => $verifierId ?? auth()->id(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete RTW verification (all documents verified, combination valid).
     */
    public function completeVerification(RightToWorkVerification $verification, ?int $verifierId = null): void
    {
        $combinationResult = $this->validateDocumentCombination($verification);

        if (!$combinationResult['valid']) {
            throw new \InvalidArgumentException("Cannot complete verification: " . $combinationResult['message']);
        }

        DB::beginTransaction();

        try {
            // Calculate expiry based on document expiries
            $expiryDate = $this->calculateRTWExpiry($verification);

            // Calculate retention date
            $retentionYears = $this->jurisdictionConfig[$verification->jurisdiction]['retention_years'] ?? 3;
            $retentionDate = now()->addYears($retentionYears);

            $verification->update([
                'status' => RightToWorkVerification::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $verifierId ?? auth()->id(),
                'verification_method' => 'manual',
                'document_combination' => $combinationResult['combination'],
                'expires_at' => $expiryDate,
                'retention_expires_at' => $retentionDate,
            ]);

            $verification->addAuditLog('verified', [
                'verified_by' => $verifierId ?? auth()->id(),
                'combination' => $combinationResult['combination'],
                'expires_at' => $expiryDate?->toDateString(),
            ]);

            // Send notification
            try {
                $verification->user->notify(new RTWVerifiedNotification($verification));
            } catch (\Exception $e) {
                Log::warning("Failed to send RTW verified notification", [
                    'verification_id' => $verification->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Schedule expiry reminders if applicable
            if ($expiryDate) {
                $this->scheduleExpiryReminders($verification);
            }

            DB::commit();

            Log::info("RTW verification completed", [
                'verification_id' => $verification->id,
                'user_id' => $verification->user_id,
                'expires_at' => $expiryDate?->toDateString(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate RTW expiry date based on document expiries.
     */
    public function calculateRTWExpiry(RightToWorkVerification $verification): ?Carbon
    {
        $documents = $verification->documents()
            ->where('status', 'verified')
            ->whereNotNull('expiry_date')
            ->get();

        if ($documents->isEmpty()) {
            return null;
        }

        // RTW expires when the earliest document expires
        $earliestExpiry = $documents->min('expiry_date');

        // Also consider work permit expiry if set
        if ($verification->work_permit_expiry) {
            $earliestExpiry = min($earliestExpiry, $verification->work_permit_expiry);
        }

        return Carbon::parse($earliestExpiry);
    }

    /**
     * Schedule expiry reminder notifications.
     */
    public function scheduleExpiryReminders(RightToWorkVerification $verification): void
    {
        if (!$verification->expires_at) {
            return;
        }

        // Reminders at 30, 14, and 7 days before expiry
        // This is typically done via a scheduled job that checks daily
        // Here we just set up the tracking
        $verification->update([
            'expiry_reminder_level' => 0,
            'last_reminder_sent_at' => null,
        ]);

        Log::info("RTW expiry reminders scheduled", [
            'verification_id' => $verification->id,
            'expires_at' => $verification->expires_at->toDateString(),
        ]);
    }

    /**
     * Process expiring verifications and send reminders.
     * Called by scheduled job.
     */
    public function processExpiryReminders(): array
    {
        $results = [
            'reminders_sent' => 0,
            'expired' => 0,
        ];

        $reminderDays = [30, 14, 7];

        foreach ($reminderDays as $days) {
            $verifications = RightToWorkVerification::where('status', RightToWorkVerification::STATUS_VERIFIED)
                ->whereDate('expires_at', now()->addDays($days)->toDateString())
                ->where('expiry_reminder_level', '<', $this->getReminderLevel($days))
                ->get();

            foreach ($verifications as $verification) {
                try {
                    $verification->user->notify(new RTWExpiryReminderNotification($verification, $days));

                    $verification->update([
                        'expiry_reminder_level' => $this->getReminderLevel($days),
                        'last_reminder_sent_at' => now(),
                    ]);

                    $results['reminders_sent']++;
                } catch (\Exception $e) {
                    Log::error("Failed to send RTW expiry reminder", [
                        'verification_id' => $verification->id,
                        'days' => $days,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Mark expired verifications
        $expired = RightToWorkVerification::where('status', RightToWorkVerification::STATUS_VERIFIED)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $verification) {
            $verification->markExpired();
            $results['expired']++;
        }

        return $results;
    }

    /**
     * Get reminder level from days.
     */
    protected function getReminderLevel(int $days): int
    {
        return match (true) {
            $days >= 30 => 1,
            $days >= 14 => 2,
            $days >= 7 => 3,
            default => 4,
        };
    }

    /**
     * Verify UK share code online.
     */
    public function verifyUKShareCode(
        RightToWorkVerification $verification,
        string $shareCode,
        string $dateOfBirth
    ): array {
        if ($verification->jurisdiction !== 'UK') {
            throw new \InvalidArgumentException("Share code verification only available for UK");
        }

        // In production, this would call the actual UK Government API
        // For now, we simulate the verification
        $apiKey = config('rtw.uk_share_code_api_key');

        if (!$apiKey) {
            Log::warning("UK Share Code API not configured, simulating verification");

            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Verification simulated (API not configured)',
            ];
        }

        try {
            // Make API call to UK Government service
            // https://www.gov.uk/view-right-to-work
            $response = $this->callUKShareCodeAPI($shareCode, $dateOfBirth);

            if ($response['valid']) {
                $verification->update([
                    'online_verification_code' => $shareCode,
                    'online_verification_reference' => $response['reference'] ?? null,
                    'online_verified_at' => now(),
                    'has_work_restrictions' => $response['has_restrictions'] ?? false,
                    'work_restrictions' => $response['restrictions'] ?? null,
                ]);

                return [
                    'success' => true,
                    'status' => $response['status'],
                    'work_allowed' => $response['work_allowed'],
                    'restrictions' => $response['restrictions'] ?? null,
                    'expires_at' => $response['expires_at'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Verification failed',
            ];
        } catch (\Exception $e) {
            Log::error("UK Share Code verification failed", [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Verification service unavailable',
            ];
        }
    }

    /**
     * Verify Australian VEVO.
     */
    public function verifyAustralianVEVO(
        RightToWorkVerification $verification,
        string $passportNumber,
        string $countryOfPassport,
        string $dateOfBirth
    ): array {
        if ($verification->jurisdiction !== 'AU') {
            throw new \InvalidArgumentException("VEVO verification only available for Australia");
        }

        $apiKey = config('rtw.au_vevo_api_key');

        if (!$apiKey) {
            Log::warning("Australian VEVO API not configured, simulating verification");

            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Verification simulated (API not configured)',
            ];
        }

        try {
            // Make API call to Australian Immigration
            $response = $this->callVEVOAPI($passportNumber, $countryOfPassport, $dateOfBirth);

            if ($response['valid']) {
                $verification->update([
                    'online_verification_reference' => $response['reference'] ?? null,
                    'online_verified_at' => now(),
                    'has_work_restrictions' => $response['has_restrictions'] ?? false,
                    'work_restrictions' => $response['restrictions'] ?? null,
                    'work_permit_expiry' => $response['visa_expiry'] ?? null,
                ]);

                return [
                    'success' => true,
                    'visa_status' => $response['visa_status'],
                    'work_entitlement' => $response['work_entitlement'],
                    'visa_expiry' => $response['visa_expiry'] ?? null,
                    'conditions' => $response['conditions'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Verification failed',
            ];
        } catch (\Exception $e) {
            Log::error("Australian VEVO verification failed", [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Verification service unavailable',
            ];
        }
    }

    /**
     * Call UK Share Code API (placeholder for actual implementation).
     */
    protected function callUKShareCodeAPI(string $shareCode, string $dateOfBirth): array
    {
        // Implementation would use GuzzleHttp to call the UK Government API
        // This is a placeholder
        return [
            'valid' => true,
            'status' => 'verified',
            'work_allowed' => true,
            'has_restrictions' => false,
        ];
    }

    /**
     * Call Australian VEVO API (placeholder for actual implementation).
     */
    protected function callVEVOAPI(string $passportNumber, string $countryOfPassport, string $dateOfBirth): array
    {
        // Implementation would use GuzzleHttp to call the VEVO API
        // This is a placeholder
        return [
            'valid' => true,
            'visa_status' => 'valid',
            'work_entitlement' => 'unlimited',
            'has_restrictions' => false,
        ];
    }

    /**
     * Get verification status for a user.
     */
    public function getVerificationStatus(User $user, ?string $jurisdiction = null): array
    {
        $query = RightToWorkVerification::where('user_id', $user->id);

        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }

        $verifications = $query->with('documents')->get();

        return [
            'has_active_verification' => $verifications->contains(fn($v) => $v->isActive()),
            'verifications' => $verifications->map(function ($v) {
                return [
                    'id' => $v->id,
                    'jurisdiction' => $v->jurisdiction,
                    'jurisdiction_name' => $v->jurisdiction_name,
                    'status' => $v->status,
                    'verified_at' => $v->verified_at?->toDateString(),
                    'expires_at' => $v->expires_at?->toDateString(),
                    'days_until_expiry' => $v->days_until_expiry,
                    'is_active' => $v->isActive(),
                    'is_expiring_soon' => $v->isExpiringSoon(),
                    'document_count' => $v->documents->count(),
                    'verified_document_count' => $v->verified_document_count,
                ];
            }),
        ];
    }

    /**
     * Check if user has valid RTW for a jurisdiction.
     */
    public function hasValidRTW(User $user, string $jurisdiction): bool
    {
        return RightToWorkVerification::where('user_id', $user->id)
            ->where('jurisdiction', $jurisdiction)
            ->active()
            ->exists();
    }

    /**
     * Get all supported jurisdictions.
     */
    public function getSupportedJurisdictions(): array
    {
        return collect($this->jurisdictionConfig)->map(function ($config, $code) {
            return [
                'code' => $code,
                'name' => $config['name'],
                'verification_type' => $config['verification_type'],
                'online_verification' => $config['online_verification'],
            ];
        })->values()->toArray();
    }
}
