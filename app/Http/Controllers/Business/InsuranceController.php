<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\SubmitInsuranceRequest;
use App\Http\Requests\Business\UpdateInsuranceRequest;
use App\Models\InsuranceCertificate;
use App\Models\InsuranceVerification;
use App\Services\InsuranceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Business Insurance Controller
 * BIZ-REG-005: Insurance & Compliance
 *
 * Handles insurance certificate submission and compliance tracking
 */
class InsuranceController extends Controller
{
    protected InsuranceVerificationService $insuranceService;

    public function __construct(InsuranceVerificationService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Get insurance requirements for jurisdiction.
     *
     * GET /api/business/insurance/requirements
     */
    public function getRequirements(Request $request): JsonResponse
    {
        $request->validate([
            'jurisdiction' => 'required|string|size:2',
            'business_type' => 'nullable|string',
            'industry' => 'nullable|string',
            'region' => 'nullable|string',
        ]);

        $requirements = $this->insuranceService->getInsuranceRequirements(
            $request->jurisdiction,
            $request->business_type,
            $request->industry,
            $request->region
        );

        return response()->json([
            'success' => true,
            'data' => $requirements,
        ]);
    }

    /**
     * Submit insurance certificate.
     *
     * POST /api/business/insurance
     */
    public function submitInsurance(SubmitInsuranceRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'message' => 'Only business accounts can submit insurance',
            ], 403);
        }

        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        try {
            // Get or create insurance verification
            $verification = $this->insuranceService->getOrCreateVerification(
                $profile,
                $request->jurisdiction ?? $profile->business_country ?? 'US'
            );

            // Submit the certificate
            $certificate = $this->insuranceService->submitInsurance(
                $verification,
                $request->file('certificate'),
                $request->validated()
            );

            // Get updated status
            $status = $this->insuranceService->getInsuranceStatus($verification);

            return response()->json([
                'success' => true,
                'message' => 'Insurance certificate submitted successfully',
                'data' => [
                    'certificate' => [
                        'id' => $certificate->id,
                        'insurance_type' => $certificate->insurance_type,
                        'carrier_name' => $certificate->carrier_name,
                        'coverage_amount' => $certificate->getCoverageFormatted(),
                        'effective_date' => $certificate->effective_date->toDateString(),
                        'expiry_date' => $certificate->expiry_date->toDateString(),
                        'status' => $certificate->status,
                        'status_label' => $certificate->getStatusLabel(),
                        'carrier_verified' => $certificate->carrier_verified,
                        'meets_minimum_coverage' => $certificate->meets_minimum_coverage,
                    ],
                    'compliance_status' => $status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit insurance', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit insurance certificate',
            ], 500);
        }
    }

    /**
     * Get insurance status.
     *
     * GET /api/business/insurance
     */
    public function getInsuranceStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $verification = InsuranceVerification::where('business_profile_id', $profile->id)->first();

        if (!$verification) {
            // Return requirements for profile jurisdiction
            $jurisdiction = $profile->business_country ?? 'US';
            $requirements = $this->insuranceService->getInsuranceRequirements(
                $jurisdiction,
                $profile->business_type,
                $profile->industry,
                $profile->business_state
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'has_verification' => false,
                    'is_compliant' => false,
                    'requirements' => $requirements,
                ],
            ]);
        }

        $status = $this->insuranceService->getInsuranceStatus($verification);

        return response()->json([
            'success' => true,
            'data' => array_merge(['has_verification' => true], $status),
        ]);
    }

    /**
     * Update insurance certificate.
     *
     * PUT /api/business/insurance/{certificate}
     */
    public function updateInsurance(UpdateInsuranceRequest $request, InsuranceCertificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Verify ownership
        if ($certificate->insuranceVerification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if certificate can be updated
        if ($certificate->status === InsuranceCertificate::STATUS_VERIFIED && !$request->hasFile('certificate')) {
            // Only allow certain updates on verified certificates
            $allowedFields = ['auto_renews', 'additional_insured_text'];
            $updates = array_intersect_key($request->validated(), array_flip($allowedFields));

            if (empty($updates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot modify verified certificate. Please submit a new certificate.',
                ], 422);
            }
        }

        try {
            $certificate = $this->insuranceService->updateInsurance(
                $certificate,
                $request->validated(),
                $request->file('certificate')
            );

            $status = $this->insuranceService->getInsuranceStatus($certificate->insuranceVerification);

            return response()->json([
                'success' => true,
                'message' => 'Insurance certificate updated successfully',
                'data' => [
                    'certificate' => [
                        'id' => $certificate->id,
                        'insurance_type' => $certificate->insurance_type,
                        'carrier_name' => $certificate->carrier_name,
                        'coverage_amount' => $certificate->getCoverageFormatted(),
                        'effective_date' => $certificate->effective_date->toDateString(),
                        'expiry_date' => $certificate->expiry_date->toDateString(),
                        'status' => $certificate->status,
                        'status_label' => $certificate->getStatusLabel(),
                    ],
                    'compliance_status' => $status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update insurance', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update insurance certificate',
            ], 500);
        }
    }

    /**
     * Get certificate details.
     *
     * GET /api/business/insurance/{certificate}
     */
    public function getCertificate(Request $request, InsuranceCertificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Verify ownership or admin
        if ($certificate->insuranceVerification->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Record view access
        $certificate->recordAccess('view', $user);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $certificate->id,
                'insurance_type' => $certificate->insurance_type,
                'insurance_type_label' => $certificate->getInsuranceTypeLabel(),
                'policy_number' => $certificate->policy_number,
                'carrier_name' => $certificate->carrier_name,
                'carrier_am_best_rating' => $certificate->carrier_am_best_rating,
                'named_insured' => $certificate->named_insured,
                'insured_address' => $certificate->insured_address,
                'coverage_amount' => $certificate->getCoverageFormatted(),
                'coverage_amount_cents' => $certificate->coverage_amount,
                'coverage_currency' => $certificate->coverage_currency,
                'per_occurrence_limit' => $certificate->per_occurrence_limit ? $certificate->per_occurrence_limit / 100 : null,
                'aggregate_limit' => $certificate->aggregate_limit ? $certificate->aggregate_limit / 100 : null,
                'deductible_amount' => $certificate->deductible_amount ? $certificate->deductible_amount / 100 : null,
                'effective_date' => $certificate->effective_date->toDateString(),
                'expiry_date' => $certificate->expiry_date->toDateString(),
                'days_until_expiry' => $certificate->getDaysUntilExpiry(),
                'is_expired' => $certificate->isExpired(),
                'auto_renews' => $certificate->auto_renews,
                'has_additional_insured' => $certificate->has_additional_insured,
                'additional_insured_verified' => $certificate->additional_insured_verified,
                'has_waiver_of_subrogation' => $certificate->has_waiver_of_subrogation,
                'waiver_verified' => $certificate->waiver_verified,
                'status' => $certificate->status,
                'status_label' => $certificate->getStatusLabel(),
                'status_color' => $certificate->getStatusColor(),
                'carrier_verified' => $certificate->carrier_verified,
                'carrier_verified_at' => $certificate->carrier_verified_at?->toIso8601String(),
                'meets_minimum_coverage' => $certificate->meets_minimum_coverage,
                'coverage_validation' => $certificate->coverage_validation_details,
                'reviewed_at' => $certificate->reviewed_at?->toIso8601String(),
                'review_notes' => $certificate->review_notes,
                'rejection_reason' => $certificate->rejection_reason,
                'file_info' => [
                    'filename' => $certificate->original_filename,
                    'size' => $certificate->getFileSizeFormatted(),
                    'mime_type' => $certificate->mime_type,
                ],
                'created_at' => $certificate->created_at->toIso8601String(),
                'updated_at' => $certificate->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get certificate download URL.
     *
     * GET /api/business/insurance/{certificate}/url
     */
    public function getCertificateUrl(Request $request, InsuranceCertificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Verify ownership or admin
        if ($certificate->insuranceVerification->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Record view access
        $certificate->recordAccess('view', $user);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $certificate->getSecureUrl(30),
                'expires_in' => 1800,
            ],
        ]);
    }

    /**
     * Download certificate.
     *
     * GET /api/business/insurance/certificates/{token}/download
     */
    public function downloadCertificate(Request $request, string $token): mixed
    {
        $request->validate([
            'expires' => 'required|integer',
            'signature' => 'required|string',
        ]);

        $certificate = InsuranceCertificate::where('access_token', $token)->first();

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found',
            ], 404);
        }

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $token . $request->expires,
            config('app.key')
        );

        if (!hash_equals($expectedSignature, $request->signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        // Check expiry
        if ($request->expires < now()->timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Download link has expired',
            ], 403);
        }

        // Verify user access
        $user = $request->user();
        if (!$user || ($certificate->insuranceVerification->user_id !== $user->id && !$user->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Record access
        $certificate->recordAccess('download', $user);

        // Stream file
        $path = $certificate->getFilePath();

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return \Storage::disk($certificate->storage_provider)->download(
            $path,
            $certificate->original_filename,
            ['Content-Type' => $certificate->mime_type]
        );
    }

    /**
     * Delete/Remove a certificate.
     *
     * DELETE /api/business/insurance/{certificate}
     */
    public function deleteCertificate(Request $request, InsuranceCertificate $certificate): JsonResponse
    {
        $user = $request->user();

        // Verify ownership
        if ($certificate->insuranceVerification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only allow deletion of pending or rejected certificates
        if ($certificate->status === InsuranceCertificate::STATUS_VERIFIED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete verified certificates. They will be replaced when you submit a new certificate.',
            ], 422);
        }

        try {
            $certificate->delete();

            // Update compliance status
            $certificate->insuranceVerification->updateComplianceStatus();

            return response()->json([
                'success' => true,
                'message' => 'Certificate removed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete certificate', [
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove certificate',
            ], 500);
        }
    }

    /**
     * Search insurance carriers.
     *
     * GET /api/business/insurance/carriers/search
     */
    public function searchCarriers(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $carriers = $this->insuranceService->searchCarriers(
            $request->q,
            $request->limit ?? 10
        );

        return response()->json([
            'success' => true,
            'data' => $carriers,
        ]);
    }

    /**
     * Get compliance history.
     *
     * GET /api/business/insurance/history
     */
    public function getComplianceHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $verification = InsuranceVerification::where('business_profile_id', $profile->id)->first();

        if (!$verification) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_history' => false,
                ],
            ]);
        }

        // Get all certificates including deleted
        $certificates = InsuranceCertificate::withTrashed()
            ->where('insurance_verification_id', $verification->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'has_history' => true,
                'notification_history' => $verification->notification_history ?? [],
                'certificates' => $certificates->map(function ($cert) {
                    return [
                        'id' => $cert->id,
                        'insurance_type' => $cert->insurance_type,
                        'insurance_type_label' => $cert->getInsuranceTypeLabel(),
                        'carrier_name' => $cert->carrier_name,
                        'policy_number' => $cert->policy_number,
                        'coverage_amount' => $cert->getCoverageFormatted(),
                        'effective_date' => $cert->effective_date->toDateString(),
                        'expiry_date' => $cert->expiry_date->toDateString(),
                        'status' => $cert->status,
                        'status_label' => $cert->getStatusLabel(),
                        'is_deleted' => $cert->trashed(),
                        'deleted_at' => $cert->deleted_at?->toIso8601String(),
                        'created_at' => $cert->created_at->toIso8601String(),
                    ];
                })->toArray(),
            ],
        ]);
    }
}
