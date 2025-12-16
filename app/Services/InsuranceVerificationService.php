<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\InsuranceCarrier;
use App\Models\InsuranceCertificate;
use App\Models\InsuranceRequirement;
use App\Models\InsuranceVerification;
use App\Models\User;
use App\Notifications\Business\InsuranceExpiryReminderNotification;
use App\Notifications\Business\InsuranceExpiredNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Insurance Verification Service
 * BIZ-REG-005: Insurance & Compliance
 *
 * Handles insurance certificate verification, carrier validation, and compliance tracking
 */
class InsuranceVerificationService
{
    /**
     * Get insurance requirements for a jurisdiction and business.
     */
    public function getInsuranceRequirements(
        string $jurisdiction,
        ?string $businessType = null,
        ?string $industry = null,
        ?string $region = null
    ): array {
        $requirements = InsuranceRequirement::getRequirements(
            $jurisdiction,
            $businessType,
            $industry,
            $region
        );

        return [
            'jurisdiction' => $jurisdiction,
            'requirements' => $requirements->map(function ($req) {
                return [
                    'id' => $req->id,
                    'insurance_type' => $req->insurance_type,
                    'insurance_name' => $req->insurance_name,
                    'description' => $req->description,
                    'is_required' => $req->is_required,
                    'minimum_coverage' => $req->getMinimumCoverageFormatted(),
                    'minimum_coverage_cents' => $req->minimum_coverage_amount,
                    'minimum_per_occurrence' => $req->getMinimumPerOccurrenceFormatted(),
                    'minimum_aggregate' => $req->getMinimumAggregateFormatted(),
                    'additional_insured_required' => $req->additional_insured_required,
                    'waiver_of_subrogation_required' => $req->waiver_of_subrogation_required,
                ];
            })->values()->toArray(),
            'required_count' => $requirements->where('is_required', true)->count(),
        ];
    }

    /**
     * Initialize or get insurance verification for a business.
     */
    public function getOrCreateVerification(BusinessProfile $profile, string $jurisdiction): InsuranceVerification
    {
        $verification = InsuranceVerification::firstOrCreate(
            ['business_profile_id' => $profile->id],
            [
                'user_id' => $profile->user_id,
                'jurisdiction' => strtoupper($jurisdiction),
                'status' => InsuranceVerification::STATUS_PENDING,
            ]
        );

        // Update jurisdiction if changed
        if ($verification->jurisdiction !== strtoupper($jurisdiction)) {
            $verification->update(['jurisdiction' => strtoupper($jurisdiction)]);
        }

        return $verification;
    }

    /**
     * Submit insurance certificate.
     */
    public function submitInsurance(
        InsuranceVerification $verification,
        UploadedFile $file,
        array $insuranceData
    ): InsuranceCertificate {
        // Get requirement for this insurance type
        $requirement = InsuranceRequirement::active()
            ->forJurisdiction($verification->jurisdiction)
            ->ofType($insuranceData['insurance_type'])
            ->first();

        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $secureFilename = Str::uuid() . '.' . $extension;
        $storagePath = "insurance-certificates/{$verification->business_profile_id}/{$secureFilename}";

        // Calculate file hash
        $fileHash = InsuranceCertificate::calculateFileHash($file->getContent());

        // Store file
        $path = $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            config('filesystems.verification_disk', 's3')
        );

