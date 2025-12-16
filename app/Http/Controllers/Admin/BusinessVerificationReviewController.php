<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewVerificationRequest;
use App\Http\Requests\Admin\ReviewInsuranceRequest;
use App\Models\BusinessDocument;
use App\Models\BusinessVerification;
use App\Models\InsuranceCertificate;
use App\Models\InsuranceVerification;
use App\Services\BusinessVerificationService;
use App\Services\InsuranceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Admin Business Verification Review Controller
 * BIZ-REG-004 & BIZ-REG-005: Admin Review Interface
 *
 * Handles admin review queue for KYB and insurance verifications
 */
class BusinessVerificationReviewController extends Controller
{
    protected BusinessVerificationService $verificationService;
    protected InsuranceVerificationService $insuranceService;

    public function __construct(
        BusinessVerificationService $verificationService,
        InsuranceVerificationService $insuranceService
    ) {
        $this->verificationService = $verificationService;
        $this->insuranceService = $insuranceService;
        $this->middleware(['auth', 'admin']);
    }

    // ==================== KYB VERIFICATION REVIEW ====================

    /**
     * Get KYB verification review queue.
     *
     * GET /api/admin/verification/queue
     */
    public function getReviewQueue(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string',
            'jurisdiction' => 'nullable|string|size:2',
            'priority' => 'nullable|string|in:high,normal,low',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:submitted_at,review_priority,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        $query = BusinessVerification::with(['businessProfile', 'user', 'documents'])
            ->requiresManualReview();

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by jurisdiction
        if ($request->jurisdiction) {
            $query->forJurisdiction($request->jurisdiction);
        }

        // Filter by priority
        if ($request->priority) {
            $priorityMap = ['high' => 2, 'normal' => 1, 'low' => 0];
            $query->where('review_priority', $priorityMap[$request->priority] ?? 0);
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'review_priority';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if ($sortBy !== 'submitted_at') {
            $query->orderBy('submitted_at', 'asc');
        }

        $verifications = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => [
                'verifications' => $verifications->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'business_name' => $v->businessProfile->business_name ?? $v->user->name,
                        'business_type' => $v->businessProfile->business_type ?? null,
                        'jurisdiction' => $v->jurisdiction,
                        'status' => $v->status,
                        'status_label' => $v->getStatusLabel(),
                        'review_priority' => $v->review_priority,
                        'manual_review_reason' => $v->manual_review_reason,
                        'documents_count' => $v->documents->count(),
                        'pending_documents' => $v->documents->where('status', 'pending')->count(),
                        'submitted_at' => $v->submitted_at?->toIso8601String(),
                        'auto_verified' => $v->auto_verified,
                        'submission_attempts' => $v->submission_attempts,
                    ];
                }),
                'pagination' => [
                    'total' => $verifications->total(),
                    'per_page' => $verifications->perPage(),
                    'current_page' => $verifications->currentPage(),
                    'last_page' => $verifications->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get verification details for review.
     *
     * GET /api/admin/verification/{verification}
     */
    public function getVerificationDetails(BusinessVerification $verification): JsonResponse
    {
        $verification->load(['businessProfile', 'user', 'documents.requirement', 'reviewer']);

        $status = $this->verificationService->getVerificationStatus($verification);

        return response()->json([
            'success' => true,
            'data' => [
                'verification' => $status,
                'business' => [
                    'id' => $verification->businessProfile->id,
                    'user_id' => $verification->user_id,
                    'business_name' => $verification->businessProfile->business_name,
                    'business_type' => $verification->businessProfile->business_type,
                    'industry' => $verification->businessProfile->industry,
                    'address' => $verification->businessProfile->business_address,
                    'city' => $verification->businessProfile->business_city,
                    'state' => $verification->businessProfile->business_state,
                    'country' => $verification->businessProfile->business_country,
                    'phone' => $verification->businessProfile->business_phone,
                    'created_at' => $verification->businessProfile->created_at->toIso8601String(),
                ],
                'user' => [
                    'id' => $verification->user->id,
                    'name' => $verification->user->name,
                    'email' => $verification->user->email,
                ],
                'extracted_data' => [
                    'legal_business_name' => $verification->legal_business_name,
                    'trading_name' => $verification->trading_name,
                    'registration_number' => $verification->registration_number,
                    'tax_id' => $verification->tax_id,
                    'business_type' => $verification->business_type,
                    'incorporation_date' => $verification->incorporation_date?->toDateString(),
                    'incorporation_state' => $verification->incorporation_state,
                    'incorporation_country' => $verification->incorporation_country,
                    'registered_address' => $verification->registered_address,
                    'registered_city' => $verification->registered_city,
                    'registered_state' => $verification->registered_state,
                    'registered_postal_code' => $verification->registered_postal_code,
                    'registered_country' => $verification->registered_country,
                ],
                'auto_verification_results' => $verification->auto_verification_results,
                'reviewer' => $verification->reviewer ? [
                    'id' => $verification->reviewer->id,
                    'name' => $verification->reviewer->name,
                ] : null,
                'review_history' => [
                    'started_at' => $verification->review_started_at?->toIso8601String(),
                    'reviewed_at' => $verification->reviewed_at?->toIso8601String(),
                    'notes' => $verification->review_notes,
                ],
            ],
        ]);
    }

    /**
     * Start reviewing a verification.
     *
     * POST /api/admin/verification/{verification}/start-review
     */
    public function startReview(Request $request, BusinessVerification $verification): JsonResponse
    {
        $admin = $request->user();

        // Check if already being reviewed by another admin
        if ($verification->reviewer_id && $verification->reviewer_id !== $admin->id) {
            return response()->json([
                'success' => false,
                'message' => 'This verification is already being reviewed by another admin',
                'reviewer' => $verification->reviewer->name ?? 'Unknown',
            ], 409);
        }

        $verification->startReview($admin);

        Log::info('Admin started verification review', [
            'admin_id' => $admin->id,
            'verification_id' => $verification->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review started',
            'data' => [
                'verification_id' => $verification->id,
                'status' => $verification->status,
            ],
        ]);
    }

    /**
     * Review document within verification.
     *
     * POST /api/admin/verification/documents/{document}/review
     */
    public function reviewDocument(Request $request, BusinessDocument $document): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:verified,rejected',
            'reason' => 'required_if:status,rejected|nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $admin = $request->user();

        if ($request->status === 'verified') {
            $document->verify($admin, $request->notes);
        } else {
            $document->reject($admin, $request->reason, $request->notes);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document ' . $request->status,
            'data' => [
                'document_id' => $document->id,
                'status' => $document->status,
            ],
        ]);
    }

    /**
     * Approve verification.
     *
     * POST /api/admin/verification/{verification}/approve
     */
    public function approveVerification(ReviewVerificationRequest $request, BusinessVerification $verification): JsonResponse
    {
        $admin = $request->user();

        try {
            $verification = $this->verificationService->completeVerification(
                $verification,
                $admin,
                $request->notes
            );

            Log::info('Business verification approved', [
                'admin_id' => $admin->id,
                'verification_id' => $verification->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification approved successfully',
                'data' => [
                    'verification_id' => $verification->id,
                    'status' => $verification->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve verification', [
                'admin_id' => $admin->id,
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve verification',
            ], 500);
        }
    }

    /**
     * Reject verification.
     *
     * POST /api/admin/verification/{verification}/reject
     */
    public function rejectVerification(ReviewVerificationRequest $request, BusinessVerification $verification): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'details' => 'nullable|array',
        ]);

        $admin = $request->user();

        try {
            $verification = $this->verificationService->rejectVerification(
                $verification,
                $admin,
                $request->reason,
                $request->details,
                $request->notes
            );

            Log::info('Business verification rejected', [
                'admin_id' => $admin->id,
                'verification_id' => $verification->id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification rejected',
                'data' => [
                    'verification_id' => $verification->id,
                    'status' => $verification->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject verification', [
                'admin_id' => $admin->id,
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject verification',
            ], 500);
        }
    }

    /**
     * Request additional documents.
     *
     * POST /api/admin/verification/{verification}/request-documents
     */
    public function requestDocuments(Request $request, BusinessVerification $verification): JsonResponse
    {
        $request->validate([
            'required_documents' => 'required|array|min:1',
            'required_documents.*' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $verification->requestDocuments(
            $request->required_documents,
            $request->notes
        );

        Log::info('Additional documents requested', [
            'admin_id' => $request->user()->id,
            'verification_id' => $verification->id,
            'documents' => $request->required_documents,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document request sent to business',
            'data' => [
                'verification_id' => $verification->id,
                'status' => $verification->status,
                'required_documents' => $request->required_documents,
            ],
        ]);
    }

    /**
     * Update extracted data.
     *
     * PUT /api/admin/verification/{verification}/extracted-data
     */
    public function updateExtractedData(Request $request, BusinessVerification $verification): JsonResponse
    {
        $validated = $request->validate([
            'legal_business_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'business_type' => 'nullable|string|max:100',
            'incorporation_date' => 'nullable|date',
            'incorporation_state' => 'nullable|string|max:100',
            'incorporation_country' => 'nullable|string|max:100',
            'registered_address' => 'nullable|string|max:255',
            'registered_city' => 'nullable|string|max:100',
            'registered_state' => 'nullable|string|max:100',
            'registered_postal_code' => 'nullable|string|max:50',
            'registered_country' => 'nullable|string|max:100',
        ]);

        $verification->update($validated);

        Log::info('Verification extracted data updated', [
            'admin_id' => $request->user()->id,
            'verification_id' => $verification->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extracted data updated',
        ]);
    }

    // ==================== INSURANCE VERIFICATION REVIEW ====================

    /**
     * Get insurance review queue.
     *
     * GET /api/admin/insurance/queue
     */
    public function getInsuranceReviewQueue(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,verified,rejected',
            'insurance_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = InsuranceCertificate::with(['insuranceVerification.businessProfile', 'insuranceVerification.user'])
            ->pending();

        if ($request->insurance_type) {
            $query->ofType($request->insurance_type);
        }

        $certificates = $query->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => [
                'certificates' => $certificates->map(function ($cert) {
                    return [
                        'id' => $cert->id,
                        'business_name' => $cert->insuranceVerification->businessProfile->business_name ?? 'Unknown',
                        'insurance_type' => $cert->insurance_type,
                        'insurance_type_label' => $cert->getInsuranceTypeLabel(),
                        'carrier_name' => $cert->carrier_name,
                        'coverage_amount' => $cert->getCoverageFormatted(),
                        'effective_date' => $cert->effective_date->toDateString(),
                        'expiry_date' => $cert->expiry_date->toDateString(),
                        'status' => $cert->status,
                        'carrier_verified' => $cert->carrier_verified,
                        'meets_minimum_coverage' => $cert->meets_minimum_coverage,
                        'created_at' => $cert->created_at->toIso8601String(),
                    ];
                }),
                'pagination' => [
                    'total' => $certificates->total(),
                    'per_page' => $certificates->perPage(),
                    'current_page' => $certificates->currentPage(),
                    'last_page' => $certificates->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get insurance certificate details for review.
     *
     * GET /api/admin/insurance/{certificate}
     */
    public function getInsuranceDetails(InsuranceCertificate $certificate): JsonResponse
    {
        $certificate->load(['insuranceVerification.businessProfile', 'insuranceVerification.user', 'requirement', 'reviewer']);

        return response()->json([
            'success' => true,
            'data' => [
                'certificate' => [
                    'id' => $certificate->id,
                    'insurance_type' => $certificate->insurance_type,
                    'insurance_type_label' => $certificate->getInsuranceTypeLabel(),
                    'policy_number' => $certificate->policy_number,
                    'carrier_name' => $certificate->carrier_name,
                    'carrier_naic_code' => $certificate->carrier_naic_code,
                    'carrier_am_best_rating' => $certificate->carrier_am_best_rating,
                    'named_insured' => $certificate->named_insured,
                    'insured_address' => $certificate->insured_address,
                    'coverage_amount' => $certificate->getCoverageFormatted(),
                    'coverage_amount_cents' => $certificate->coverage_amount,
                    'per_occurrence_limit' => $certificate->per_occurrence_limit,
                    'aggregate_limit' => $certificate->aggregate_limit,
                    'deductible_amount' => $certificate->deductible_amount,
                    'effective_date' => $certificate->effective_date->toDateString(),
                    'expiry_date' => $certificate->expiry_date->toDateString(),
                    'auto_renews' => $certificate->auto_renews,
                    'has_additional_insured' => $certificate->has_additional_insured,
                    'additional_insured_text' => $certificate->additional_insured_text,
                    'additional_insured_verified' => $certificate->additional_insured_verified,
                    'has_waiver_of_subrogation' => $certificate->has_waiver_of_subrogation,
                    'waiver_verified' => $certificate->waiver_verified,
                    'status' => $certificate->status,
                    'carrier_verified' => $certificate->carrier_verified,
                    'carrier_verified_at' => $certificate->carrier_verified_at?->toIso8601String(),
                    'carrier_verification_response' => $certificate->carrier_verification_response,
                    'extracted_data' => $certificate->extracted_data,
                    'extraction_confidence' => $certificate->extraction_confidence,
                    'meets_minimum_coverage' => $certificate->meets_minimum_coverage,
                    'coverage_validation_details' => $certificate->coverage_validation_details,
                    'file_info' => [
                        'filename' => $certificate->original_filename,
                        'size' => $certificate->getFileSizeFormatted(),
                        'mime_type' => $certificate->mime_type,
                    ],
                ],
                'requirement' => $certificate->requirement ? [
                    'id' => $certificate->requirement->id,
                    'insurance_name' => $certificate->requirement->insurance_name,
                    'minimum_coverage' => $certificate->requirement->getMinimumCoverageFormatted(),
                    'additional_insured_required' => $certificate->requirement->additional_insured_required,
                    'waiver_of_subrogation_required' => $certificate->requirement->waiver_of_subrogation_required,
                ] : null,
                'business' => [
                    'id' => $certificate->insuranceVerification->businessProfile->id,
                    'business_name' => $certificate->insuranceVerification->businessProfile->business_name,
                    'business_type' => $certificate->insuranceVerification->businessProfile->business_type,
                ],
                'reviewer' => $certificate->reviewer ? [
                    'id' => $certificate->reviewer->id,
                    'name' => $certificate->reviewer->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Approve insurance certificate.
     *
     * POST /api/admin/insurance/{certificate}/approve
     */
    public function approveInsurance(ReviewInsuranceRequest $request, InsuranceCertificate $certificate): JsonResponse
    {
        $admin = $request->user();

        try {
            $certificate = $this->insuranceService->verifyCertificate(
                $certificate,
                $admin,
                $request->boolean('verify_additional_insured', false),
                $request->boolean('verify_waiver', false),
                $request->notes
            );

            Log::info('Insurance certificate approved', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Insurance certificate verified',
                'data' => [
                    'certificate_id' => $certificate->id,
                    'status' => $certificate->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to approve insurance', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify certificate',
            ], 500);
        }
    }

    /**
     * Reject insurance certificate.
     *
     * POST /api/admin/insurance/{certificate}/reject
     */
    public function rejectInsurance(ReviewInsuranceRequest $request, InsuranceCertificate $certificate): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $admin = $request->user();

        try {
            $certificate = $this->insuranceService->rejectCertificate(
                $certificate,
                $admin,
                $request->reason,
                $request->notes
            );

            Log::info('Insurance certificate rejected', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Insurance certificate rejected',
                'data' => [
                    'certificate_id' => $certificate->id,
                    'status' => $certificate->status,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject insurance', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject certificate',
            ], 500);
        }
    }

    /**
     * Get document/certificate file for viewing.
     *
     * GET /api/admin/verification/documents/{document}/view
     * GET /api/admin/insurance/{certificate}/view
     */
    public function viewDocument(Request $request, $id, string $type = 'document'): mixed
    {
        $admin = $request->user();

        if ($type === 'insurance') {
            $record = InsuranceCertificate::findOrFail($id);
            $record->recordAccess('view', $admin);
        } else {
            $record = BusinessDocument::findOrFail($id);
            $record->recordAccess('view', $admin);
        }

        $path = $record->getFilePath();

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        // Return inline for PDF/images, download for others
        $inline = in_array($record->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif']);

        return \Storage::disk($record->storage_provider)->response(
            $path,
            $record->original_filename,
            [
                'Content-Type' => $record->mime_type,
                'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $record->original_filename . '"',
            ]
        );
    }

    // ==================== STATISTICS ====================

    /**
     * Get verification statistics.
     *
     * GET /api/admin/verification/stats
     */
    public function getStatistics(): JsonResponse
    {
        $kybStats = [
            'pending' => BusinessVerification::pending()->count(),
            'in_review' => BusinessVerification::inReview()->count(),
            'approved_today' => BusinessVerification::approved()
                ->whereDate('reviewed_at', today())
                ->count(),
            'rejected_today' => BusinessVerification::rejected()
                ->whereDate('reviewed_at', today())
                ->count(),
            'avg_review_time_hours' => BusinessVerification::approved()
                ->whereNotNull('submitted_at')
                ->whereNotNull('reviewed_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at)) as avg_hours')
                ->value('avg_hours') ?? 0,
        ];

        $insuranceStats = [
            'pending' => InsuranceCertificate::pending()->count(),
            'verified_today' => InsuranceCertificate::verified()
                ->whereDate('reviewed_at', today())
                ->count(),
            'expiring_30_days' => InsuranceCertificate::expiringSoon(30)->count(),
            'expired' => InsuranceCertificate::expired()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'kyb' => $kybStats,
                'insurance' => $insuranceStats,
            ],
        ]);
    }
}
