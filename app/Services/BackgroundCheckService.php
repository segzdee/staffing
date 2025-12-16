<?php

namespace App\Services;

use App\Models\AdjudicationCase;
use App\Models\BackgroundCheck;
use App\Models\BackgroundCheckConsent;
use App\Models\User;
use App\Notifications\BackgroundCheckInitiatedNotification;
use App\Notifications\BackgroundCheckClearNotification;
use App\Notifications\BackgroundCheckReviewRequiredNotification;
use App\Notifications\PreAdverseActionNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * STAFF-REG-006: Background Check Service
 *
 * Handles background check operations including:
 * - Checkr integration (US)
 * - DBS integration (UK)
 * - Consent management
 * - Adjudication workflow
 * - FCRA-compliant adverse action process
 */
class BackgroundCheckService
{
    /**
     * Check provider configurations by jurisdiction.
     */
    protected array $providerConfig = [
        'US' => [
            'provider' => 'checkr',
            'check_types' => [
                'basic' => [
                    'name' => 'Basic Check',
                    'package' => 'tasker_standard',
                    'components' => ['ssn_trace', 'national_criminal'],
                    'price_cents' => 3500,
                ],
                'standard' => [
                    'name' => 'Standard Check',
                    'package' => 'tasker_plus',
                    'components' => ['ssn_trace', 'national_criminal', 'sex_offender'],
                    'price_cents' => 5000,
                ],
                'professional' => [
                    'name' => 'Professional Check',
                    'package' => 'driver_standard',
                    'components' => ['ssn_trace', 'national_criminal', 'sex_offender', 'county_criminal', 'motor_vehicle'],
                    'price_cents' => 7500,
                ],
                'comprehensive' => [
                    'name' => 'Comprehensive Check',
                    'package' => 'driver_pro',
                    'components' => ['ssn_trace', 'national_criminal', 'sex_offender', 'county_criminal', 'motor_vehicle', 'education', 'employment'],
                    'price_cents' => 12500,
                ],
            ],
            'expiry_days' => 365,
        ],
        'UK' => [
            'provider' => 'dbs',
            'check_types' => [
                'dbs_basic' => [
                    'name' => 'DBS Basic',
                    'package' => 'basic',
                    'components' => ['unspent_convictions'],
                    'price_cents' => 2500, // GBP converted
                ],
                'dbs_standard' => [
                    'name' => 'DBS Standard',
                    'package' => 'standard',
                    'components' => ['unspent_convictions', 'spent_convictions', 'cautions'],
                    'price_cents' => 4000,
                ],
                'dbs_enhanced' => [
                    'name' => 'DBS Enhanced',
                    'package' => 'enhanced',
                    'components' => ['unspent_convictions', 'spent_convictions', 'cautions', 'police_intelligence'],
                    'price_cents' => 5500,
                ],
                'dbs_enhanced_barred' => [
                    'name' => 'DBS Enhanced with Barred List',
                    'package' => 'enhanced_barred',
                    'components' => ['unspent_convictions', 'spent_convictions', 'cautions', 'police_intelligence', 'barred_list'],
                    'price_cents' => 6500,
                ],
            ],
            'expiry_days' => 1095, // 3 years
        ],
        'AU' => [
            'provider' => 'police_clearance',
            'check_types' => [
                'police_check' => [
                    'name' => 'National Police Check',
                    'package' => 'national',
                    'components' => ['national_criminal'],
                    'price_cents' => 4200, // AUD converted
                ],
                'working_with_children' => [
                    'name' => 'Working with Children Check',
                    'package' => 'wwcc',
                    'components' => ['national_criminal', 'child_protection'],
                    'price_cents' => 8000,
                ],
            ],
            'expiry_days' => 365,
        ],
        'DEFAULT' => [
            'provider' => 'police_clearance',
            'check_types' => [
                'standard' => [
                    'name' => 'Police Clearance',
                    'package' => 'standard',
                    'components' => ['criminal_record'],
                    'price_cents' => 5000,
                ],
            ],
            'expiry_days' => 365,
        ],
    ];

    /**
     * Checkr API client.
     */
    protected ?object $checkrClient = null;