        DB::beginTransaction();
        try {
            // Soft delete existing certificate of same type if any
            InsuranceCertificate::where('insurance_verification_id', $verification->id)
                ->where('insurance_type', $insuranceData['insurance_type'])
                ->whereIn('status', [
                    InsuranceCertificate::STATUS_PENDING,
                    InsuranceCertificate::STATUS_VERIFIED,
                ])
                ->update(['deleted_at' => now()]);

            // Convert coverage amount to cents
            $coverageAmount = isset($insuranceData['coverage_amount'])
                ? (int)($insuranceData['coverage_amount'] * 100)
                : 0;

            $perOccurrence = isset($insuranceData['per_occurrence_limit'])
                ? (int)($insuranceData['per_occurrence_limit'] * 100)
                : null;

            $aggregate = isset($insuranceData['aggregate_limit'])
                ? (int)($insuranceData['aggregate_limit'] * 100)
                : null;

            $deductible = isset($insuranceData['deductible_amount'])
                ? (int)($insuranceData['deductible_amount'] * 100)
                : null;

            $certificate = InsuranceCertificate::create([
                'insurance_verification_id' => $verification->id,
                'business_profile_id' => $verification->business_profile_id,
                'requirement_id' => $requirement?->id,
                'insurance_type' => $insuranceData['insurance_type'],
                'policy_number' => $insuranceData['policy_number'] ?? null,
                'carrier_name' => $insuranceData['carrier_name'],
                'carrier_naic_code' => $insuranceData['carrier_naic_code'] ?? null,
                'named_insured' => $insuranceData['named_insured'],
                'insured_address' => $insuranceData['insured_address'] ?? null,
                'coverage_amount' => $coverageAmount,
                'coverage_currency' => $insuranceData['coverage_currency'] ?? 'USD',
                'per_occurrence_limit' => $perOccurrence,
                'aggregate_limit' => $aggregate,
                'deductible_amount' => $deductible,
                'coverage_details' => $insuranceData['coverage_details'] ?? null,
                'effective_date' => $insuranceData['effective_date'],
                'expiry_date' => $insuranceData['expiry_date'],
                'auto_renews' => $insuranceData['auto_renews'] ?? false,
                'has_additional_insured' => $insuranceData['has_additional_insured'] ?? false,
                'additional_insured_text' => $insuranceData['additional_insured_text'] ?? null,
                'has_waiver_of_subrogation' => $insuranceData['has_waiver_of_subrogation'] ?? false,
                'file_hash' => $fileHash,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'storage_provider' => config('filesystems.verification_disk', 's3'),
                'status' => InsuranceCertificate::STATUS_PENDING,
            ]);

            // Set encrypted path
            $certificate->setFilePath($path);
            $certificate->save();

            // Log access
            $certificate->recordAccess('upload', auth()->user());

            DB::commit();

            Log::info('Insurance certificate submitted', [
                'certificate_id' => $certificate->id,
                'verification_id' => $verification->id,
                'insurance_type' => $insuranceData['insurance_type'],
            ]);

            // Attempt carrier verification
            $this->verifyWithCarrier($certificate);

            // Validate coverage amount
            $certificate->validateCoverage();

            // Update compliance status
            $verification->updateComplianceStatus();

            return $certificate;

        } catch (\Exception $e) {
            DB::rollBack();
            Storage::disk(config('filesystems.verification_disk', 's3'))->delete($path);
            throw $e;
        }
    }

    /**
     * Verify insurance with carrier API.
     */
    public function verifyWithCarrier(InsuranceCertificate $certificate): array
    {
        $carrier = InsuranceCarrier::active()
            ->where(function ($q) use ($certificate) {
                $q->where('naic_code', $certificate->carrier_naic_code)
                  ->orWhere('name', 'like', "%{$certificate->carrier_name}%");
            })
            ->first();

        if (!$carrier || !$carrier->supportsApiVerification()) {
            return [
                'verified' => false,
                'method' => 'manual_required',
                'message' => 'Carrier does not support automated verification',
            ];
        }

        try {
            $result = $this->callCarrierApi($carrier, $certificate);

            $certificate->recordCarrierVerification(
                $result['verified'] ?? false,
                $result
            );

            if ($result['verified']) {
                // Update carrier info if returned
                $updates = [];
                if (isset($result['am_best_rating'])) {
                    $updates['carrier_am_best_rating'] = $result['am_best_rating'];
                }
                if (!empty($updates)) {
                    $certificate->update($updates);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Carrier verification failed', [
                'certificate_id' => $certificate->id,
                'carrier' => $carrier->name,
                'error' => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'error' => 'Carrier API unavailable',
            ];
        }
    }

    /**
     * Call carrier verification API.
     */
    protected function callCarrierApi(InsuranceCarrier $carrier, InsuranceCertificate $certificate): array
    {
        // This is a placeholder for actual carrier API integrations
        // Each carrier has different API requirements

        $apiType = $carrier->verification_api_type;

        return match ($apiType) {
            'rest' => $this->callRestApi($carrier, $certificate),
            'soap' => $this->callSoapApi($carrier, $certificate),
            default => ['verified' => false, 'error' => 'Unknown API type'],
        };
    }

    /**
     * Call REST carrier API.
     */
    protected function callRestApi(InsuranceCarrier $carrier, InsuranceCertificate $certificate): array
    {
        // Placeholder - actual implementation would vary by carrier
        $endpoint = $carrier->verification_api_endpoint;

        if (!$endpoint) {
            return ['verified' => false, 'error' => 'No API endpoint configured'];
        }

        // Example REST call (would be customized per carrier)
        try {
            $response = Http::timeout(30)->post($endpoint, [
                'policy_number' => $certificate->policy_number,
                'named_insured' => $certificate->named_insured,
                'effective_date' => $certificate->effective_date->format('Y-m-d'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'verified' => $data['valid'] ?? false,
                    'policy_status' => $data['status'] ?? null,
                    'coverage_confirmed' => $data['coverage_amount'] ?? null,
                ];
            }

            return ['verified' => false, 'error' => 'API returned error'];

        } catch (\Exception $e) {
            return ['verified' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Call SOAP carrier API.
     */
    protected function callSoapApi(InsuranceCarrier $carrier, InsuranceCertificate $certificate): array
    {
        // Placeholder for SOAP implementation
        return ['verified' => false, 'error' => 'SOAP integration not implemented'];
    }

    /**
     * Validate coverage amount against requirements.
     */
    public function validateCoverageAmount(
        InsuranceCertificate $certificate,
        ?InsuranceRequirement $requirement = null
    ): array {
        if (!$requirement) {
            $requirement = $certificate->requirement;
        }

        if (!$requirement) {
            $requirement = InsuranceRequirement::active()
                ->ofType($certificate->insurance_type)
                ->forJurisdiction($certificate->insuranceVerification->jurisdiction ?? 'US')
                ->first();
        }

        return $certificate->validateCoverage();
    }

    /**
     * Validate additional insured clause.
     */
    public function validateAdditionalInsured(InsuranceCertificate $certificate): array
    {
        $requirement = $certificate->requirement;

        if (!$requirement || !$requirement->additional_insured_required) {
            return ['valid' => true, 'message' => 'Additional insured not required'];
        }

        if (!$certificate->has_additional_insured) {
            return ['valid' => false, 'message' => 'Additional insured clause is required'];
        }

        // Check if additional insured text matches required wording
        if ($requirement->additional_insured_wording) {
            $requiredText = strtolower($requirement->additional_insured_wording);
            $actualText = strtolower($certificate->additional_insured_text ?? '');

            // Simple contains check - production would use more sophisticated matching
            if (strpos($actualText, $requiredText) === false) {
                return [
                    'valid' => false,
                    'message' => 'Additional insured wording does not match requirements',
                    'requires_manual_review' => true,
                ];
            }
        }

        return ['valid' => true, 'verified' => true];
    }

    /**
     * Schedule expiry reminders.
     */
    public function scheduleExpiryReminders(InsuranceCertificate $certificate): void
    {
        if (!$certificate->expiry_date) {
            return;
        }

        $daysUntilExpiry = $certificate->getDaysUntilExpiry();

        // Already expired
        if ($daysUntilExpiry !== null && $daysUntilExpiry <= 0) {
            if (!$certificate->expired_notified) {
                $this->sendExpiryNotification($certificate, 0);
            }
            return;
        }

        // Schedule reminders at 90, 60, 30, 14, 7 days
        $reminderDays = [90, 60, 30, 14, 7];

        foreach ($reminderDays as $days) {
            if ($daysUntilExpiry !== null && $daysUntilExpiry <= $days) {
                $column = "expiry_{$days}_day_notified";
                if (!$certificate->$column) {
                    $this->sendExpiryNotification($certificate, $days);
                    break; // Only send one notification at a time
                }
            }
        }
    }

    /**
     * Send expiry notification.
     */
    protected function sendExpiryNotification(InsuranceCertificate $certificate, int $days): void
    {
        $user = $certificate->insuranceVerification->user;

        if ($days === 0) {
            $user->notify(new InsuranceExpiredNotification($certificate));
            $certificate->markExpiryNotificationSent(0);

            // Check if business should be suspended
            $this->checkComplianceAfterExpiry($certificate);
        } else {
            $user->notify(new InsuranceExpiryReminderNotification($certificate, $days));
            $certificate->markExpiryNotificationSent($days);
        }

        // Record in verification notification history
        $certificate->insuranceVerification->recordNotification(
            $days === 0 ? 'expired' : "expiry_{$days}_days",
            [
                'certificate_id' => $certificate->id,
                'insurance_type' => $certificate->insurance_type,
                'expiry_date' => $certificate->expiry_date->toDateString(),
            ]
        );

        Log::info('Insurance expiry notification sent', [
            'certificate_id' => $certificate->id,
            'days_until_expiry' => $days,
        ]);
    }

    /**
     * Check compliance after certificate expiry.
     */
    protected function checkComplianceAfterExpiry(InsuranceCertificate $certificate): void
    {
        $verification = $certificate->insuranceVerification;
        $verification->updateComplianceStatus();

        // If this was a required coverage and now non-compliant, consider suspension
        $requirement = InsuranceRequirement::active()
            ->ofType($certificate->insurance_type)
            ->forJurisdiction($verification->jurisdiction)
            ->required()
            ->first();

        if ($requirement && !$verification->is_fully_compliant) {
            // Auto-suspend after grace period (7 days)
            $daysExpired = $certificate->getDaysUntilExpiry();
            if ($daysExpired !== null && $daysExpired <= -7) {
                $verification->suspend(
                    "Required {$requirement->insurance_name} has expired and not been renewed"
                );
            }
        }
    }

    /**
     * Get insurance compliance status.
     */
    public function getInsuranceStatus(InsuranceVerification $verification): array
    {
        $verification->updateComplianceStatus();

        $requirements = $this->getInsuranceRequirements(
            $verification->jurisdiction,
            $verification->businessProfile->business_type ?? null,
            $verification->businessProfile->industry ?? null,
            $verification->businessProfile->business_state ?? null
        );

        $certificates = $verification->certificates()->whereNull('deleted_at')->get();
        $coverageStatus = [];

        foreach ($requirements['requirements'] as $req) {
            $cert = $certificates
                ->where('insurance_type', $req['insurance_type'])
                ->whereIn('status', [InsuranceCertificate::STATUS_PENDING, InsuranceCertificate::STATUS_VERIFIED])
                ->sortByDesc('created_at')
                ->first();

            $coverageStatus[$req['insurance_type']] = [
                'requirement' => $req,
                'certificate' => $cert ? [
                    'id' => $cert->id,
                    'status' => $cert->status,
                    'status_label' => $cert->getStatusLabel(),
                    'carrier_name' => $cert->carrier_name,
                    'coverage_amount' => $cert->getCoverageFormatted(),
                    'effective_date' => $cert->effective_date->toDateString(),
                    'expiry_date' => $cert->expiry_date->toDateString(),
                    'days_until_expiry' => $cert->getDaysUntilExpiry(),
                    'is_expired' => $cert->isExpired(),
                    'carrier_verified' => $cert->carrier_verified,
                    'meets_minimum_coverage' => $cert->meets_minimum_coverage,
                ] : null,
                'has_coverage' => $cert !== null && $cert->isActive(),
                'is_compliant' => $cert !== null && $cert->isActive() && $cert->meets_minimum_coverage,
            ];
        }

        return [
            'verification_id' => $verification->id,
            'status' => $verification->status,
            'status_label' => $verification->getStatusLabel(),
            'status_color' => $verification->getStatusColor(),
            'is_fully_compliant' => $verification->is_fully_compliant,
            'compliance_percentage' => $verification->getCompliancePercentage(),
            'is_suspended' => $verification->is_suspended,
            'suspension_reason' => $verification->suspension_reason,
            'coverages' => $coverageStatus,
            'missing_coverages' => $verification->missing_coverages ?? [],
            'expiring_soon' => $verification->expiring_soon ?? [],
            'compliant_since' => $verification->compliant_since?->toIso8601String(),
            'last_check' => $verification->last_compliance_check?->toIso8601String(),
        ];
    }

    /**
     * Update insurance certificate.
     */
    public function updateInsurance(
        InsuranceCertificate $certificate,
        array $updates,
        ?UploadedFile $newFile = null
    ): InsuranceCertificate {
        DB::beginTransaction();
        try {
            // Handle file replacement
            if ($newFile) {
                // Delete old file
                $oldPath = $certificate->getFilePath();
                if ($oldPath) {
                    Storage::disk($certificate->storage_provider)->delete($oldPath);
                }

                // Store new file
                $extension = $newFile->getClientOriginalExtension();
                $secureFilename = Str::uuid() . '.' . $extension;
                $storagePath = "insurance-certificates/{$certificate->business_profile_id}/{$secureFilename}";

                $path = $newFile->storeAs(
                    dirname($storagePath),
                    basename($storagePath),
                    config('filesystems.verification_disk', 's3')
                );

                $certificate->setFilePath($path);
                $certificate->file_hash = InsuranceCertificate::calculateFileHash($newFile->getContent());
                $certificate->original_filename = $newFile->getClientOriginalName();
                $certificate->mime_type = $newFile->getMimeType();
                $certificate->file_size = $newFile->getSize();

                // Reset verification status
                $certificate->status = InsuranceCertificate::STATUS_PENDING;
                $certificate->carrier_verified = false;
                $certificate->carrier_verified_at = null;

                $certificate->recordAccess('upload', auth()->user());
            }

            // Convert amounts to cents if provided
            if (isset($updates['coverage_amount'])) {
                $updates['coverage_amount'] = (int)($updates['coverage_amount'] * 100);
            }
            if (isset($updates['per_occurrence_limit'])) {
                $updates['per_occurrence_limit'] = (int)($updates['per_occurrence_limit'] * 100);
            }
            if (isset($updates['aggregate_limit'])) {
                $updates['aggregate_limit'] = (int)($updates['aggregate_limit'] * 100);
            }
            if (isset($updates['deductible_amount'])) {
                $updates['deductible_amount'] = (int)($updates['deductible_amount'] * 100);
            }

            // Update certificate
            $certificate->fill($updates);
            $certificate->save();

            DB::commit();

            // Re-validate if coverage changed
            if (isset($updates['coverage_amount'])) {
                $certificate->validateCoverage();
            }

            // Re-verify with carrier if needed
            if ($newFile || isset($updates['policy_number'])) {
                $this->verifyWithCarrier($certificate);
            }

            // Update compliance status
            $certificate->insuranceVerification->updateComplianceStatus();

            return $certificate->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify certificate manually (admin action).
     */
    public function verifyCertificate(
        InsuranceCertificate $certificate,
        User $reviewer,
        bool $verifyAdditionalInsured = false,
        bool $verifyWaiver = false,
        ?string $notes = null
    ): InsuranceCertificate {
        // Update additional insured verification
        if ($verifyAdditionalInsured) {
            $certificate->additional_insured_verified = true;
        }

        // Update waiver verification
        if ($verifyWaiver) {
            $certificate->waiver_verified = true;
        }

        $certificate->verify($reviewer, $notes);

        Log::info('Insurance certificate verified', [
            'certificate_id' => $certificate->id,
            'reviewer_id' => $reviewer->id,
        ]);

        return $certificate;
    }

    /**
     * Reject certificate manually (admin action).
     */
    public function rejectCertificate(
        InsuranceCertificate $certificate,
        User $reviewer,
        string $reason,
        ?string $notes = null
    ): InsuranceCertificate {
        $certificate->reject($reviewer, $reason, $notes);

        Log::info('Insurance certificate rejected', [
            'certificate_id' => $certificate->id,
            'reviewer_id' => $reviewer->id,
            'reason' => $reason,
        ]);

        return $certificate;
    }

    /**
     * Process expired certificates.
     */
    public function processExpiredCertificates(): int
    {
        $count = 0;

        // Find expired certificates that haven't been marked expired
        $expiredCerts = InsuranceCertificate::where('status', InsuranceCertificate::STATUS_VERIFIED)
            ->where('is_expired', false)
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expiredCerts as $certificate) {
            $certificate->markExpired();
            $this->sendExpiryNotification($certificate, 0);
            $count++;
        }

        return $count;
    }

    /**
     * Search insurance carriers.
     */
    public function searchCarriers(string $query, int $limit = 10): array
    {
        $carriers = InsuranceCarrier::search($query, $limit);

        return $carriers->map(function ($carrier) {
            return [
                'id' => $carrier->id,
                'name' => $carrier->name,
                'naic_code' => $carrier->naic_code,
                'am_best_rating' => $carrier->am_best_rating,
                'rating_description' => $carrier->getRatingDescription(),
                'supports_verification' => $carrier->supportsApiVerification(),
            ];
        })->toArray();
    }

    /**
     * Lift suspension when compliance is restored.
     */
    public function checkAndLiftSuspension(InsuranceVerification $verification): bool
    {
        if (!$verification->is_suspended) {
            return false;
        }

        $verification->updateComplianceStatus();

        if ($verification->is_fully_compliant) {
            $verification->liftSuspension();
            Log::info('Insurance suspension lifted', [
                'verification_id' => $verification->id,
            ]);
            return true;
        }

        return false;
    }
}
