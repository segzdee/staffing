<?php

namespace App\Services\FaceRecognition\Adapters;

use App\Services\FaceRecognition\FaceRecognitionAdapterInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SL-005: Azure Face API Adapter
 *
 * Backup face recognition provider using Azure Face API.
 */
class AzureFaceAdapter implements FaceRecognitionAdapterInterface
{
    protected string $endpoint;

    protected string $apiKey;

    protected string $personGroupId;

    protected float $similarityThreshold;

    public function __construct()
    {
        $this->endpoint = config('face_recognition.azure.endpoint', '');
        $this->apiKey = config('face_recognition.azure.key', '');
        $this->personGroupId = config('face_recognition.azure.person_group_id', 'overtimestaff');
        $this->similarityThreshold = config('face_recognition.min_confidence', 85.0);
    }

    /**
     * Make an API request to Azure Face API.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function makeRequest(string $method, string $path, array $options = []): array
    {
        $url = rtrim($this->endpoint, '/').'/face/v1.0/'.ltrim($path, '/');

        $request = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
            'Content-Type' => $options['content_type'] ?? 'application/json',
        ])->timeout(30);

        try {
            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $options['query'] ?? []),
                'POST' => $request->post($url, $options['body'] ?? []),
                'PUT' => $request->put($url, $options['body'] ?? []),
                'DELETE' => $request->delete($url),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return [
                'success' => false,
                'error' => $response->json()['error']['message'] ?? 'Unknown error',
                'code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Prepare image for Azure API (binary).
     */
    protected function prepareImageBinary(string $imageData): string
    {
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            return file_get_contents($imageData);
        }

        if (str_starts_with($imageData, 'data:image')) {
            $imageData = explode(',', $imageData)[1];
        }

