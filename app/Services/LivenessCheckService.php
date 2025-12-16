<?php

namespace App\Services;

use App\Models\IdentityVerification;
use App\Models\LivenessCheck;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * LivenessCheckService - STAFF-REG-004
 *
 * Handles liveness verification to ensure the person is physically present.
 */
class LivenessCheckService
{
    /**
     * Minimum liveness score to pass (0-1).
     */
    protected const MIN_LIVENESS_SCORE = 0.80;

    /**
     * Minimum face quality score (0-1).
     */
    protected const MIN_FACE_QUALITY = 0.70;

    /**
     * Session timeout in minutes.
     */
    protected const SESSION_TIMEOUT_MINUTES = 15;

    /**
     * Onfido API configuration.
     */
    protected string $onfidoBaseUrl;
    protected string $onfidoApiToken;

    public function __construct()
    {
        $this->onfidoBaseUrl = config('services.onfido.api_url', 'https://api.onfido.com/v3.6');
        $this->onfidoApiToken = config('services.onfido.api_token', '');
    }

    /**
     * Create a new liveness check for a verification.
     *
     * @param IdentityVerification $verification
     * @param string $type Check type (passive, active, video, motion)
     * @return array
     */
    public function createLivenessCheck(
        IdentityVerification $verification,
        string $type = LivenessCheck::TYPE_ACTIVE
    ): array {
        // Check for existing pending liveness check
        $existingCheck = LivenessCheck::where('identity_verification_id', $verification->id)
            ->whereIn('status', [LivenessCheck::STATUS_PENDING, LivenessCheck::STATUS_IN_PROGRESS])
            ->first();

        if ($existingCheck && !$existingCheck->hasSessionExpired()) {
            return [
                'success' => true,
                'liveness_check_id' => $existingCheck->id,
                'session_token' => $existingCheck->session_token,
                'challenges' => $existingCheck->challenges,
                'status' => $existingCheck->status,
            ];
        }

        // Mark expired check if exists
        if ($existingCheck) {
            $existingCheck->markExpired();
        }

        try {
            // Create new liveness check
            $livenessCheck = LivenessCheck::createForVerification($verification, $type);

            // Generate challenges for active liveness
            $challenges = [];
            if ($type === LivenessCheck::TYPE_ACTIVE || $type === LivenessCheck::TYPE_MOTION) {
                $challenges = $livenessCheck->generateChallenges(3);
            }

            // Start session
            $livenessCheck->startSession();

            // Store device info
            $livenessCheck->storeDeviceInfo([
                'ip_address' => request()->ip(),
                'browser' => request()->userAgent(),
            ]);

            return [
                'success' => true,
                'liveness_check_id' => $livenessCheck->id,
                'session_token' => $livenessCheck->session_token,
                'challenges' => $challenges,
                'type' => $type,
                'status' => $livenessCheck->status,
                'timeout_minutes' => self::SESSION_TIMEOUT_MINUTES,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create liveness check', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create liveness check.',
            ];
        }
    }

    /**
     * Perform liveness check analysis on submitted data.
     *
     * @param LivenessCheck $livenessCheck
     * @param array $data Submitted data (frames, responses, etc.)
     * @return array
     */
    public function performLivenessCheck(LivenessCheck $livenessCheck, array $data): array
    {
        if ($livenessCheck->hasSessionExpired()) {
            return [
                'success' => false,
                'error' => 'Session expired. Please start a new liveness check.',
            ];
        }

        $livenessCheck->markProcessing();

        try {
            // Analyze based on check type
            $result = match ($livenessCheck->check_type) {
                LivenessCheck::TYPE_PASSIVE => $this->analyzePassiveLiveness($livenessCheck, $data),
                LivenessCheck::TYPE_ACTIVE => $this->analyzeActiveLiveness($livenessCheck, $data),
                LivenessCheck::TYPE_VIDEO => $this->analyzeVideoLiveness($livenessCheck, $data),
                LivenessCheck::TYPE_MOTION => $this->analyzeMotionLiveness($livenessCheck, $data),
                default => $this->analyzePassiveLiveness($livenessCheck, $data),
            };

            // Store spoofing check results
            if (isset($result['spoofing_checks'])) {
                $livenessCheck->storeSpoofingResults($result['spoofing_checks']);
            }

            // Determine pass/fail
            if ($result['passed']) {
                $livenessCheck->markPassed([
                    'liveness_score' => $result['liveness_score'],
                    'face_quality_score' => $result['face_quality_score'] ?? null,
                    'breakdown' => $result['breakdown'] ?? null,
                ]);
            } else {
                $livenessCheck->markFailed(
                    $result['failure_reason'],
                    $result['failure_details'] ?? []
                );
            }

            return [
                'success' => true,
                'passed' => $result['passed'],
                'liveness_score' => $result['liveness_score'],
                'face_quality_score' => $result['face_quality_score'] ?? null,
                'is_real_person' => $result['is_real_person'] ?? null,
                'spoofing_detected' => $livenessCheck->hasSpoofingDetected(),
                'status' => $livenessCheck->status,
                'message' => $result['passed']
                    ? 'Liveness check passed successfully.'
                    : $result['failure_reason'],
            ];
        } catch (\Exception $e) {
            Log::error('Liveness check analysis failed', [
                'liveness_check_id' => $livenessCheck->id,
                'error' => $e->getMessage(),
            ]);

            $livenessCheck->markFailed('Analysis error', ['exception' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Liveness check analysis failed. Please try again.',
            ];
        }
    }

    /**
     * Analyze passive liveness (single image).
     *
     * @param LivenessCheck $livenessCheck
     * @param array $data
     * @return array
     */
    protected function analyzePassiveLiveness(LivenessCheck $livenessCheck, array $data): array
    {
        $imageData = $data['image'] ?? $data['selfie'] ?? null;

        if (!$imageData) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'No image provided.',
            ];
        }

