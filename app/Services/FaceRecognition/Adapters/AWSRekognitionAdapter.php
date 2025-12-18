<?php

namespace App\Services\FaceRecognition\Adapters;

use App\Services\FaceRecognition\FaceRecognitionAdapterInterface;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;

/**
 * SL-005: AWS Rekognition Face Recognition Adapter
 *
 * Primary face recognition provider using AWS Rekognition.
 */
class AWSRekognitionAdapter implements FaceRecognitionAdapterInterface
{
    protected ?RekognitionClient $client = null;

    protected string $collectionId;

    protected float $similarityThreshold;

    public function __construct()
    {
        $this->collectionId = config('face_recognition.aws.collection_id', 'overtimestaff-faces');
        $this->similarityThreshold = config('face_recognition.min_confidence', 85.0);
    }

    /**
     * Get the Rekognition client.
     */
    protected function getClient(): RekognitionClient
    {
        if ($this->client === null) {
            $this->client = new RekognitionClient([
                'version' => 'latest',
                'region' => config('face_recognition.aws.region', config('services.aws.region', 'us-east-1')),
                'credentials' => [
                    'key' => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
            ]);
        }

        return $this->client;
    }

    /**
     * Decode image data for AWS.
     *
     * @return array{Bytes?: string, S3Object?: array}
     */
    protected function prepareImage(string $imageData): array
    {
        // If it's a URL, check if it's an S3 URL
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            if (str_contains($imageData, 's3.amazonaws.com') || str_contains($imageData, 's3://')) {
                // Parse S3 URL
                preg_match('/s3\.(.+)\.amazonaws\.com\/(.+?)\/(.+)/', $imageData, $matches);
                if ($matches) {
                    return [
                        'S3Object' => [
                            'Bucket' => $matches[2],
                            'Name' => $matches[3],
                        ],
                    ];
                }
            }

            // Download and convert to bytes
            $imageData = file_get_contents($imageData);

            return ['Bytes' => $imageData];
        }

        // Base64 encoded image
        if (str_starts_with($imageData, 'data:image')) {
            $imageData = explode(',', $imageData)[1];
        }

        return ['Bytes' => base64_decode($imageData)];
    }