        return base64_decode($imageData);
    }

    /**
     * Detect faces and get face IDs.
     *
     * @return array<string, mixed>
     */
    protected function detectFaceId(string $imageData): array
    {
        $url = rtrim($this->endpoint, '/').'/face/v1.0/detect';

        try {
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Content-Type' => 'application/octet-stream',
            ])
                ->withBody($this->prepareImageBinary($imageData), 'application/octet-stream')
                ->withQueryParameters([
                    'returnFaceId' => 'true',
                    'returnFaceAttributes' => 'age,gender,glasses,emotion,blur,exposure,noise,occlusion,qualityForRecognition',
                    'recognitionModel' => 'recognition_04',
                    'detectionModel' => 'detection_03',
                ])
                ->post($url);

            if ($response->successful()) {
                $faces = $response->json();

                if (empty($faces)) {
                    return ['success' => false, 'error' => 'No face detected'];
                }

                return [
                    'success' => true,
                    'face_id' => $faces[0]['faceId'],
                    'attributes' => $faces[0]['faceAttributes'] ?? [],
                    'quality' => $faces[0]['faceAttributes']['qualityForRecognition'] ?? 'unknown',
                ];
            }

            return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Detection failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ensure the person group exists.
     */
    protected function ensurePersonGroupExists(): void
    {
        $result = $this->makeRequest('GET', "persongroups/{$this->personGroupId}");

        if (! $result['success'] && str_contains($result['error'] ?? '', 'PersonGroupNotFound')) {
            $this->makeRequest('PUT', "persongroups/{$this->personGroupId}", [
                'body' => [
                    'name' => 'OvertimeStaff Faces',
                    'recognitionModel' => 'recognition_04',
                ],
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function indexFace(string $imageData, string $externalId): array
    {
        try {
            $this->ensurePersonGroupExists();

            // Create a person
            $createResult = $this->makeRequest('POST', "persongroups/{$this->personGroupId}/persons", [
                'body' => [
                    'name' => $externalId,
                    'userData' => json_encode(['user_id' => $externalId]),
                ],
            ]);

            if (! $createResult['success']) {
                return [
                    'success' => false,
                    'face_id' => null,
                    'confidence' => null,
                    'attributes' => null,
                    'error' => $createResult['error'],
                ];
            }

            $personId = $createResult['data']['personId'];

            // Add face to person
            $url = rtrim($this->endpoint, '/')."/face/v1.0/persongroups/{$this->personGroupId}/persons/{$personId}/persistedFaces";

            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => $this->apiKey,
                'Content-Type' => 'application/octet-stream',
            ])
                ->withBody($this->prepareImageBinary($imageData), 'application/octet-stream')
                ->withQueryParameters(['detectionModel' => 'detection_03'])
                ->post($url);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'face_id' => null,
                    'confidence' => null,
                    'attributes' => null,
                    'error' => $response->json()['error']['message'] ?? 'Failed to add face',
                ];
            }

            $persistedFaceId = $response->json()['persistedFaceId'];

            // Train the person group
            $this->makeRequest('POST', "persongroups/{$this->personGroupId}/train");

            return [
                'success' => true,
                'face_id' => "{$personId}:{$persistedFaceId}",
                'confidence' => 100.0,
                'attributes' => [],
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Azure Face indexFace failed', ['error' => $e->getMessage()]);

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
            // Parse personId from face_id
            [$personId] = explode(':', $faceId);

            // Detect face in source image
            $detection = $this->detectFaceId($sourceImage);

            if (! $detection['success']) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => $detection['error'],
                ];
            }

            // Verify against person
            $result = $this->makeRequest('POST', 'verify', [
                'body' => [
                    'faceId' => $detection['face_id'],
                    'personId' => $personId,
                    'personGroupId' => $this->personGroupId,
                ],
            ]);

            if (! $result['success']) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => $result['error'],
                ];
            }

            $confidence = ($result['data']['confidence'] ?? 0) * 100;

            return [
                'match' => $result['data']['isIdentical'] ?? false,
                'confidence' => $confidence,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Azure Face compareFaces failed', ['error' => $e->getMessage()]);

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
            // Detect faces in both images
            $source = $this->detectFaceId($sourceImage);
            $target = $this->detectFaceId($targetImage);

            if (! $source['success'] || ! $target['success']) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => $source['error'] ?? $target['error'] ?? 'No face detected',
                ];
            }

            // Verify the two faces
            $result = $this->makeRequest('POST', 'verify', [
                'body' => [
                    'faceId1' => $source['face_id'],
                    'faceId2' => $target['face_id'],
                ],
            ]);

            if (! $result['success']) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'error' => $result['error'],
                ];
            }

            $confidence = ($result['data']['confidence'] ?? 0) * 100;

            return [
                'match' => $result['data']['isIdentical'] ?? false,
                'confidence' => $confidence,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('Azure Face compareFacesDirectly failed', ['error' => $e->getMessage()]);

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
        $detection = $this->detectFaceId($imageData);

        if (! $detection['success']) {
            return [
                'faces' => [],
                'face_count' => 0,
                'error' => $detection['error'],
            ];
        }

        return [
            'faces' => [[
                'face_id' => $detection['face_id'],
                'attributes' => $detection['attributes'],
                'quality' => $detection['quality'],
            ]],
            'face_count' => 1,
            'error' => null,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function detectLiveness(string $imageData): array
    {
        try {
            $detection = $this->detectFaceId($imageData);

            if (! $detection['success']) {
                return [
                    'passed' => false,
                    'confidence' => 0,
                    'error' => $detection['error'],
                ];
            }

            $attributes = $detection['attributes'] ?? [];

            // Calculate liveness score based on quality metrics
            $quality = $detection['quality'] ?? 'low';
            $blur = $attributes['blur']['blurLevel'] ?? 'medium';
            $exposure = $attributes['exposure']['exposureLevel'] ?? 'goodExposure';
            $noise = $attributes['noise']['noiseLevel'] ?? 'medium';
            $occlusion = $attributes['occlusion'] ?? [];

            $qualityScore = match ($quality) {
                'high' => 100,
                'medium' => 70,
                default => 40,
            };

            $blurPenalty = match ($blur) {
                'low' => 0,
                'medium' => 15,
                default => 30,
            };

            $exposurePenalty = match ($exposure) {
                'goodExposure' => 0,
                'overExposure' => 20,
                default => 25,
            };

            $noisePenalty = match ($noise) {
                'low' => 0,
                'medium' => 10,
                default => 20,
            };

            $occlusionPenalty = 0;
            if ($occlusion['foreheadOccluded'] ?? false) {
                $occlusionPenalty += 15;
            }
            if ($occlusion['eyeOccluded'] ?? false) {
                $occlusionPenalty += 25;
            }
            if ($occlusion['mouthOccluded'] ?? false) {
                $occlusionPenalty += 10;
            }

            $livenessScore = max(0, $qualityScore - $blurPenalty - $exposurePenalty - $noisePenalty - $occlusionPenalty);
            $passed = $livenessScore >= 60;

            return [
                'passed' => $passed,
                'confidence' => $livenessScore,
                'error' => $passed ? null : 'Liveness check failed. Please improve lighting and face the camera directly.',
            ];
        } catch (\Exception $e) {
            Log::error('Azure Face detectLiveness failed', ['error' => $e->getMessage()]);

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
            [$personId] = explode(':', $faceId);

            $result = $this->makeRequest('DELETE', "persongroups/{$this->personGroupId}/persons/{$personId}");

            return [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Azure Face deleteFace failed', ['error' => $e->getMessage()]);

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
        return 'azure';
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(): bool
    {
        return ! empty($this->endpoint) && ! empty($this->apiKey);
    }
}