    /**
     * Get check requirements for a jurisdiction.
     */
    public function getCheckRequirements(string $jurisdiction): array
    {
        $config = $this->providerConfig[$jurisdiction] ?? $this->providerConfig['DEFAULT'];

        return [
            'jurisdiction' => $jurisdiction,
            'provider' => $config['provider'],
            'check_types' => collect($config['check_types'])->map(function ($type, $key) {
                return [
                    'id' => $key,
                    'name' => $type['name'],
                    'components' => $type['components'],
                    'price' => $type['price_cents'] / 100,
                ];
            })->values()->toArray(),
            'required_consents' => BackgroundCheckConsent::REQUIRED_CONSENTS[$jurisdiction]
                ?? BackgroundCheckConsent::REQUIRED_CONSENTS['DEFAULT'],
            'expiry_days' => $config['expiry_days'],
        ];
    }

    /**
     * Initiate a background check.
     */
    public function initiateCheck(
        User $user,
        string $jurisdiction,
        string $checkType,
        ?int $billedToId = null
    ): BackgroundCheck {
        $config = $this->providerConfig[$jurisdiction] ?? $this->providerConfig['DEFAULT'];
        $typeConfig = $config['check_types'][$checkType] ?? null;

        if (!$typeConfig) {
            throw new \InvalidArgumentException("Invalid check type '{$checkType}' for jurisdiction {$jurisdiction}");
        }

        // Check for existing pending check
        $existing = BackgroundCheck::where('user_id', $user->id)
            ->where('jurisdiction', $jurisdiction)
            ->where('check_type', $checkType)
            ->whereIn('status', [
                BackgroundCheck::STATUS_PENDING_CONSENT,
                BackgroundCheck::STATUS_CONSENT_RECEIVED,
                BackgroundCheck::STATUS_SUBMITTED,
                BackgroundCheck::STATUS_PROCESSING,
            ])
            ->first();

        if ($existing) {
            return $existing;
        }

        $check = BackgroundCheck::create([
            'user_id' => $user->id,
            'jurisdiction' => $jurisdiction,
            'provider' => $config['provider'],
            'check_type' => $checkType,
            'check_components' => $typeConfig['components'],
            'status' => BackgroundCheck::STATUS_PENDING_CONSENT,
            'cost_cents' => $typeConfig['price_cents'],
            'cost_currency' => $this->getCurrencyForJurisdiction($jurisdiction),
            'billed_to' => $billedToId,
            'audit_log' => [[
                'action' => 'initiated',
                'timestamp' => now()->toIso8601String(),
                'user_id' => auth()->id(),
            ]],
        ]);

        // Create required consent records
        $requiredConsents = BackgroundCheckConsent::REQUIRED_CONSENTS[$jurisdiction]
            ?? BackgroundCheckConsent::REQUIRED_CONSENTS['DEFAULT'];

        foreach ($requiredConsents as $consentType) {
            BackgroundCheckConsent::create([
                'background_check_id' => $check->id,
                'user_id' => $user->id,
                'consent_type' => $consentType,
                'document_version' => config('background_check.consent_document_version', '1.0'),
            ]);
        }

        // Send notification
        try {
            $user->notify(new BackgroundCheckInitiatedNotification($check));
        } catch (\Exception $e) {
            Log::warning("Failed to send background check initiated notification", [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info("Background check initiated", [
            'check_id' => $check->id,
            'user_id' => $user->id,
            'jurisdiction' => $jurisdiction,
            'check_type' => $checkType,
        ]);

        return $check;
    }

    /**
     * Record consent for a background check.
     */
    public function recordConsent(
        BackgroundCheck $check,
        string $consentType,
        string $signatureType,
        ?string $signatureData = null,
        ?string $signatoryName = null
    ): BackgroundCheckConsent {
        $consent = $check->consents()
            ->where('consent_type', $consentType)
            ->firstOrFail();

        // Get the appropriate disclosure text
        $disclosureText = match ($consentType) {
            BackgroundCheckConsent::TYPE_FCRA_DISCLOSURE => BackgroundCheckConsent::getFCRADisclosureText(),
            BackgroundCheckConsent::TYPE_FCRA_AUTHORIZATION => BackgroundCheckConsent::getFCRAAuthorizationText(),
            default => null,
        };

        $consent->recordConsent($signatureType, $signatureData, $signatoryName, $disclosureText);

        // Check if all required consents are now received
        $allConsentsReceived = $check->consents()
            ->where('consented', false)
            ->doesntExist();

        if ($allConsentsReceived) {
            $check->markConsentReceived();
        }

        Log::info("Background check consent recorded", [
            'check_id' => $check->id,
            'consent_type' => $consentType,
            'all_consents_received' => $allConsentsReceived,
        ]);

        return $consent;
    }

    /**
     * Submit check to provider after consent.
     */
    public function submitToProvider(BackgroundCheck $check): array
    {
        if ($check->status !== BackgroundCheck::STATUS_CONSENT_RECEIVED) {
            throw new \InvalidArgumentException("Check must have consent received before submission");
        }

        $provider = $check->provider;

        return match ($provider) {
            'checkr' => $this->submitToCheckr($check),
            'dbs' => $this->submitToDBS($check),
            default => $this->submitGenericCheck($check),
        };
    }

    /**
     * Submit check to Checkr (US).
     */
    protected function submitToCheckr(BackgroundCheck $check): array
    {
        $apiKey = config('services.checkr.api_key');
        $baseUrl = config('services.checkr.base_url', 'https://api.checkr.com');

        if (!$apiKey) {
            Log::warning("Checkr API not configured, simulating submission");
            return $this->simulateProviderSubmission($check);
        }

        $user = $check->user;
        $profile = $user->workerProfile;

        try {
            // Step 1: Create candidate in Checkr
            $candidateResponse = Http::withBasicAuth($apiKey, '')
                ->post("{$baseUrl}/v1/candidates", [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $profile?->phone,
                    'dob' => $profile?->date_of_birth,
                    'ssn' => null, // Would need secure input
                    'zipcode' => $profile?->zip_code,
                ]);

            if (!$candidateResponse->successful()) {
                throw new \Exception("Failed to create Checkr candidate: " . $candidateResponse->body());
            }

            $candidateId = $candidateResponse->json('id');

            // Step 2: Create invitation (initiates the check)
            $config = $this->providerConfig['US']['check_types'][$check->check_type];
            $invitationResponse = Http::withBasicAuth($apiKey, '')
                ->post("{$baseUrl}/v1/invitations", [
                    'candidate_id' => $candidateId,
                    'package' => $config['package'],
                ]);

            if (!$invitationResponse->successful()) {
                throw new \Exception("Failed to create Checkr invitation: " . $invitationResponse->body());
            }

            $reportId = $invitationResponse->json('report_id');

            $check->markSubmitted($candidateId, $reportId);

            return [
                'success' => true,
                'candidate_id' => $candidateId,
                'report_id' => $reportId,
                'invitation_url' => $invitationResponse->json('invitation_url'),
            ];
        } catch (\Exception $e) {
            Log::error("Checkr submission failed", [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Submit check to DBS (UK).
     */
    protected function submitToDBS(BackgroundCheck $check): array
    {
        // DBS integration would go here
        // For now, simulate submission
        Log::warning("DBS integration not implemented, simulating submission");
        return $this->simulateProviderSubmission($check);
    }

    /**
     * Submit generic check (non-integrated jurisdictions).
     */
    protected function submitGenericCheck(BackgroundCheck $check): array
    {
        // For non-integrated jurisdictions, mark as submitted
        // and wait for manual document upload
        $check->update([
            'status' => BackgroundCheck::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Check submitted for manual processing',
            'requires_manual_upload' => true,
        ];
    }

    /**
     * Simulate provider submission for testing.
     */
    protected function simulateProviderSubmission(BackgroundCheck $check): array
    {
        $candidateId = 'sim_' . uniqid();
        $reportId = 'rep_' . uniqid();

        $check->markSubmitted($candidateId, $reportId);

        return [
            'success' => true,
            'simulated' => true,
            'candidate_id' => $candidateId,
            'report_id' => $reportId,
        ];
    }

    /**
     * Process webhook from Checkr.
     */
    public function processCheckrWebhook(array $payload): array
    {
        $type = $payload['type'] ?? null;
        $reportId = $payload['data']['object']['id'] ?? null;

        if (!$type || !$reportId) {
            return ['success' => false, 'error' => 'Invalid webhook payload'];
        }

        $check = BackgroundCheck::where('provider_report_id', $reportId)->first();

        if (!$check) {
            Log::warning("Checkr webhook received for unknown report", ['report_id' => $reportId]);
            return ['success' => false, 'error' => 'Report not found'];
        }

        return match ($type) {
            'report.created' => $this->handleReportCreated($check, $payload),
            'report.completed' => $this->handleReportCompleted($check, $payload),
            'report.upgraded' => $this->handleReportUpgraded($check, $payload),
            'report.suspended' => $this->handleReportSuspended($check, $payload),
            default => ['success' => true, 'message' => 'Event type not handled'],
        };
    }

    /**
     * Handle report created webhook.
     */
    protected function handleReportCreated(BackgroundCheck $check, array $payload): array
    {
        $check->updateFromWebhook(BackgroundCheck::STATUS_PROCESSING);

        return ['success' => true, 'action' => 'status_updated'];
    }

    /**
     * Handle report completed webhook.
     */
    protected function handleReportCompleted(BackgroundCheck $check, array $payload): array
    {
        $data = $payload['data']['object'] ?? [];
        $status = $data['status'] ?? 'complete';
        $result = $this->mapCheckrStatus($status);

        // Store encrypted result data
        $check->result_data = $data;

        if (isset($data['report_url'])) {
            $check->report_url = $data['report_url'];
        }

        $check->updateFromWebhook(
            $result === BackgroundCheck::RESULT_CONSIDER
                ? BackgroundCheck::STATUS_CONSIDER
                : BackgroundCheck::STATUS_COMPLETE,
            $result,
            $data
        );

        // Send appropriate notification
        $this->sendResultNotification($check);

        // Create adjudication case if needed
        if ($result === BackgroundCheck::RESULT_CONSIDER) {
            $this->createAdjudicationCase($check, $data);
        }

        return ['success' => true, 'result' => $result];
    }

    /**
     * Handle report upgraded webhook.
     */
    protected function handleReportUpgraded(BackgroundCheck $check, array $payload): array
    {
        // Check was upgraded, status may have changed
        return $this->handleReportCompleted($check, $payload);
    }

    /**
     * Handle report suspended webhook.
     */
    protected function handleReportSuspended(BackgroundCheck $check, array $payload): array
    {
        $check->updateFromWebhook(BackgroundCheck::STATUS_SUSPENDED);

        return ['success' => true, 'action' => 'suspended'];
    }

    /**
     * Map Checkr status to our result.
     */
    protected function mapCheckrStatus(string $status): string
    {
        return match ($status) {
            'clear' => BackgroundCheck::RESULT_CLEAR,
            'consider' => BackgroundCheck::RESULT_CONSIDER,
            'fail', 'adverse_action' => BackgroundCheck::RESULT_FAIL,
            default => BackgroundCheck::RESULT_PENDING,
        };
    }

    /**
     * Send notification based on result.
     */
    protected function sendResultNotification(BackgroundCheck $check): void
    {
        try {
            if ($check->result === BackgroundCheck::RESULT_CLEAR) {
                $check->user->notify(new BackgroundCheckClearNotification($check));
            } elseif ($check->result === BackgroundCheck::RESULT_CONSIDER) {
                $check->user->notify(new BackgroundCheckReviewRequiredNotification($check));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send background check result notification", [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create adjudication case for "consider" results.
     */
    public function createAdjudicationCase(BackgroundCheck $check, ?array $reportData = null): AdjudicationCase
    {
        // Determine case type from report data
        $caseType = $this->determineCaseType($reportData);
        $severity = $this->determineSeverity($reportData);

        $case = AdjudicationCase::create([
            'background_check_id' => $check->id,
            'user_id' => $check->user_id,
            'case_type' => $caseType,
            'status' => AdjudicationCase::STATUS_OPEN,
            'severity' => $severity,
            'record_details' => $this->extractNonPIIDetails($reportData),
        ]);

        // Set encrypted findings
        if ($reportData) {
            $case->findings = $this->formatFindings($reportData);
            $case->save();
        }

        $check->update(['adjudication_status' => BackgroundCheck::ADJ_PENDING]);

        Log::info("Adjudication case created", [
            'case_id' => $case->id,
            'case_number' => $case->case_number,
            'check_id' => $check->id,
        ]);

        return $case;
    }

    /**
     * Determine case type from report data.
     */
    protected function determineCaseType(?array $reportData): string
    {
        if (!$reportData) {
            return AdjudicationCase::TYPE_OTHER;
        }

        // Check for specific flags in Checkr response
        if (!empty($reportData['criminal_records'])) {
            return AdjudicationCase::TYPE_CRIMINAL_RECORD;
        }

        if (!empty($reportData['ssn_trace_alerts'])) {
            return AdjudicationCase::TYPE_IDENTITY_MISMATCH;
        }

        if (!empty($reportData['motor_vehicle_alerts'])) {
            return AdjudicationCase::TYPE_MOTOR_VEHICLE;
        }

        if (!empty($reportData['sex_offender_alerts'])) {
            return AdjudicationCase::TYPE_SEX_OFFENDER;
        }

        return AdjudicationCase::TYPE_OTHER;
    }

    /**
     * Determine severity from report data.
     */
    protected function determineSeverity(?array $reportData): string
    {
        if (!$reportData) {
            return AdjudicationCase::SEVERITY_MEDIUM;
        }

        // Sex offender = critical
        if (!empty($reportData['sex_offender_alerts'])) {
            return AdjudicationCase::SEVERITY_CRITICAL;
        }

        // Felony = high
        if ($this->hasFelony($reportData)) {
            return AdjudicationCase::SEVERITY_HIGH;
        }

        // Misdemeanor = medium
        if ($this->hasMisdemeanor($reportData)) {
            return AdjudicationCase::SEVERITY_MEDIUM;
        }

        return AdjudicationCase::SEVERITY_LOW;
    }

    /**
     * Check for felony in report data.
     */
    protected function hasFelony(?array $reportData): bool
    {
        $records = $reportData['criminal_records'] ?? [];

        foreach ($records as $record) {
            if (stripos($record['charge_type'] ?? '', 'felony') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for misdemeanor in report data.
     */
    protected function hasMisdemeanor(?array $reportData): bool
    {
        $records = $reportData['criminal_records'] ?? [];

        foreach ($records as $record) {
            if (stripos($record['charge_type'] ?? '', 'misdemeanor') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract non-PII details for case record.
     */
    protected function extractNonPIIDetails(?array $reportData): array
    {
        if (!$reportData) {
            return [];
        }

        return [
            'record_count' => count($reportData['criminal_records'] ?? []),
            'charge_types' => collect($reportData['criminal_records'] ?? [])
                ->pluck('charge_type')
                ->unique()
                ->values()
                ->toArray(),
            'jurisdictions' => collect($reportData['criminal_records'] ?? [])
                ->pluck('jurisdiction')
                ->unique()
                ->values()
                ->toArray(),
            'date_range' => [
                'oldest' => collect($reportData['criminal_records'] ?? [])->min('date'),
                'newest' => collect($reportData['criminal_records'] ?? [])->max('date'),
            ],
        ];
    }

    /**
     * Format findings for encrypted storage.
     */
    protected function formatFindings(?array $reportData): string
    {
        if (!$reportData) {
            return 'No detailed findings available';
        }

        $findings = [];

        foreach ($reportData['criminal_records'] ?? [] as $record) {
            $findings[] = sprintf(
                "- %s: %s (%s) - %s",
                $record['date'] ?? 'Unknown date',
                $record['charge'] ?? 'Unknown charge',
                $record['charge_type'] ?? 'Unknown type',
                $record['disposition'] ?? 'Unknown disposition'
            );
        }

        return implode("\n", $findings) ?: 'No records found';
    }

    /**
     * Initiate pre-adverse action process (FCRA compliance).
     */
    public function initiatePreAdverseAction(BackgroundCheck $check): void
    {
        if ($check->jurisdiction !== 'US') {
            throw new \InvalidArgumentException("Pre-adverse action only applicable for US checks");
        }

        DB::beginTransaction();

        try {
            $check->initiatePreAdverseAction();

            // Send pre-adverse action notice
            $check->user->notify(new PreAdverseActionNotification($check));

            DB::commit();

            Log::info("Pre-adverse action initiated", [
                'check_id' => $check->id,
                'deadline' => $check->pre_adverse_action_deadline->toDateString(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send adverse action notice after waiting period.
     */
    public function sendAdverseActionNotice(BackgroundCheck $check): void
    {
        if (!$check->pre_adverse_action_sent_at) {
            throw new \InvalidArgumentException("Pre-adverse action must be sent first");
        }

        if (now()->lt($check->pre_adverse_action_deadline)) {
            throw new \InvalidArgumentException("Waiting period not yet complete");
        }

        $check->completeAdverseAction();

        Log::info("Adverse action completed", ['check_id' => $check->id]);
    }

    /**
     * Get check status for a user.
     */
    public function getCheckStatus(User $user, ?string $jurisdiction = null): array
    {
        $query = BackgroundCheck::where('user_id', $user->id);

        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }

        $checks = $query->with(['consents', 'adjudicationCases'])->get();

        return [
            'has_active_check' => $checks->contains(fn($c) =>
                $c->result === BackgroundCheck::RESULT_CLEAR ||
                ($c->result === BackgroundCheck::RESULT_CONSIDER &&
                 $c->adjudication_status === BackgroundCheck::ADJ_APPROVED)
            ),
            'checks' => $checks->map(function ($check) {
                return [
                    'id' => $check->id,
                    'jurisdiction' => $check->jurisdiction,
                    'provider' => $check->provider,
                    'check_type' => $check->check_type,
                    'check_type_name' => $check->check_type_name,
                    'status' => $check->status,
                    'status_name' => $check->status_name,
                    'result' => $check->result,
                    'adjudication_status' => $check->adjudication_status,
                    'submitted_at' => $check->submitted_at?->toDateTimeString(),
                    'completed_at' => $check->completed_at?->toDateTimeString(),
                    'expires_at' => $check->expires_at?->toDateTimeString(),
                    'is_expired' => $check->isExpired(),
                    'consent_status' => $this->getConsentStatus($check),
                ];
            }),
        ];
    }

    /**
     * Get consent status for a check.
     */
    protected function getConsentStatus(BackgroundCheck $check): array
    {
        $consents = $check->consents;

        return [
            'required' => $consents->count(),
            'received' => $consents->where('consented', true)->count(),
            'complete' => $consents->every(fn($c) => $c->consented),
            'consents' => $consents->map(fn($c) => [
                'type' => $c->consent_type,
                'type_name' => $c->consent_type_name,
                'consented' => $c->consented,
                'consented_at' => $c->consented_at?->toDateTimeString(),
            ]),
        ];
    }

    /**
     * Check if user has valid background check for jurisdiction.
     */
    public function hasValidCheck(User $user, string $jurisdiction, ?string $checkType = null): bool
    {
        $query = BackgroundCheck::where('user_id', $user->id)
            ->where('jurisdiction', $jurisdiction)
            ->active();

        if ($checkType) {
            $query->where('check_type', $checkType);
        }

        return $query->exists();
    }

    /**
     * Get currency for jurisdiction.
     */
    protected function getCurrencyForJurisdiction(string $jurisdiction): string
    {
        return match ($jurisdiction) {
            'US' => 'USD',
            'UK' => 'GBP',
            'AU' => 'AUD',
            'UAE' => 'AED',
            'SG' => 'SGD',
            default => 'USD',
        };
    }

    /**
     * Process expired checks.
     * Called by scheduled job.
     */
    public function processExpiredChecks(): array
    {
        $expired = BackgroundCheck::whereNotIn('status', [
                BackgroundCheck::STATUS_EXPIRED,
                BackgroundCheck::STATUS_CANCELLED,
            ])
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $check) {
            $check->update(['status' => BackgroundCheck::STATUS_EXPIRED]);
            $count++;
        }

        return ['expired_count' => $count];
    }

    /**
     * Get all supported check types for a jurisdiction.
     */
    public function getSupportedCheckTypes(string $jurisdiction): array
    {
        $config = $this->providerConfig[$jurisdiction] ?? $this->providerConfig['DEFAULT'];

        return collect($config['check_types'])->map(function ($type, $key) {
            return [
                'id' => $key,
                'name' => $type['name'],
                'components' => $type['components'],
                'price_cents' => $type['price_cents'],
            ];
        })->values()->toArray();
    }
}
