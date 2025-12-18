<?php

namespace App\Services;

use App\Models\FaceProfile;
use App\Models\FaceVerificationLog;
use App\Models\User;
use App\Services\FaceRecognition\Adapters\AWSRekognitionAdapter;
use App\Services\FaceRecognition\Adapters\AzureFaceAdapter;
use App\Services\FaceRecognition\FaceRecognitionAdapterInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * SL-005: Face Recognition Service for Clock-In/Out Verification
 *
 * Provides face enrollment and verification capabilities for shift attendance.
 */
class FaceRecognitionService
{
    protected ?FaceRecognitionAdapterInterface $adapter = null;

    protected ?FaceRecognitionAdapterInterface $fallbackAdapter = null;

    /**
     * Get the primary face recognition adapter.
     */
    protected function getAdapter(): FaceRecognitionAdapterInterface
    {
        if ($this->adapter === null) {
            $provider = config('face_recognition.provider', 'aws');
            $this->adapter = $this->createAdapter($provider);
        }

        return $this->adapter;
    }

    /**
     * Get the fallback face recognition adapter.
     */
    protected function getFallbackAdapter(): ?FaceRecognitionAdapterInterface
    {
        if ($this->fallbackAdapter === null) {
            $fallbackProvider = config('face_recognition.fallback_provider');
            if ($fallbackProvider) {
                $this->fallbackAdapter = $this->createAdapter($fallbackProvider);
            }
        }

        return $this->fallbackAdapter;
    }

    /**
     * Create an adapter instance for the given provider.
     */
    protected function createAdapter(string $provider): FaceRecognitionAdapterInterface
    {
        return match ($provider) {
            'aws' => new AWSRekognitionAdapter,
            'azure' => new AzureFaceAdapter,
            default => new AWSRekognitionAdapter,
        };
    }

    /**
     * Check if face recognition is enabled.
     */
    public function isEnabled(): bool
    {
        return config('face_recognition.enabled', true);
    }

    /**
     * Enroll a user's face for future verification.
     *
     * @param  string  $imageData  Base64 encoded image
     * @return array{success: bool, face_profile: ?FaceProfile, error: ?string}
     */
    public function enrollFace(User $user, string $imageData): array
    {
        $startTime = microtime(true);

        try {
            $adapter = $this->getAdapter();

            if (! $adapter->isAvailable()) {
                return $this->handleEnrollmentFallback($user, $imageData);
            }

            // Store the image first
            $imagePath = $this->storeImage($imageData, "enrollments/{$user->id}");

            // Index the face with the provider
            $result = $adapter->indexFace($imageData, (string) $user->id);

            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            if (! $result['success']) {
                // Log the failure
                FaceVerificationLog::logVerification(
                    user: $user,
                    action: FaceVerificationLog::ACTION_ENROLL,
                    provider: $adapter->getProviderName(),
                    matchResult: false,
                    failureReason: $result['error'],
                    processingTimeMs: $processingTime
                );

                // Try fallback if available
                return $this->handleEnrollmentWithFallback($user, $imageData, $result['error']);
            }

            // Create or update the face profile
            $faceProfile = FaceProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'face_id' => $result['face_id'],
                    'provider' => $adapter->getProviderName(),
                    'face_attributes' => $result['attributes'],
                    'is_enrolled' => true,
                    'enrolled_at' => now(),
                    'enrollment_image_url' => $imagePath,
                    'photo_count' => 1,
                    'status' => FaceProfile::STATUS_ACTIVE,
                    'collection_id' => config('face_recognition.aws.collection_id'),
                ]
            );

            // Log the successful enrollment
            FaceVerificationLog::logEnrollment(
                user: $user,
                provider: $adapter->getProviderName(),
                confidence: $result['confidence'],
                imageUrl: $imagePath,
                providerResponse: $result
            );

