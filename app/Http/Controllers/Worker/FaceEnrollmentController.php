<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\FaceVerificationLog;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * SL-005: Face Enrollment Controller
 *
 * Handles face enrollment for workers to enable clock-in/out verification.
 */
class FaceEnrollmentController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceRecognitionService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show the face enrollment page.
     */
    public function index(): View
    {
        $this->authorizeWorker();

        $user = Auth::user();
        $faceProfile = FaceProfile::where('user_id', $user->id)->first();
        $enrollmentStatus = $this->faceRecognitionService->getEnrollmentStatus($user);
        $verificationStats = $this->faceRecognitionService->getVerificationStats($user);

        return view('worker.face-enrollment.index', compact(
            'faceProfile',
            'enrollmentStatus',
            'verificationStats'
        ));
    }

    /**
     * Show the enrollment form.
     */
    public function create(): View
    {
        $this->authorizeWorker();

        $user = Auth::user();
        $existingProfile = FaceProfile::where('user_id', $user->id)->first();

        if ($existingProfile && $existingProfile->is_enrolled) {
            return redirect()->route('worker.face-enrollment.index')
                ->with('info', 'You are already enrolled. You can update your enrollment if needed.');
        }

        return view('worker.face-enrollment.create');
    }

    /**
     * Process face enrollment.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'face_image' => 'required|string', // Base64 encoded image
        ], [
            'face_image.required' => 'Please capture a photo of your face.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Check if already enrolled
        $existingProfile = FaceProfile::where('user_id', $user->id)
            ->where('is_enrolled', true)
            ->first();

        if ($existingProfile) {
            return response()->json([
                'success' => false,
                'error' => 'You are already enrolled. Please use the update endpoint to modify your enrollment.',
            ], 400);
        }

        // Perform enrollment
        $result = $this->faceRecognitionService->enrollFace($user, $request->face_image);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Enrollment failed. Please try again with better lighting.',
            ], 422);
        }

        $faceProfile = $result['face_profile'];

        return response()->json([
            'success' => true,
            'message' => $faceProfile->is_enrolled
                ? 'Face enrolled successfully! You can now use face verification for clock-in/out.'
                : 'Photo submitted for review. You will be notified once approved.',
            'enrolled' => $faceProfile->is_enrolled,
            'requires_approval' => $result['requires_manual_approval'] ?? false,
        ]);
    }

    /**
     * Update existing enrollment (re-enroll).
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'face_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Delete existing enrollment first
        $this->faceRecognitionService->deleteFaceProfile($user);

        // Re-enroll
        $result = $this->faceRecognitionService->enrollFace($user, $request->face_image);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Re-enrollment failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Face enrollment updated successfully!',
            'enrolled' => $result['face_profile']->is_enrolled,
        ]);
    }

    /**
     * Delete face enrollment.
     */
    public function destroy(): JsonResponse
    {
        $this->authorizeWorker();

        $user = Auth::user();

        $this->faceRecognitionService->deleteFaceProfile($user);

        return response()->json([
            'success' => true,
            'message' => 'Face enrollment removed successfully.',
        ]);
    }

    /**
     * Test face verification (for enrolled users).
     */
    public function testVerification(Request $request): JsonResponse
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'face_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // Check enrollment status
        $enrollmentStatus = $this->faceRecognitionService->getEnrollmentStatus($user);

        if (! $enrollmentStatus['enrolled']) {
            return response()->json([
                'success' => false,
                'error' => 'Please complete face enrollment first.',
            ], 400);
        }

        // Perform verification
        $result = $this->faceRecognitionService->verifyFace($user, $request->face_image);

        return response()->json([
            'success' => $result['match'],
            'match' => $result['match'],
            'confidence' => round($result['confidence'], 2),
            'liveness_passed' => $result['liveness'],
            'message' => $result['match']
                ? 'Verification successful! Confidence: '.round($result['confidence'], 2).'%'
                : ($result['error'] ?? 'Verification failed. Please try again.'),
            'allow_retry' => ! $result['match'],
        ]);
    }

    /**
     * Get enrollment status as JSON.
     */
    public function status(): JsonResponse
    {
        $this->authorizeWorker();

        $user = Auth::user();
        $status = $this->faceRecognitionService->getEnrollmentStatus($user);
        $stats = $this->faceRecognitionService->getVerificationStats($user);

        return response()->json([
            'success' => true,
            'enrollment' => $status,
            'statistics' => $stats,
            'face_recognition_enabled' => $this->faceRecognitionService->isEnabled(),
        ]);
    }

    /**
     * Get verification history.
     */
    public function history(Request $request): JsonResponse
    {
        $this->authorizeWorker();

        $user = Auth::user();

        $logs = FaceVerificationLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 20))
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => FaceVerificationLog::getActionTypes()[$log->action] ?? $log->action,
                    'provider' => $log->provider,
                    'match_result' => $log->match_result,
                    'confidence' => $log->confidence_score,
                    'confidence_level' => $log->confidence_level,
                    'liveness_passed' => $log->liveness_passed,
                    'fallback_used' => $log->fallback_used,
                    'failure_reason' => $log->failure_reason,
                    'created_at' => $log->created_at->toIso8601String(),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Add additional enrollment photo.
     */
    public function addPhoto(Request $request): JsonResponse
    {
        $this->authorizeWorker();

        $validator = Validator::make($request->all(), [
            'face_image' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $faceProfile = FaceProfile::where('user_id', $user->id)->first();

        if (! $faceProfile || ! $faceProfile->is_enrolled) {
            return response()->json([
                'success' => false,
                'error' => 'Please complete initial enrollment first.',
            ], 400);
        }

        $maxPhotos = config('face_recognition.enrollment.max_photo_count', 5);

        if ($faceProfile->photo_count >= $maxPhotos) {
            return response()->json([
                'success' => false,
                'error' => "Maximum of {$maxPhotos} photos allowed.",
            ], 400);
        }

        // Store the additional image
        $imagePath = $this->storeImage($request->face_image, "enrollments/{$user->id}/additional");
        $faceProfile->addEnrollmentImage($imagePath);

        return response()->json([
            'success' => true,
            'message' => 'Additional photo added successfully.',
            'photo_count' => $faceProfile->fresh()->photo_count,
        ]);
    }

    /**
     * Store an image and return the path.
     */
    protected function storeImage(string $imageData, string $path): string
    {
        if (str_starts_with($imageData, 'data:image')) {
            $imageData = explode(',', $imageData)[1];
        }

        $imageContent = base64_decode($imageData);
        $filename = $path.'/'.uniqid().'.jpg';

        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imageContent);

        return \Illuminate\Support\Facades\Storage::disk('public')->url($filename);
    }

    /**
     * Authorize that the current user is a worker.
     */
    protected function authorizeWorker(): void
    {
        if (! Auth::user()->isWorker()) {
            abort(403, 'Only workers can access face enrollment.');
        }
    }
}
