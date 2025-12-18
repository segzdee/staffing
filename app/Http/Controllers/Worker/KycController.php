<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * WKR-001: Worker KYC Controller
 *
 * Handles KYC verification flow for workers including document upload,
 * status checking, and re-submission after rejection.
 */
class KycController extends Controller
{
    protected KycService $kycService;

    public function __construct(KycService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * Display the KYC status page.
     */
    public function index(): View
    {
        $user = Auth::user();
        $status = $this->kycService->getKycStatus($user);
        $verifications = KycVerification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('worker.kyc.index', [
            'status' => $status,
            'verifications' => $verifications,
            'documentTypes' => config('kyc.document_types', []),
        ]);
    }

    /**
     * Get KYC status via API.
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access KYC verification.',
            ], 403);
        }

        $status = $this->kycService->getKycStatus($user);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Show the document upload form.
     */
    public function create(): View
    {
        $user = Auth::user();
        $country = $user->workerProfile?->country ?? 'US';
        $requirements = $this->kycService->getVerificationRequirements($country);

        // Check if user can submit
        $status = $this->kycService->getKycStatus($user);

        if (! $status['can_submit']) {
            return redirect()
                ->route('worker.kyc.index')
                ->with('error', 'You cannot submit a new verification at this time.');
        }

        return view('worker.kyc.create', [
            'requirements' => $requirements,
            'country' => $country,
            'maxFileSize' => config('kyc.upload.max_size', 10240),
            'allowedMimes' => config('kyc.upload.allowed_extensions', ['jpg', 'png', 'pdf']),
        ]);
    }

    /**
     * Submit new KYC verification.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can submit KYC verification.',
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|in:'.implode(',', config('kyc.document_types', [])),
            'document_country' => 'required|string|size:2',
            'document_number' => 'nullable|string|max:50',
            'document_expiry' => 'nullable|date|after:today',
            'document_front' => 'required|file|mimes:'.implode(',', config('kyc.upload.allowed_extensions', [])).'|max:'.config('kyc.upload.max_size', 10240),
            'document_back' => 'nullable|file|mimes:'.implode(',', config('kyc.upload.allowed_extensions', [])).'|max:'.config('kyc.upload.max_size', 10240),
            'selfie' => (config('kyc.selfie_required', true) ? 'required' : 'nullable').'|file|mimes:jpg,jpeg,png,webp|max:'.config('kyc.upload.max_size', 10240),
        ], [
            'document_front.required' => 'Please upload the front of your document.',
            'document_front.mimes' => 'Document must be an image (JPG, PNG) or PDF file.',
            'document_front.max' => 'Document file size must not exceed 10MB.',
            'selfie.required' => 'A selfie photo is required for verification.',
            'selfie.mimes' => 'Selfie must be a JPG, JPEG, PNG, or WebP image.',
            'document_expiry.after' => 'Document must not be expired.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->kycService->initiateVerification($user, [
            'document_type' => $request->input('document_type'),
            'document_country' => strtoupper($request->input('document_country')),
            'document_number' => $request->input('document_number'),
            'document_expiry' => $request->input('document_expiry'),
            'document_front' => $request->file('document_front'),
            'document_back' => $request->file('document_back'),
            'selfie' => $request->file('selfie'),
        ]);

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
                'verification_id' => $result['verification_id'],
                'redirect_url' => route('worker.kyc.index'),
            ],
        ]);
    }

    /**
     * Show verification details.
     */
    public function show(int $id): View|JsonResponse
    {
        $user = Auth::user();
        $verification = KycVerification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $verification) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification not found.',
                ], 404);
            }

            return redirect()
                ->route('worker.kyc.index')
                ->with('error', 'Verification not found.');
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $this->formatVerificationResponse($verification),
            ]);
        }

        return view('worker.kyc.show', [
            'verification' => $verification,
        ]);
    }

    /**
     * Show resubmit form for rejected verification.
     */
    public function resubmit(int $id): View
    {
        $user = Auth::user();
        $verification = KycVerification::where('user_id', $user->id)
            ->where('id', $id)
            ->where('status', KycVerification::STATUS_REJECTED)
            ->first();

        if (! $verification) {
            return redirect()
                ->route('worker.kyc.index')
                ->with('error', 'Verification not found or cannot be resubmitted.');
        }

        if (! $verification->canRetry()) {
            return redirect()
                ->route('worker.kyc.index')
                ->with('error', 'Maximum verification attempts reached. Please contact support.');
        }

        $country = $verification->document_country;
        $requirements = $this->kycService->getVerificationRequirements($country);

        return view('worker.kyc.resubmit', [
            'verification' => $verification,
            'requirements' => $requirements,
            'maxFileSize' => config('kyc.upload.max_size', 10240),
            'allowedMimes' => config('kyc.upload.allowed_extensions', ['jpg', 'png', 'pdf']),
        ]);
    }

    /**
     * Get verification requirements for a country.
     */
    public function requirements(Request $request): JsonResponse
    {
        $country = $request->input('country', 'US');
        $requirements = $this->kycService->getVerificationRequirements($country);

        return response()->json([
            'success' => true,
            'data' => $requirements,
        ]);
    }

    /**
     * Get verification history.
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        $verifications = KycVerification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($v) => $this->formatVerificationResponse($v));

        return response()->json([
            'success' => true,
            'data' => $verifications,
        ]);
    }

    /**
     * Format verification for API response.
     */
    protected function formatVerificationResponse(KycVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'status' => $verification->status,
            'status_name' => $verification->status_name,
            'status_color' => $verification->status_color,
            'document_type' => $verification->document_type,
            'document_type_name' => $verification->document_type_name,
            'document_country' => $verification->document_country,
            'document_expiry' => $verification->document_expiry?->format('Y-m-d'),
            'rejection_reason' => $verification->rejection_reason,
            'can_retry' => $verification->canRetry(),
            'attempt_count' => $verification->attempt_count,
            'max_attempts' => $verification->max_attempts,
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'created_at' => $verification->created_at->toIso8601String(),
            'reviewed_at' => $verification->reviewed_at?->toIso8601String(),
            'is_expiring_soon' => $verification->isDocumentExpiringSoon(),
        ];
    }
}
