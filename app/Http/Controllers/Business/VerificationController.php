<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\InitiateVerificationRequest;
use App\Http\Requests\Business\SubmitDocumentRequest;
use App\Http\Requests\Business\ResubmitDocumentRequest;
use App\Models\BusinessDocument;
use App\Models\BusinessVerification;
use App\Services\BusinessVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Business Verification Controller
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Handles business KYB verification workflow
 */
class VerificationController extends Controller
{
    protected BusinessVerificationService $verificationService;

    public function __construct(BusinessVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Get KYB requirements for a jurisdiction.
     *
     * GET /api/business/verification/requirements
     */
    public function getRequirements(Request $request): JsonResponse
    {
        $request->validate([
            'jurisdiction' => 'required|string|size:2',
            'business_type' => 'nullable|string',
            'industry' => 'nullable|string',
        ]);

        $requirements = $this->verificationService->getKYBRequirements(
            $request->jurisdiction,
            $request->business_type,
            $request->industry
        );

        return response()->json([
            'success' => true,
            'data' => $requirements,
        ]);
    }

    /**
     * Initiate business verification.
     *
     * POST /api/business/verification/initiate
     */
    public function initiateVerification(InitiateVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isBusiness()) {
            return response()->json([
                'success' => false,
                'message' => 'Only business accounts can initiate verification',
            ], 403);
        }

        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        try {
            $verification = $this->verificationService->initiateVerification(
                $profile,
                $request->jurisdiction
            );

            return response()->json([
                'success' => true,
                'message' => 'Verification initiated successfully',
                'data' => [
                    'verification_id' => $verification->id,
                    'status' => $verification->status,
                    'jurisdiction' => $verification->jurisdiction,
                    'requirements' => $this->verificationService->getKYBRequirements(
                        $verification->jurisdiction,
                        $profile->business_type,
                        $profile->industry
                    ),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to initiate verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate verification',
            ], 500);
        }
    }

    /**
     * Submit verification documents.
     *
     * POST /api/business/verification/documents
     */
    public function submitDocuments(SubmitDocumentRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        // Get or create verification
        $verification = BusinessVerification::where('business_profile_id', $profile->id)
            ->whereIn('status', [
                BusinessVerification::STATUS_PENDING,
                BusinessVerification::STATUS_DOCUMENTS_REQUIRED,
            ])
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'No active verification found. Please initiate verification first.',
            ], 404);
        }

        try {
            $documents = [];

            foreach ($request->file('documents') as $index => $file) {
                $documentType = $request->document_types[$index];
                $documentName = $request->document_names[$index] ?? null;

                $document = $this->verificationService->uploadDocument(
                    $verification,
                    $file,
                    $documentType,
                    $documentName
                );

                $documents[] = [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'document_name' => $document->document_name,
                    'status' => $document->status,
                    'uploaded_at' => $document->created_at->toIso8601String(),
                ];
            }

            // Check if all required documents are now submitted
            $status = $this->verificationService->getVerificationStatus($verification);

            // Auto-submit if all documents uploaded
            if ($status['has_all_required'] && $request->input('auto_submit', true)) {
                $verification = $this->verificationService->submitDocuments($verification);
            }

            return response()->json([
                'success' => true,
                'message' => 'Documents uploaded successfully',
                'data' => [
                    'documents' => $documents,
                    'verification_status' => $status,
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to submit documents', [
                'user_id' => $user->id,
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload documents',
            ], 500);
        }
    }

    /**
     * Get verification status.
     *
     * GET /api/business/verification/status
     */
    public function getVerificationStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $verification = BusinessVerification::where('business_profile_id', $profile->id)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_verification' => false,
                    'is_verified' => $profile->is_verified,
                ],
            ]);
        }

        $status = $this->verificationService->getVerificationStatus($verification);

        return response()->json([
            'success' => true,
            'data' => array_merge(['has_verification' => true], $status),
        ]);
    }

    /**
     * Resubmit a rejected document.
     *
     * POST /api/business/verification/documents/{document}/resubmit
     */
    public function resubmitDocument(ResubmitDocumentRequest $request, BusinessDocument $document): JsonResponse
    {
        $user = $request->user();

        // Verify ownership
        if ($document->businessVerification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check if document can be resubmitted
        if (!in_array($document->status, [BusinessDocument::STATUS_REJECTED, BusinessDocument::STATUS_EXPIRED])) {
            return response()->json([
                'success' => false,
                'message' => 'Document cannot be resubmitted in current status',
            ], 422);
        }

        try {
            $newDocument = $this->verificationService->resubmitDocument(
                $document->businessVerification,
                $document,
                $request->file('document')
            );

            return response()->json([
                'success' => true,
                'message' => 'Document resubmitted successfully',
                'data' => [
                    'id' => $newDocument->id,
                    'document_type' => $newDocument->document_type,
                    'document_name' => $newDocument->document_name,
                    'status' => $newDocument->status,
                    'uploaded_at' => $newDocument->created_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resubmit document', [
                'user_id' => $user->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resubmit document',
            ], 500);
        }
    }

    /**
     * Download a document (secure URL).
     *
     * GET /api/business/verification/documents/{token}/download
     */
    public function downloadDocument(Request $request, string $token): mixed
    {
        $request->validate([
            'expires' => 'required|integer',
            'signature' => 'required|string',
        ]);

        $document = BusinessDocument::where('access_token', $token)->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        // Verify signature
        $expectedSignature = hash_hmac(
            'sha256',
            $token . $request->expires,
            config('app.key')
        );

        if (!hash_equals($expectedSignature, $request->signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        // Check expiry
        if ($request->expires < now()->timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Download link has expired',
            ], 403);
        }

        // Verify user has access (owner or admin)
        $user = $request->user();
        if (!$user || ($document->businessVerification->user_id !== $user->id && !$user->isAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Record access
        $document->recordAccess('download', $user);

        // Stream file from storage
        $path = $document->getFilePath();

        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return \Storage::disk($document->storage_provider)->download(
            $path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * Get document secure download URL.
     *
     * GET /api/business/verification/documents/{document}/url
     */
    public function getDocumentUrl(Request $request, BusinessDocument $document): JsonResponse
    {
        $user = $request->user();

        // Verify ownership or admin access
        if ($document->businessVerification->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Record view access
        $document->recordAccess('view', $user);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $document->getSecureUrl(30), // 30 minute expiry
                'expires_in' => 1800, // seconds
            ],
        ]);
    }

    /**
     * Submit verification for review (after all documents uploaded).
     *
     * POST /api/business/verification/submit
     */
    public function submitForReview(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        $verification = BusinessVerification::where('business_profile_id', $profile->id)
            ->whereIn('status', [
                BusinessVerification::STATUS_PENDING,
                BusinessVerification::STATUS_DOCUMENTS_REQUIRED,
            ])
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'No active verification found',
            ], 404);
        }

        try {
            $verification = $this->verificationService->submitDocuments($verification);
            $status = $this->verificationService->getVerificationStatus($verification);

            return response()->json([
                'success' => true,
                'message' => 'Verification submitted for review',
                'data' => $status,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to submit verification', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit verification',
            ], 500);
        }
    }
}
