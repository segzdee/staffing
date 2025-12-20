<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\IdentityVerification;
use App\Models\LivenessCheck;
use App\Services\IdentityVerificationService;
use App\Services\LivenessCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Identity Verification Controller - STAFF-REG-004
 *
 * Handles KYC verification flow for workers.
 */
class IdentityVerificationController extends Controller
{
    protected IdentityVerificationService $verificationService;

    protected LivenessCheckService $livenessService;

    public function __construct(
        IdentityVerificationService $verificationService,
        LivenessCheckService $livenessService
    ) {
        $this->verificationService = $verificationService;
        $this->livenessService = $livenessService;
    }

    /**
     * Show the identity verification status page.
     *
     * GET /worker/identity
     */
    public function index()
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            abort(403, 'Only workers can access identity verification.');
        }

        // Get current verification status
        $status = $this->verificationService->getVerificationStatus($user);

        // Get verification history
        $verifications = IdentityVerification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get liveness check status
        $latestLiveness = LivenessCheck::where('user_id', $user->id)
            ->latest()
            ->first();

        return view('worker.verification.identity', compact(
            'status',
            'verifications',
            'latestLiveness'
        ));
    }

    /**
     * Alias for initiate() to match route definition.
     */
    public function initiateVerification(Request $request): JsonResponse
    {
        return $this->initiate($request);
    }

    /**
     * Get the current verification status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access identity verification.',
            ], 403);
        }

        $status = $this->verificationService->getVerificationStatus($user);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Initiate a new identity verification.
     */
    public function initiate(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can initiate identity verification.',
            ], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'level' => 'sometimes|string|in:basic,standard,enhanced',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $level = $request->input('level', 'standard');

        $result = $this->verificationService->initiateVerification($user, $level);

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
                'sdk_token' => $result['sdk_token'],
                'status' => $result['status'],
                'level' => $result['level'] ?? $level,
            ],
        ]);
    }

    /**
     * Retry a failed verification.
     */
    public function retry(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can retry identity verification.',
            ], 403);
        }

        $result = $this->verificationService->retryVerification($user);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Verification retry initiated.',
            'data' => [
                'verification_id' => $result['verification_id'],
                'sdk_token' => $result['sdk_token'],
                'status' => $result['status'],
            ],
        ]);
    }

    /**
     * Get verification details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatVerificationResponse($verification),
        ]);
    }

    /**
     * Cancel a pending verification.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('id', $id)
            ->whereIn('status', [
                IdentityVerification::STATUS_PENDING,
                IdentityVerification::STATUS_AWAITING_INPUT,
            ])
            ->first();

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found or cannot be cancelled.',
            ], 404);
        }

        $verification->cancel();

        // Update worker profile
        $user->workerProfile?->update([
            'kyc_status' => 'not_started',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Verification cancelled.',
        ]);
    }

    /**
     * Get available document types for the user's jurisdiction.
     */
    public function getDocumentTypes(Request $request): JsonResponse
    {
        $user = Auth::user();
        $countryCode = $request->input('country', $user->workerProfile?->country ?? 'US');

        $documentTypes = \App\Models\VerificationDocument::getAcceptedTypesByJurisdiction($countryCode);

        $formattedTypes = [];
        foreach ($documentTypes as $type) {
            $formattedTypes[] = [
                'type' => $type,
                'name' => \App\Models\VerificationDocument::getDocumentTypeName($type),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'country_code' => $countryCode,
                'document_types' => $formattedTypes,
            ],
        ]);
    }

    /**
     * Create a verification check (after documents are submitted).
     */
    public function createCheck(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('id', $id)
            ->whereIn('status', [
                IdentityVerification::STATUS_PENDING,
                IdentityVerification::STATUS_AWAITING_INPUT,
            ])
            ->first();

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found or not in correct state.',
            ], 404);
        }

        $result = $this->verificationService->createVerificationCheck($verification);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification check created. We\'ll notify you when results are ready.',
            'data' => [
                'check_id' => $result['check_id'],
                'status' => $verification->fresh()->status,
            ],
        ]);
    }

    /**
     * Start a liveness check.
     */
    public function startLiveness(Request $request, int $verificationId): JsonResponse
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('id', $verificationId)
            ->first();

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => 'Verification not found.',
            ], 404);
        }

        // Only enhanced level requires active liveness
        $type = $verification->verification_level === 'enhanced'
            ? LivenessCheck::TYPE_ACTIVE
            : LivenessCheck::TYPE_PASSIVE;

        $result = $this->livenessService->createLivenessCheck($verification, $type);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'liveness_check_id' => $result['liveness_check_id'],
                'session_token' => $result['session_token'],
                'type' => $result['type'],
                'challenges' => $result['challenges'] ?? [],
                'timeout_minutes' => $result['timeout_minutes'] ?? 15,
            ],
        ]);
    }

    /**
     * Submit liveness check data.
     */
    public function submitLiveness(Request $request, int $verificationId, int $livenessCheckId): JsonResponse
    {
        $user = Auth::user();

        $livenessCheck = LivenessCheck::where('id', $livenessCheckId)
            ->where('user_id', $user->id)
            ->where('identity_verification_id', $verificationId)
            ->first();

        if (! $livenessCheck) {
            return response()->json([
                'success' => false,
                'message' => 'Liveness check not found.',
            ], 404);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'image' => 'required_without_all:video,frames|string',
            'video' => 'required_without_all:image,frames|string',
            'frames' => 'sometimes|array',
            'frames.*' => 'string',
            'challenge_responses' => 'sometimes|array',
            'challenge_responses.*.completed' => 'boolean',
            'challenge_responses.*.confidence' => 'numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->livenessService->performLivenessCheck($livenessCheck, $request->all());

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
                'passed' => $result['passed'],
                'liveness_score' => $result['liveness_score'],
                'status' => $result['status'],
                'spoofing_detected' => $result['spoofing_detected'] ?? false,
            ],
        ]);
    }

    /**
     * Get liveness check status.
     */
    public function getLivenessStatus(Request $request, int $verificationId, int $livenessCheckId): JsonResponse
    {
        $user = Auth::user();

        $livenessCheck = LivenessCheck::where('id', $livenessCheckId)
            ->where('user_id', $user->id)
            ->where('identity_verification_id', $verificationId)
            ->first();

        if (! $livenessCheck) {
            return response()->json([
                'success' => false,
                'message' => 'Liveness check not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->livenessService->getLivenessCheckStatus($livenessCheck),
        ]);
    }

    /**
     * Get verification history for the user.
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();

        $verifications = IdentityVerification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $history = $verifications->map(fn ($v) => $this->formatVerificationResponse($v));

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Format verification for API response.
     */
    protected function formatVerificationResponse(IdentityVerification $verification): array
    {
        return [
            'id' => $verification->id,
            'status' => $verification->status,
            'status_label' => $this->getStatusLabel($verification->status),
            'verification_level' => $verification->verification_level,
            'provider' => $verification->provider,
            'result' => $verification->result,
            'rejection_reason' => $verification->rejection_reason,
            'attempt_count' => $verification->attempt_count,
            'max_attempts' => $verification->max_attempts,
            'can_retry' => $verification->canRetry(),
            'expires_at' => $verification->expires_at?->toIso8601String(),
            'created_at' => $verification->created_at->toIso8601String(),
            'reviewed_at' => $verification->reviewed_at?->toIso8601String(),

            // Face match data
            'face_match_performed' => $verification->face_match_performed,
            'face_match_result' => $verification->face_match_result,

            // SDK info (only for pending verifications)
            'has_valid_sdk_token' => $verification->isPending() && $verification->haValidSdkToken(),

            // Documents summary
            'documents_count' => $verification->documents()->count(),

            // Liveness checks summary
            'liveness_checks_count' => $verification->livenessChecks()->count(),
            'liveness_passed' => $verification->livenessChecks()->where('status', 'passed')->exists(),
        ];
    }

    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            IdentityVerification::STATUS_PENDING => 'Pending',
            IdentityVerification::STATUS_AWAITING_INPUT => 'Awaiting Documents',
            IdentityVerification::STATUS_PROCESSING => 'Processing',
            IdentityVerification::STATUS_MANUAL_REVIEW => 'Under Review',
            IdentityVerification::STATUS_APPROVED => 'Verified',
            IdentityVerification::STATUS_REJECTED => 'Rejected',
            IdentityVerification::STATUS_EXPIRED => 'Expired',
            IdentityVerification::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }
}