    /**
     * Ensure the face collection exists.
     */
    protected function ensureCollectionExists(): void
    {
        try {
            $this->getClient()->describeCollection([
                'CollectionId' => $this->collectionId,
            ]);
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                $this->getClient()->createCollection([
                    'CollectionId' => $this->collectionId,
                ]);
                Log::info('Created AWS Rekognition collection', ['collection_id' => $this->collectionId]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function indexFace(string $imageData, string $externalId): array
    {
        try {
            $this->ensureCollectionExists();

            $result = $this->getClient()->indexFaces([
                'CollectionId' => $this->collectionId,
                'Image' => $this->prepareImage($imageData),
                'ExternalImageId' => $externalId,
                'MaxFaces' => 1,
                'QualityFilter' => 'AUTO',
                'DetectionAttributes' => ['ALL'],
            ]);

            $faceRecords = $result->get('FaceRecords') ?? [];

            if (empty($faceRecords)) {
                return [
                    'success' => false,
                    'face_id' => null,
                    'confidence' => null,
                    'attributes' => null,
                    'error' => 'No face detected in the image.',
                ];
            }

            $face = $faceRecords[0]['Face'];
            $faceDetail = $faceRecords[0]['FaceDetail'] ?? [];

            return [
                'success' => true,
                'face_id' => $face['FaceId'],
                'confidence' => $face['Confidence'],
                'attributes' => $this->extractAttributes($faceDetail),
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition indexFace failed', [
                'error' => $e->getMessage(),
                'external_id' => $externalId,
            ]);

            return [
                'success' => false,
                'face_id' => null,
                'confidence' => null,
                'attributes' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function compareFaces(string $sourceImage, string $faceId): array
    {
        try {
            $result = $this->getClient()->searchFacesByImage([
                'CollectionId' => $this->collectionId,
                'Image' => $this->prepareImage($sourceImage),
                'MaxFaces' => 1,
                'FaceMatchThreshold' => $this->similarityThreshold,
            ]);

            $matches = $result->get('FaceMatches') ?? [];

            if (empty($matches)) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => null,
                ];
            }

            // Check if the matched face is the one we're looking for
            foreach ($matches as $match) {
                if ($match['Face']['FaceId'] === $faceId) {
                    return [
                        'match' => true,
                        'confidence' => $match['Similarity'],
                        'error' => null,
                    ];
                }
            }

            // Face found but doesn't match the expected face ID
            return [
                'match' => false,
                'confidence' => $matches[0]['Similarity'] ?? 0,
                'error' => 'Face does not match the enrolled profile.',
            ];
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            if ($e->getAwsErrorCode() === 'InvalidParameterException') {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => 'No face detected in the verification image.',
                ];
            }

            Log::error('AWS Rekognition compareFaces failed', [
                'error' => $e->getMessage(),
                'face_id' => $faceId,
            ]);

            return [
                'match' => false,
                'confidence' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function compareFacesDirectly(string $sourceImage, string $targetImage): array
    {
        try {
            $result = $this->getClient()->compareFaces([
                'SourceImage' => $this->prepareImage($sourceImage),
                'TargetImage' => $this->prepareImage($targetImage),
                'SimilarityThreshold' => $this->similarityThreshold,
            ]);

            $matches = $result->get('FaceMatches') ?? [];

            if (empty($matches)) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => null,
                ];
            }

            $topMatch = $matches[0];

            return [
                'match' => $topMatch['Similarity'] >= $this->similarityThreshold,
                'confidence' => $topMatch['Similarity'],
                'error' => null,
            ];
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            if ($e->getAwsErrorCode() === 'InvalidParameterException') {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => 'No face detected in one or both images.',
                ];
            }

            Log::error('AWS Rekognition compareFacesDirectly failed', ['error' => $e->getMessage()]);

            return [
                'match' => false,
                'confidence' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detectFaces(string $imageData): array
    {
        try {
            $result = $this->getClient()->detectFaces([
                'Image' => $this->prepareImage($imageData),
                'Attributes' => ['ALL'],
            ]);

            $faceDetails = $result->get('FaceDetails') ?? [];
            $faces = [];

            foreach ($faceDetails as $face) {
                $faces[] = [
                    'confidence' => $face['Confidence'],
                    'bounding_box' => $face['BoundingBox'],
                    'attributes' => $this->extractAttributes($face),
                ];
            }

            return [
                'faces' => $faces,
                'face_count' => count($faces),
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition detectFaces failed', ['error' => $e->getMessage()]);

            return [
                'faces' => [],
                'face_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detectLiveness(string $imageData): array
    {
        try {
            // AWS Rekognition liveness detection requires a specific session-based flow
            // For single-image analysis, we use face quality metrics as a proxy
            $result = $this->getClient()->detectFaces([
                'Image' => $this->prepareImage($imageData),
                'Attributes' => ['ALL'],
            ]);

            $faceDetails = $result->get('FaceDetails') ?? [];

            if (empty($faceDetails)) {
                return [
                    'passed' => false,
                    'confidence' => 0,
                    'error' => 'No face detected.',
                ];
            }

            $face = $faceDetails[0];
            $quality = $face['Quality'] ?? [];

            // Calculate liveness score based on quality metrics
            $brightness = $quality['Brightness'] ?? 50;
            $sharpness = $quality['Sharpness'] ?? 50;
            $eyesOpen = ($face['EyesOpen']['Value'] ?? true) ? 100 : 0;
            $faceOccluded = ($face['FaceOccluded']['Value'] ?? false) ? 0 : 100;
            $sunglasses = ($face['Sunglasses']['Value'] ?? false) ? 0 : 100;

            // Weighted score calculation
            $livenessScore = (
                ($brightness * 0.15) +
                ($sharpness * 0.15) +
                ($eyesOpen * 0.30) +
                ($faceOccluded * 0.20) +
                ($sunglasses * 0.20)
            );

            // Require minimum 70% confidence for liveness
            $passed = $livenessScore >= 70;

            return [
                'passed' => $passed,
                'confidence' => $livenessScore,
                'error' => $passed ? null : 'Liveness check failed. Please ensure good lighting and face the camera directly.',
                'details' => [
                    'brightness' => $brightness,
                    'sharpness' => $sharpness,
                    'eyes_open' => $eyesOpen,
                    'face_occluded' => $faceOccluded,
                    'sunglasses' => $sunglasses,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition detectLiveness failed', ['error' => $e->getMessage()]);

            return [
                'passed' => false,
                'confidence' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFace(string $faceId): array
    {
        try {
            $this->getClient()->deleteFaces([
                'CollectionId' => $this->collectionId,
                'FaceIds' => [$faceId],
            ]);

            return [
                'success' => true,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('AWS Rekognition deleteFace failed', [
                'error' => $e->getMessage(),
                'face_id' => $faceId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderName(): string
    {
        return 'aws';
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        $key = config('services.aws.key');
        $secret = config('services.aws.secret');

        return ! empty($key) && ! empty($secret);
    }

    /**
     * Extract face attributes from detection result.
     *
     * @return array<string, mixed>
     */
    protected function extractAttributes(array $faceDetail): array
    {
        return [
            'age_range' => $faceDetail['AgeRange'] ?? null,
            'gender' => $faceDetail['Gender'] ?? null,
            'emotions' => $faceDetail['Emotions'] ?? [],
            'smile' => $faceDetail['Smile'] ?? null,
            'eyeglasses' => $faceDetail['Eyeglasses'] ?? null,
            'sunglasses' => $faceDetail['Sunglasses'] ?? null,
            'beard' => $faceDetail['Beard'] ?? null,
            'mustache' => $faceDetail['Mustache'] ?? null,
            'eyes_open' => $faceDetail['EyesOpen'] ?? null,
            'mouth_open' => $faceDetail['MouthOpen'] ?? null,
            'quality' => $faceDetail['Quality'] ?? null,
            'pose' => $faceDetail['Pose'] ?? null,
        ];
    }
}
