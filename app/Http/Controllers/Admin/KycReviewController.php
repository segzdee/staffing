<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * WKR-001: Admin KYC Review Controller
 *
 * Handles admin review workflow for KYC verifications including
 * viewing documents, approving/rejecting, and bulk actions.
 */
class KycReviewController extends Controller
{
    protected KycService $kycService;

    public function __construct(KycService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * List pending KYC verifications.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'document_type' => $request->input('document_type'),
            'country' => $request->input('country'),
            'provider' => $request->input('provider'),
            'per_page' => $request->input('per_page', 20),
        ];

        $verifications = $this->kycService->getPendingReviews($filters);

        // Statistics
        $stats = [
            'pending' => KycVerification::pending()->count(),
            'in_review' => KycVerification::inReview()->count(),
            'approved_today' => KycVerification::approved()
                ->whereDate('reviewed_at', today())
                ->count(),
            'rejected_today' => KycVerification::rejected()
                ->whereDate('reviewed_at', today())
                ->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'verifications' => $verifications->items(),
                    'pagination' => [
                        'current_page' => $verifications->currentPage(),
                        'last_page' => $verifications->lastPage(),
                        'per_page' => $verifications->perPage(),
                        'total' => $verifications->total(),
                    ],
                    'stats' => $stats,
                ],
            ]);
        }

        return view('admin.kyc.index', [
            'verifications' => $verifications,
            'stats' => $stats,
            'filters' => $filters,
            'documentTypes' => config('kyc.document_types', []),
        ]);
    }

    /**
     * View verification details with documents.
     */
    public function show(int $id): View|JsonResponse
    {
        $verification = KycVerification::with(['user', 'reviewer'])->find($id);

        if (! $verification) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification not found.',
                ], 404);
            }

            return redirect()
                ->route('admin.kyc.index')
                ->with('error', 'Verification not found.');
        }

        // Get document URLs
        $documentUrls = [
            'front' => $this->kycService->getDocumentUrl($verification, 'front'),
            'back' => $this->kycService->getDocumentUrl($verification, 'back'),
            'selfie' => $this->kycService->getDocumentUrl($verification, 'selfie'),
        ];

        // Get user's verification history
        $history = KycVerification::where('user_id', $verification->user_id)
            ->where('id', '!=', $verification->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'verification' => $this->formatVerificationResponse($verification),
                    'document_urls' => $documentUrls,
                    'history' => $history->map(fn ($v) => $this->formatVerificationResponse($v)),
                ],
            ]);
        }

        return view('admin.kyc.show', [
            'verification' => $verification,
            'documentUrls' => $documentUrls,
            'history' => $history,
        ]);
    }

    /**
     * Approve a verification.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $verification = KycVerification::find($id);

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->kycService->approveVerification(
            $verification,
            Auth::user(),
            $request->input('notes')
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'verification_id' => $verification->id,
                'status' => $verification->fresh()->status,
            ],
        ]);
    }

    /**
     * Reject a verification.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $verification = KycVerification::find($id);

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'A rejection reason is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->kycService->rejectVerification(
            $verification,
            $request->input('reason'),
            Auth::user()
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'verification_id' => $verification->id,
                'status' => $verification->fresh()->status,
            ],
        ]);
    }

    /**
     * Bulk approve verifications.
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:kyc_verifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->kycService->bulkApprove(
            $request->input('ids'),
            Auth::user()
        );

        return response()->json([
            'success' => true,
            'message' => "Approved {$result['approved']} verification(s).".($result['failed'] > 0 ? " {$result['failed']} failed." : ''),
            'data' => $result,
        ]);
    }

    /**
     * Bulk reject verifications.
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:kyc_verifications,id',
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->kycService->bulkReject(
            $request->input('ids'),
            $request->input('reason'),
            Auth::user()
        );

        return response()->json([
            'success' => true,
            'message' => "Rejected {$result['rejected']} verification(s).".($result['failed'] > 0 ? " {$result['failed']} failed." : ''),
            'data' => $result,
        ]);
    }

    /**
     * View document securely.
     */
    public function viewDocument(int $id, string $type): JsonResponse
    {
        $verification = KycVerification::find($id);

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found.',
            ], 404);
        }

        if (! in_array($type, ['front', 'back', 'selfie'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid document type.',
            ], 400);
        }

        $url = $this->kycService->getDocumentUrl($verification, $type);

        if (! $url) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $url,
                'expires_in' => config('kyc.document_url_expiry', 15).' minutes',
            ],
        ]);
    }

    /**
     * Get statistics for dashboard.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'pending' => KycVerification::pending()->count(),
            'in_review' => KycVerification::inReview()->count(),
            'approved_today' => KycVerification::approved()
                ->whereDate('reviewed_at', today())
                ->count(),
            'rejected_today' => KycVerification::rejected()
                ->whereDate('reviewed_at', today())
                ->count(),
            'expiring_soon' => KycVerification::expiringSoon()->count(),
            'by_country' => KycVerification::requiringReview()
                ->selectRaw('document_country, count(*) as count')
                ->groupBy('document_country')
                ->pluck('count', 'document_country'),
            'by_document_type' => KycVerification::requiringReview()
                ->selectRaw('document_type, count(*) as count')
                ->groupBy('document_type')
                ->pluck('count', 'document_type'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get list of expiring verifications.
     */
    public function expiring(Request $request): View|JsonResponse
    {
        $days = $request->input('days', config('kyc.expiry_warning_days', 30));
        $verifications = KycVerification::expiringSoon($days)
            ->with('user')
            ->orderBy('expires_at', 'asc')
            ->paginate($request->input('per_page', 20));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'verifications' => $verifications->items(),
                    'pagination' => [
                        'current_page' => $verifications->currentPage(),
                        'last_page' => $verifications->lastPage(),
                        'per_page' => $verifications->perPage(),
                        'total' => $verifications->total(),
                    ],
                ],
            ]);
        }

        return view('admin.kyc.expiring', [
            'verifications' => $verifications,
            'days' => $days,
        ]);
    }

    /**
     * Format verification for API response.
     */
    protected function formatVerificationResponse(KycVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'user' => $verification->user ? [
                'id' => $verification->user->id,
                'name' => $verification->user->name,
                'email' => $verification->user->email,
            ] : null,
            'status' => $verification->status,
            'status_name' => $verification->status_name,
            'status_color' => $verification->status_color,
            'document_type' => $verification->document_type,
            'document_type_name' => $verification->document_type_name,
            'document_country' => $verification->document_country,
            'document_number' => $verification->document_number,
            'document_expiry' => $verification->document_expiry?->format('Y-m-d'),
            'provider' => $verification->provider,
            'confidence_score' => $verification->confidence_score,
            'rejection_reason' => $verification->rejection_reason,
            'reviewer' => $verification->reviewer ? [
                'id' => $verification->reviewer->id,
                'name' => $verification->reviewer->name,
            ] : null,
            'reviewer_notes' => $verification->reviewer_notes,
            'attempt_count' => $verification->attempt_count,
            'max_attempts' => $verification->max_attempts,
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'created_at' => $verification->created_at->toIso8601String(),
            'reviewed_at' => $verification->reviewed_at?->toIso8601String(),
            'ip_address' => $verification->ip_address,
        ];
    }
}