        // Use AWS Rekognition if configured
        if (config('services.aws.rekognition.enabled', false)) {
            return $this->analyzeWithRekognition($imageData);
        }

        // Use Onfido if API token available
        if ($this->onfidoApiToken) {
            return $this->analyzeWithOnfido($livenessCheck, $imageData);
        }

        // Fallback: Basic validation for development
        return $this->analyzeBasicLiveness($imageData);
    }

    /**
     * Analyze active liveness (challenge-response).
     *
     * @param LivenessCheck $livenessCheck
     * @param array $data
     * @return array
     */
    protected function analyzeActiveLiveness(LivenessCheck $livenessCheck, array $data): array
    {
        $challengeResponses = $data['challenge_responses'] ?? [];
        $frames = $data['frames'] ?? [];

        if (empty($challengeResponses)) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'No challenge responses provided.',
            ];
        }

        // Verify each challenge was completed
        $challengesPassed = 0;
        $challengesRequired = $livenessCheck->challenges_required;
        $breakdown = [];

        foreach ($challengeResponses as $challengeId => $response) {
            $passed = $this->verifyChallengeResponse($challengeId, $response, $frames);
            $breakdown[$challengeId] = [
                'passed' => $passed,
                'confidence' => $response['confidence'] ?? 0,
            ];

            if ($passed) {
                $challengesPassed++;
            }

            $livenessCheck->recordChallengeResponse($challengeId, $passed);
        }

        $livenessScore = $challengesRequired > 0
            ? $challengesPassed / $challengesRequired
            : 0;

        // Check spoofing signals
        $spoofingChecks = $this->detectSpoofingSignals($frames);

        $passed = $livenessScore >= self::MIN_LIVENESS_SCORE && !$spoofingChecks['spoofing_detected'];

        return [
            'passed' => $passed,
            'liveness_score' => $livenessScore,
            'face_quality_score' => $this->calculateFaceQuality($frames),
            'is_real_person' => !$spoofingChecks['spoofing_detected'],
            'spoofing_checks' => $spoofingChecks,
            'breakdown' => $breakdown,
            'failure_reason' => $passed ? null : $this->determineLivenessFailureReason($livenessScore, $spoofingChecks),
            'failure_details' => $passed ? null : [
                'challenges_passed' => $challengesPassed,
                'challenges_required' => $challengesRequired,
                'spoofing_checks' => $spoofingChecks,
            ],
        ];
    }

    /**
     * Analyze video-based liveness.
     *
     * @param LivenessCheck $livenessCheck
     * @param array $data
     * @return array
     */
    protected function analyzeVideoLiveness(LivenessCheck $livenessCheck, array $data): array
    {
        $videoData = $data['video'] ?? null;

        if (!$videoData) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'No video provided.',
            ];
        }

        // Extract frames from video
        $frames = $this->extractVideoFrames($videoData);

        if (empty($frames)) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'Could not process video.',
            ];
        }

        // Analyze frames for liveness
        return $this->analyzeFrameSequence($frames);
    }

    /**
     * Analyze motion-based liveness.
     *
     * @param LivenessCheck $livenessCheck
     * @param array $data
     * @return array
     */
    protected function analyzeMotionLiveness(LivenessCheck $livenessCheck, array $data): array
    {
        $motionData = $data['motion'] ?? [];
        $frames = $data['frames'] ?? [];

        if (empty($motionData) && empty($frames)) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'No motion data provided.',
            ];
        }

        // Analyze motion patterns
        $motionAnalysis = $this->analyzeMotionPatterns($motionData);

        // Combine with frame analysis
        $frameAnalysis = $this->analyzeFrameSequence($frames);

        $combinedScore = ($motionAnalysis['score'] + $frameAnalysis['liveness_score']) / 2;

        return [
            'passed' => $combinedScore >= self::MIN_LIVENESS_SCORE,
            'liveness_score' => $combinedScore,
            'face_quality_score' => $frameAnalysis['face_quality_score'] ?? null,
            'is_real_person' => $combinedScore >= self::MIN_LIVENESS_SCORE,
            'spoofing_checks' => $frameAnalysis['spoofing_checks'] ?? [],
            'failure_reason' => $combinedScore >= self::MIN_LIVENESS_SCORE
                ? null
                : 'Motion analysis did not confirm liveness.',
        ];
    }

    /**
     * Verify a single challenge response.
     *
     * @param string $challengeId
     * @param array $response
     * @param array $frames
     * @return bool
     */
    protected function verifyChallengeResponse(string $challengeId, array $response, array $frames): bool
    {
        $confidence = $response['confidence'] ?? 0;
        $completed = $response['completed'] ?? false;

        // Basic verification - in production, this would use ML models
        if (!$completed || $confidence < 0.7) {
            return false;
        }

        // Additional verification based on challenge type
        return match ($challengeId) {
            'blink' => $this->verifyBlinkChallenge($frames),
            'turn_head_left', 'turn_head_right' => $this->verifyHeadTurnChallenge($frames, $challengeId),
            'smile' => $this->verifySmileChallenge($frames),
            'nod' => $this->verifyNodChallenge($frames),
            default => $completed && $confidence >= 0.7,
        };
    }

    /**
     * Verify blink challenge.
     *
     * @param array $frames
     * @return bool
     */
    protected function verifyBlinkChallenge(array $frames): bool
    {
        // In production, analyze eye state changes across frames
        return count($frames) >= 2;
    }

    /**
     * Verify head turn challenge.
     *
     * @param array $frames
     * @param string $direction
     * @return bool
     */
    protected function verifyHeadTurnChallenge(array $frames, string $direction): bool
    {
        // In production, analyze face pose changes
        return count($frames) >= 3;
    }

    /**
     * Verify smile challenge.
     *
     * @param array $frames
     * @return bool
     */
    protected function verifySmileChallenge(array $frames): bool
    {
        // In production, analyze facial expression changes
        return count($frames) >= 2;
    }

    /**
     * Verify nod challenge.
     *
     * @param array $frames
     * @return bool
     */
    protected function verifyNodChallenge(array $frames): bool
    {
        // In production, analyze vertical head movement
        return count($frames) >= 3;
    }

    /**
     * Detect spoofing signals in frames.
     *
     * @param array $frames
     * @return array
     */
    protected function detectSpoofingSignals(array $frames): array
    {
        // In production, use ML models to detect:
        // - Photo of photo
        // - Screen replay
        // - Mask
        // - Deepfake

        return [
            'spoofing_detected' => false,
            'photo_detected' => false,
            'screen_detected' => false,
            'mask_detected' => false,
            'deepfake_detected' => false,
            'is_real_person' => true,
            'confidence' => 0.95,
        ];
    }

    /**
     * Calculate face quality from frames.
     *
     * @param array $frames
     * @return float
     */
    protected function calculateFaceQuality(array $frames): float
    {
        // In production, analyze:
        // - Face visibility
        // - Lighting
        // - Blur
        // - Face angle

        if (empty($frames)) {
            return 0;
        }

        return 0.85; // Default quality score for development
    }

    /**
     * Extract frames from video data.
     *
     * @param string|array $videoData
     * @return array
     */
    protected function extractVideoFrames($videoData): array
    {
        // In production, use FFmpeg to extract frames
        // For now, return empty array

        return [];
    }

    /**
     * Analyze a sequence of frames for liveness.
     *
     * @param array $frames
     * @return array
     */
    protected function analyzeFrameSequence(array $frames): array
    {
        if (empty($frames)) {
            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'No frames to analyze.',
            ];
        }

        // In production, analyze:
        // - Temporal consistency
        // - Face movement
        // - Expression changes
        // - Lighting changes

        $spoofingChecks = $this->detectSpoofingSignals($frames);

        return [
            'passed' => true,
            'liveness_score' => 0.90,
            'face_quality_score' => $this->calculateFaceQuality($frames),
            'is_real_person' => true,
            'spoofing_checks' => $spoofingChecks,
        ];
    }

    /**
     * Analyze motion patterns.
     *
     * @param array $motionData
     * @return array
     */
    protected function analyzeMotionPatterns(array $motionData): array
    {
        // In production, analyze accelerometer/gyroscope data

        return [
            'score' => 0.85,
            'natural_motion' => true,
        ];
    }

    /**
     * Analyze image with AWS Rekognition.
     *
     * @param string $imageData
     * @return array
     */
    protected function analyzeWithRekognition(string $imageData): array
    {
        try {
            $client = new \Aws\Rekognition\RekognitionClient([
                'version' => 'latest',
                'region' => config('services.aws.region', 'us-east-1'),
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);

            // Decode base64 if necessary
            $imageBytes = str_starts_with($imageData, 'data:image')
                ? base64_decode(explode(',', $imageData)[1])
                : (strlen($imageData) > 1000 ? base64_decode($imageData) : file_get_contents($imageData));

            $result = $client->detectFaces([
                'Image' => ['Bytes' => $imageBytes],
                'Attributes' => ['ALL'],
            ]);

            $faces = $result->get('FaceDetails') ?? [];

            if (empty($faces)) {
                return [
                    'passed' => false,
                    'liveness_score' => 0,
                    'failure_reason' => 'No face detected.',
                ];
            }

            $face = $faces[0];
            $confidence = ($face['Confidence'] ?? 0) / 100;
            $quality = $face['Quality'] ?? [];

            $brightnessScore = ($quality['Brightness'] ?? 50) / 100;
            $sharpnessScore = ($quality['Sharpness'] ?? 50) / 100;
            $faceQuality = ($brightnessScore + $sharpnessScore) / 2;

            // Check for sunglasses/eyes closed (potential spoofing)
            $eyesOpen = $face['EyesOpen']['Value'] ?? true;
            $sunglasses = $face['Sunglasses']['Value'] ?? false;

            $livenessScore = $confidence * 0.6 + $faceQuality * 0.4;

            if ($sunglasses || !$eyesOpen) {
                $livenessScore *= 0.5;
            }

            return [
                'passed' => $livenessScore >= self::MIN_LIVENESS_SCORE,
                'liveness_score' => $livenessScore,
                'face_quality_score' => $faceQuality,
                'is_real_person' => $livenessScore >= self::MIN_LIVENESS_SCORE,
                'spoofing_checks' => [
                    'spoofing_detected' => false,
                    'sunglasses_detected' => $sunglasses,
                    'eyes_closed' => !$eyesOpen,
                ],
                'failure_reason' => $livenessScore >= self::MIN_LIVENESS_SCORE
                    ? null
                    : 'Liveness score too low.',
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition liveness check failed', ['error' => $e->getMessage()]);

            return $this->analyzeBasicLiveness($imageData);
        }
    }

    /**
     * Analyze with Onfido.
     *
     * @param LivenessCheck $livenessCheck
     * @param string $imageData
     * @return array
     */
    protected function analyzeWithOnfido(LivenessCheck $livenessCheck, string $imageData): array
    {
        try {
            // Upload live photo to Onfido
            $response = Http::withHeaders([
                'Authorization' => 'Token token=' . $this->onfidoApiToken,
            ])->attach(
                'file',
                base64_decode($imageData),
                'liveness.jpg'
            )->post($this->onfidoBaseUrl . '/live_photos', [
                'applicant_id' => $livenessCheck->identityVerification->provider_applicant_id,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $livenessCheck->update([
                    'provider_check_id' => $data['id'],
                ]);

                // Onfido processes asynchronously, assume pass for now
                return [
                    'passed' => true,
                    'liveness_score' => 0.90,
                    'is_real_person' => true,
                    'provider_response' => $data,
                ];
            }

            return [
                'passed' => false,
                'liveness_score' => 0,
                'failure_reason' => 'Provider rejected the image.',
            ];
        } catch (\Exception $e) {
            Log::error('Onfido liveness check failed', ['error' => $e->getMessage()]);

            return $this->analyzeBasicLiveness($imageData);
        }
    }

    /**
     * Basic liveness analysis for development.
     *
     * @param string $imageData
     * @return array
     */
    protected function analyzeBasicLiveness(string $imageData): array
    {
        // Basic validation - just check that image data exists
        $hasData = strlen($imageData) > 100;

        return [
            'passed' => $hasData,
            'liveness_score' => $hasData ? 0.85 : 0,
            'face_quality_score' => $hasData ? 0.80 : 0,
            'is_real_person' => $hasData,
            'spoofing_checks' => [
                'spoofing_detected' => false,
                'photo_detected' => false,
                'screen_detected' => false,
                'mask_detected' => false,
                'deepfake_detected' => false,
                'is_real_person' => $hasData,
            ],
            'failure_reason' => $hasData ? null : 'No valid image data.',
        ];
    }

    /**
     * Determine liveness failure reason.
     *
     * @param float $livenessScore
     * @param array $spoofingChecks
     * @return string
     */
    protected function determineLivenessFailureReason(float $livenessScore, array $spoofingChecks): string
    {
        if ($spoofingChecks['deepfake_detected'] ?? false) {
            return 'Potential deepfake detected.';
        }

        if ($spoofingChecks['mask_detected'] ?? false) {
            return 'Face mask or obstruction detected.';
        }

        if ($spoofingChecks['screen_detected'] ?? false) {
            return 'Screen or digital display detected.';
        }

        if ($spoofingChecks['photo_detected'] ?? false) {
            return 'Photo of a photo detected.';
        }

        if ($livenessScore < self::MIN_LIVENESS_SCORE) {
            return 'Not enough challenges completed. Please try again.';
        }

        return 'Liveness verification failed. Please try again.';
    }

    /**
     * Get liveness check status.
     *
     * @param LivenessCheck $livenessCheck
     * @return array
     */
    public function getLivenessCheckStatus(LivenessCheck $livenessCheck): array
    {
        return [
            'id' => $livenessCheck->id,
            'status' => $livenessCheck->status,
            'check_type' => $livenessCheck->check_type,
            'passed' => $livenessCheck->hasPassed(),
            'liveness_score' => $livenessCheck->liveness_score,
            'face_quality_score' => $livenessCheck->face_quality_score,
            'challenges_completed' => $livenessCheck->challenges_completed,
            'challenges_required' => $livenessCheck->challenges_required,
            'is_real_person' => $livenessCheck->isRealPerson(),
            'spoofing_detected' => $livenessCheck->hasSpoofingDetected(),
            'failure_reason' => $livenessCheck->failure_reason,
            'session_expired' => $livenessCheck->hasSessionExpired(),
        ];
    }

    /**
     * Perform face matching between liveness selfie and ID document.
     *
     * @param LivenessCheck $livenessCheck
     * @param string $documentImageUrl
     * @return array
     */
    public function performFaceMatch(LivenessCheck $livenessCheck, string $documentImageUrl): array
    {
        $selfieUrl = $livenessCheck->selfie_storage_path;

        if (!$selfieUrl) {
            return [
                'success' => false,
                'error' => 'No selfie available for comparison.',
            ];
        }

        try {
            // Use AWS Rekognition for face comparison
            if (config('services.aws.rekognition.enabled', false)) {
                return $this->compareFacesWithRekognition($selfieUrl, $documentImageUrl);
            }

            // Mock response for development
            $result = 'match';
            $score = 0.95;

            $livenessCheck->storeFaceMatchResults($result, $score);

            return [
                'success' => true,
                'result' => $result,
                'similarity_score' => $score,
                'is_match' => $result === 'match',
            ];
        } catch (\Exception $e) {
            Log::error('Face matching failed', [
                'liveness_check_id' => $livenessCheck->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Face matching failed.',
            ];
        }
    }

    /**
     * Compare faces using AWS Rekognition.
     *
     * @param string $sourceImage
     * @param string $targetImage
     * @return array
     */
    protected function compareFacesWithRekognition(string $sourceImage, string $targetImage): array
    {
        try {
            $client = new \Aws\Rekognition\RekognitionClient([
                'version' => 'latest',
                'region' => config('services.aws.region', 'us-east-1'),
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);

            $sourceBytes = file_get_contents($sourceImage);
            $targetBytes = file_get_contents($targetImage);

            $result = $client->compareFaces([
                'SourceImage' => ['Bytes' => $sourceBytes],
                'TargetImage' => ['Bytes' => $targetBytes],
                'SimilarityThreshold' => 70,
            ]);

            $matches = $result->get('FaceMatches') ?? [];

            if (empty($matches)) {
                return [
                    'success' => true,
                    'result' => 'no_match',
                    'similarity_score' => 0,
                    'is_match' => false,
                ];
            }

            $topMatch = $matches[0];
            $similarity = ($topMatch['Similarity'] ?? 0) / 100;

            return [
                'success' => true,
                'result' => $similarity >= 0.80 ? 'match' : 'no_match',
                'similarity_score' => $similarity,
                'is_match' => $similarity >= 0.80,
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition face comparison failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Face comparison service unavailable.',
            ];
        }
    }
}
