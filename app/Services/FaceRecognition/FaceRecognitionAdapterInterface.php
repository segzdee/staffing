<?php

namespace App\Services\FaceRecognition;

/**
 * SL-005: Face Recognition Adapter Interface
 *
 * Contract for face recognition provider adapters.
 */
interface FaceRecognitionAdapterInterface
{
    /**
     * Index a face for future comparison (enrollment).
     *
     * @param  string  $imageData  Base64 encoded image or URL
     * @param  string  $externalId  External identifier (user ID)
     * @return array{success: bool, face_id: ?string, confidence: ?float, attributes: ?array, error: ?string}
     */
    public function indexFace(string $imageData, string $externalId): array;

    /**
     * Compare a source image against an indexed face.
     *
     * @param  string  $sourceImage  Base64 encoded image or URL
     * @param  string  $faceId  The indexed face ID to compare against
     * @return array{match: bool, confidence: float, error: ?string}
     */
    public function compareFaces(string $sourceImage, string $faceId): array;

    /**
     * Compare two images directly without indexing.
     *
     * @param  string  $sourceImage  Base64 encoded image or URL
     * @param  string  $targetImage  Base64 encoded image or URL
     * @return array{match: bool, confidence: float, error: ?string}
     */
    public function compareFacesDirectly(string $sourceImage, string $targetImage): array;

    /**
     * Detect faces in an image and return attributes.
     *
     * @param  string  $imageData  Base64 encoded image or URL
     * @return array{faces: array, face_count: int, error: ?string}
     */
    public function detectFaces(string $imageData): array;

    /**
     * Perform liveness detection on an image.
     *
     * @param  string  $imageData  Base64 encoded image or URL
     * @return array{passed: bool, confidence: float, error: ?string}
     */
    public function detectLiveness(string $imageData): array;

    /**
     * Delete an indexed face.
     *
     * @param  string  $faceId  The face ID to delete
     * @return array{success: bool, error: ?string}
     */
    public function deleteFace(string $faceId): array;

    /**
     * Get the provider name.
     */
    public function getProviderName(): string;

    /**
     * Check if the adapter is properly configured and available.
     */
    public function isAvailable(): bool;
}