            return [
                'success' => true,
                'face_profile' => $faceProfile,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Face enrollment failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleEnrollmentWithFallback($user, $imageData, $e->getMessage());
        }
    }

    /**
     * Handle enrollment with fallback provider.
     *
     * @return array{success: bool, face_profile: ?FaceProfile, error: ?string}
     */
    protected function handleEnrollmentWithFallback(User $user, string $imageData, string $originalError): array
    {
        $fallback = $this->getFallbackAdapter();

        if ($fallback && $fallback->isAvailable()) {
            $result = $fallback->indexFace($imageData, (string) $user->id);

            if ($result['success']) {
                $imagePath = $this->storeImage($imageData, "enrollments/{$user->id}");

                $faceProfile = FaceProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'face_id' => $result['face_id'],
                        'provider' => $fallback->getProviderName(),
                        'face_attributes' => $result['attributes'],
                        'is_enrolled' => true,
                        'enrolled_at' => now(),
                        'enrollment_image_url' => $imagePath,
                        'photo_count' => 1,
                        'status' => FaceProfile::STATUS_ACTIVE,
                    ]
                );

                FaceVerificationLog::logEnrollment(
                    user: $user,
                    provider: $fallback->getProviderName(),
                    confidence: $result['confidence'],
                    imageUrl: $imagePath
                );

                return [
                    'success' => true,
                    'face_profile' => $faceProfile,
                    'error' => null,
                ];
            }
        }

        // If fallback to manual is allowed
        if (config('face_recognition.fallback_to_manual', true)) {
            return $this->handleEnrollmentFallback($user, $imageData);
        }

        return [
            'success' => false,
            'face_profile' => null,
            'error' => $originalError,
        ];
    }

    /**
     * Handle enrollment fallback when face recognition is unavailable.
     *
     * @return array{success: bool, face_profile: ?FaceProfile, error: ?string}
     */
    protected function handleEnrollmentFallback(User $user, string $imageData): array
    {
        // Store image for manual verification
        $imagePath = $this->storeImage($imageData, "enrollments/{$user->id}");

        $faceProfile = FaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => 'manual',
                'is_enrolled' => false,
                'enrollment_image_url' => $imagePath,
                'photo_count' => 1,
                'status' => FaceProfile::STATUS_PENDING,
                'notes' => 'Pending manual verification - face recognition service unavailable.',
            ]
        );

        return [
            'success' => true,
            'face_profile' => $faceProfile,
            'error' => null,
            'requires_manual_approval' => true,
        ];
    }

    /**
     * Verify a face against an enrolled profile.
     *
     * @param  string  $imageData  Base64 encoded image
     * @param  int|null  $shiftId  Associated shift ID
     * @param  int|null  $assignmentId  Associated assignment ID
     * @param  float|null  $latitude  GPS latitude
     * @param  float|null  $longitude  GPS longitude
     * @return array{match: bool, confidence: float, liveness: bool, error: ?string}
     */
    public function verifyFace(
        User $user,
        string $imageData,
        ?int $shiftId = null,
        ?int $assignmentId = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): array {
        $startTime = microtime(true);
        $minConfidence = config('face_recognition.min_confidence', 85.0);
        $requireLiveness = config('face_recognition.require_liveness', true);

        try {
            // Get user's face profile
            $faceProfile = FaceProfile::where('user_id', $user->id)
                ->where('status', FaceProfile::STATUS_ACTIVE)
                ->first();

            if (! $faceProfile) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'liveness' => false,
                    'error' => 'No enrolled face profile found. Please complete face enrollment first.',
                ];
            }

            // Check if face recognition is enabled and adapter is available
            $adapter = $this->getAdapter();

            if (! $this->isEnabled() || ! $adapter->isAvailable()) {
                return $this->handleVerificationFallback(
                    $user, $imageData, $shiftId, $assignmentId, $latitude, $longitude
                );
            }

            // First perform liveness detection if required
            $livenessResult = ['passed' => true, 'confidence' => 100];
            if ($requireLiveness) {
                $livenessResult = $adapter->detectLiveness($imageData);

                if (! $livenessResult['passed']) {
                    $processingTime = (int) ((microtime(true) - $startTime) * 1000);

                    FaceVerificationLog::logVerification(
                        user: $user,
                        action: FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
                        provider: $adapter->getProviderName(),
                        matchResult: false,
                        livenessPassed: false,
                        shiftId: $shiftId,
                        assignmentId: $assignmentId,
                        failureReason: 'Liveness check failed',
                        processingTimeMs: $processingTime,
                        latitude: $latitude,
                        longitude: $longitude
                    );

                    return [
                        'match' => false,
                        'confidence' => 0,
                        'liveness' => false,
                        'error' => $livenessResult['error'] ?? 'Liveness verification failed. Please ensure you are taking a live photo.',
                    ];
                }
            }

            // Now perform face comparison
            $compareResult = $adapter->compareFaces($imageData, $faceProfile->face_id);
            $processingTime = (int) ((microtime(true) - $startTime) * 1000);

            // Store the verification image
            $imagePath = $this->storeImage($imageData, "verifications/{$user->id}/".date('Y-m-d'));

            $matchSuccess = $compareResult['match'] && $compareResult['confidence'] >= $minConfidence;

            // Log the verification attempt
            FaceVerificationLog::logVerification(
                user: $user,
                action: FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
                provider: $adapter->getProviderName(),
                matchResult: $matchSuccess,
                confidence: $compareResult['confidence'],
                livenessPassed: $livenessResult['passed'],
                shiftId: $shiftId,
                assignmentId: $assignmentId,
                imageUrl: $imagePath,
                failureReason: $matchSuccess ? null : ($compareResult['error'] ?? 'Confidence below threshold'),
                processingTimeMs: $processingTime,
                latitude: $latitude,
                longitude: $longitude
            );

            // Update face profile statistics
            if ($matchSuccess) {
                $faceProfile->recordVerification($compareResult['confidence'], true);
            }

            if (! $matchSuccess && config('face_recognition.fallback_to_manual', true)) {
                return [
                    'match' => false,
                    'confidence' => $compareResult['confidence'],
                    'liveness' => $livenessResult['passed'],
                    'error' => 'Face verification failed. Manual verification may be required.',
                    'allow_manual_override' => true,
                ];
            }

            return [
                'match' => $matchSuccess,
                'confidence' => $compareResult['confidence'],
                'liveness' => $livenessResult['passed'],
                'error' => $matchSuccess ? null : ($compareResult['error'] ?? 'Face verification failed.'),
            ];
        } catch (\Exception $e) {
            Log::error('Face verification failed', [
                'user_id' => $user->id,
                'shift_id' => $shiftId,
                'error' => $e->getMessage(),
            ]);

            return $this->handleVerificationFallback(
                $user, $imageData, $shiftId, $assignmentId, $latitude, $longitude, $e->getMessage()
            );
        }
    }

    /**
     * Handle verification fallback when service is unavailable.
     *
     * @return array{match: bool, confidence: float, liveness: bool, error: ?string, allow_manual_override?: bool}
     */
    protected function handleVerificationFallback(
        User $user,
        string $imageData,
        ?int $shiftId,
        ?int $assignmentId,
        ?float $latitude,
        ?float $longitude,
        ?string $originalError = null
    ): array {
        // Store the image for manual review
        $imagePath = $this->storeImage($imageData, "verifications/{$user->id}/".date('Y-m-d'));

        FaceVerificationLog::logVerification(
            user: $user,
            action: FaceVerificationLog::ACTION_VERIFY_CLOCK_IN,
            provider: 'manual_fallback',
            matchResult: false,
            shiftId: $shiftId,
            assignmentId: $assignmentId,
            imageUrl: $imagePath,
            failureReason: $originalError ?? 'Face recognition service unavailable',
            latitude: $latitude,
            longitude: $longitude
        );

        if (config('face_recognition.fallback_to_manual', true)) {
            return [
                'match' => false,
                'confidence' => 0,
                'liveness' => false,
                'error' => 'Face recognition service unavailable. Manual verification required.',
                'allow_manual_override' => true,
                'verification_image' => $imagePath,
            ];
        }

        return [
            'match' => false,
            'confidence' => 0,
            'liveness' => false,
            'error' => $originalError ?? 'Face recognition service unavailable.',
        ];
    }

    /**
     * Compare two faces directly.
     *
     * @return float Similarity confidence (0-100)
     */
    public function compareFaces(string $sourceImage, string $targetImage): float
    {
        try {
            $adapter = $this->getAdapter();

            if (! $adapter->isAvailable()) {
                return 0;
            }

            $result = $adapter->compareFacesDirectly($sourceImage, $targetImage);

            return $result['confidence'] ?? 0;
        } catch (\Exception $e) {
            Log::error('Face comparison failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Detect liveness in an image.
     */
    public function detectLiveness(string $imageData): bool
    {
        try {
            $adapter = $this->getAdapter();

            if (! $adapter->isAvailable()) {
                return true; // Allow if service unavailable
            }

            $result = $adapter->detectLiveness($imageData);

            return $result['passed'] ?? false;
        } catch (\Exception $e) {
            Log::error('Liveness detection failed', ['error' => $e->getMessage()]);

            return true; // Allow if service fails
        }
    }

    /**
     * Delete a user's face profile.
     */
    public function deleteFaceProfile(User $user): void
    {
        $faceProfile = FaceProfile::where('user_id', $user->id)->first();

        if (! $faceProfile) {
            return;
        }

        // Delete from provider if enrolled
        if ($faceProfile->face_id) {
            try {
                $adapter = $this->createAdapter($faceProfile->provider);
                if ($adapter->isAvailable()) {
                    $adapter->deleteFace($faceProfile->face_id);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to delete face from provider', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Soft delete the profile
        $faceProfile->softDelete();
    }

    /**
     * Get enrollment status for a user.
     *
     * @return array{enrolled: bool, status: string, enrolled_at: ?string, verification_count: int, last_verified: ?string}
     */
    public function getEnrollmentStatus(User $user): array
    {
        $faceProfile = FaceProfile::where('user_id', $user->id)->first();

        if (! $faceProfile) {
            return [
                'enrolled' => false,
                'status' => 'not_started',
                'enrolled_at' => null,
                'verification_count' => 0,
                'last_verified' => null,
                'provider' => null,
            ];
        }

        return [
            'enrolled' => $faceProfile->is_enrolled,
            'status' => $faceProfile->status,
            'enrolled_at' => $faceProfile->enrolled_at?->toIso8601String(),
            'verification_count' => $faceProfile->verification_count,
            'last_verified' => $faceProfile->last_verified_at?->toIso8601String(),
            'provider' => $faceProfile->provider,
            'avg_confidence' => $faceProfile->avg_confidence,
        ];
    }

    /**
     * Perform manual verification override (admin only).
     */
    public function manualVerificationOverride(
        User $user,
        User $approver,
        ?int $shiftId = null,
        ?int $assignmentId = null,
        ?string $reason = null
    ): bool {
        FaceVerificationLog::logManualOverride(
            user: $user,
            approver: $approver,
            shiftId: $shiftId,
            assignmentId: $assignmentId,
            reason: $reason
        );

        return true;
    }

    /**
     * Store an image in cloud storage.
     */
    protected function storeImage(string $imageData, string $path): string
    {
        // Decode base64 if needed
        if (str_starts_with($imageData, 'data:image')) {
            $imageData = explode(',', $imageData)[1];
        }

        $imageContent = base64_decode($imageData);
        $extension = $this->detectImageExtension($imageContent);
        $filename = $path.'/'.uniqid().'.'.$extension;

        Storage::disk('public')->put($filename, $imageContent);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Detect image extension from binary content.
     */
    protected function detectImageExtension(string $content): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);

        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * Get verification statistics for a user.
     *
     * @return array<string, mixed>
     */
    public function getVerificationStats(User $user): array
    {
        return FaceVerificationLog::getStatsForUser($user);
    }
}
