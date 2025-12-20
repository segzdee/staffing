<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\InitiateRTWRequest;
use App\Http\Requests\Worker\SubmitRTWDocumentsRequest;
use App\Http\Requests\Worker\VerifyShareCodeRequest;
use App\Http\Requests\Worker\VerifyVEVORequest;
use App\Models\RightToWorkVerification;
use App\Models\RTWDocument;
use App\Services\RightToWorkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * STAFF-REG-005: Worker Right-to-Work Controller
 *
 * Handles RTW verification initiation, document submission,
 * and status checking for workers.
 */
class RightToWorkController extends Controller
{
    protected RightToWorkService $rtwService;

    public function __construct(RightToWorkService $rtwService)
    {
        $this->middleware(['auth', 'worker']);
        $this->rtwService = $rtwService;
    }

    /**
     * Show the right-to-work verification status page.
     *
     * GET /worker/right-to-work
     */
    public function index()
    {
        $user = Auth::user();
        $workerProfile = $user->workerProfile;

        // Get latest RTW verification
        $latestVerification = RightToWorkVerification::where('worker_id', $user->id)
            ->latest()
            ->first();

        // Get verification history
        $verificationHistory = RightToWorkVerification::where('worker_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get submitted documents
        $documents = RTWDocument::where('worker_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Determine jurisdiction requirements
        $jurisdiction = $workerProfile?->country ?? 'GB';
        $requirements = $this->rtwService->getRTWRequirements($jurisdiction);

        return view('worker.verification.right-to-work', compact(
            'latestVerification',
            'verificationHistory',
            'documents',
            'requirements',
            'workerProfile'
        ));
    }

    /**
     * Get RTW requirements for a jurisdiction.
     *
     * GET /api/worker/rtw/requirements/{jurisdiction}
     */
    public function getRequirements(string $jurisdiction): JsonResponse
    {
        try {
            $requirements = $this->rtwService->getRTWRequirements(strtoupper($jurisdiction));

            return response()->json([
                'success' => true,
                'data' => $requirements,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get list of supported jurisdictions.
     *
     * GET /api/worker/rtw/jurisdictions
     */
    public function getJurisdictions(): JsonResponse
    {
        $jurisdictions = $this->rtwService->getSupportedJurisdictions();

        return response()->json([
            'success' => true,
            'data' => $jurisdictions,
        ]);
    }

    /**
     * Initiate RTW verification process.
     *
     * POST /api/worker/rtw/initiate
     */
    public function initiateVerification(InitiateRTWRequest $request): JsonResponse
    {
        $user = Auth::user();
        $jurisdiction = strtoupper($request->validated('jurisdiction'));

        try {
            $verification = $this->rtwService->initiateVerification($user, $jurisdiction);

            $requirements = $this->rtwService->getRTWRequirements($jurisdiction);

            return response()->json([
                'success' => true,
                'message' => 'RTW verification initiated',
                'data' => [
                    'verification_id' => $verification->id,
                    'status' => $verification->status,
                    'jurisdiction' => $verification->jurisdiction,
                    'verification_type' => $verification->verification_type,
                    'requirements' => $requirements,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('RTW initiation failed', [
                'user_id' => $user->id,
                'jurisdiction' => $jurisdiction,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate verification',
            ], 500);
        }
    }

    /**
     * Submit documents for RTW verification.
     *
     * POST /api/worker/rtw/documents
     */
    public function submitDocuments(SubmitRTWDocumentsRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $verification = RightToWorkVerification::where('id', $validated['verification_id'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', [
                RightToWorkVerification::STATUS_VERIFIED,
                RightToWorkVerification::STATUS_REJECTED,
            ])
            ->firstOrFail();

        $uploadedDocuments = [];
        $errors = [];

        // Process each document
        foreach ($request->file('documents', []) as $index => $file) {
            $documentType = $validated['document_types'][$index] ?? null;

            if (! $documentType) {
                $errors[] = "Document type missing for file {$index}";

                continue;
            }

            try {
                $metadata = [
                    'document_number' => $validated['document_numbers'][$index] ?? null,
                    'issuing_country' => $validated['issuing_countries'][$index] ?? null,
                    'issuing_authority' => $validated['issuing_authorities'][$index] ?? null,
                    'issue_date' => $validated['issue_dates'][$index] ?? null,
                    'expiry_date' => $validated['expiry_dates'][$index] ?? null,
                ];

                $document = $this->rtwService->uploadDocument(
                    $verification,
                    $file,
                    $documentType,
                    $metadata
                );

                $uploadedDocuments[] = [
                    'id' => $document->id,
                    'document_type' => $document->document_type,
                    'document_type_name' => $document->document_type_name,
                    'status' => $document->status,
                ];
            } catch (\Exception $e) {
                $errors[] = "Failed to upload document {$index}: ".$e->getMessage();
                Log::error('RTW document upload failed', [
                    'verification_id' => $verification->id,
                    'document_type' => $documentType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Check document combination validity
        $combinationResult = $this->rtwService->validateDocumentCombination($verification->fresh());

        return response()->json([
            'success' => count($uploadedDocuments) > 0,
            'message' => count($uploadedDocuments).' document(s) uploaded successfully',
            'data' => [
                'uploaded_documents' => $uploadedDocuments,
                'errors' => $errors,
                'verification_status' => $verification->fresh()->status,
                'document_combination' => $combinationResult,
            ],
        ], count($uploadedDocuments) > 0 ? 200 : 400);
    }

    /**
     * Get RTW verification status for current user.
     *
     * GET /api/worker/rtw/status
     */
    public function getVerificationStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        $jurisdiction = $request->query('jurisdiction');

        $status = $this->rtwService->getVerificationStatus(
            $user,
            $jurisdiction ? strtoupper($jurisdiction) : null
        );

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get details of a specific verification.
     *
     * GET /api/worker/rtw/{id}
     */
    public function getVerification(int $id): JsonResponse
    {
        $user = Auth::user();

        $verification = RightToWorkVerification::where('id', $id)
            ->where('user_id', $user->id)
            ->with('documents')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $verification->id,
                'jurisdiction' => $verification->jurisdiction,
                'jurisdiction_name' => $verification->jurisdiction_name,
                'verification_type' => $verification->verification_type,
                'verification_type_name' => $verification->verification_type_name,
                'status' => $verification->status,
                'document_combination' => $verification->document_combination,
                'verified_at' => $verification->verified_at?->toDateString(),
                'expires_at' => $verification->expires_at?->toDateString(),
                'days_until_expiry' => $verification->days_until_expiry,
                'is_active' => $verification->isActive(),
                'is_expiring_soon' => $verification->isExpiringSoon(),
                'has_work_restrictions' => $verification->has_work_restrictions,
                'work_restrictions' => $verification->work_restrictions,
                'documents' => $verification->documents->map(fn ($doc) => [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'document_type_name' => $doc->document_type_name,
                    'document_list' => $doc->document_list,
                    'status' => $doc->status,
                    'issue_date' => $doc->issue_date?->toDateString(),
                    'expiry_date' => $doc->expiry_date?->toDateString(),
                    'days_until_expiry' => $doc->days_until_expiry,
                    'is_expired' => $doc->isExpired(),
                    'verified_at' => $doc->verified_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    /**
     * Verify UK share code online.
     *
     * POST /api/worker/rtw/verify-share-code
     */
    public function verifyShareCode(VerifyShareCodeRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $verification = RightToWorkVerification::where('id', $validated['verification_id'])
            ->where('user_id', $user->id)
            ->where('jurisdiction', 'UK')
            ->firstOrFail();

        try {
            $result = $this->rtwService->verifyUKShareCode(
                $verification,
                $validated['share_code'],
                $validated['date_of_birth']
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'Share code verified' : 'Verification failed'),
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('UK share code verification failed', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification service error',
            ], 500);
        }
    }

    /**
     * Verify Australian VEVO online.
     *
     * POST /api/worker/rtw/verify-vevo
     */
    public function verifyVEVO(VerifyVEVORequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $verification = RightToWorkVerification::where('id', $validated['verification_id'])
            ->where('user_id', $user->id)
            ->where('jurisdiction', 'AU')
            ->firstOrFail();

        try {
            $result = $this->rtwService->verifyAustralianVEVO(
                $verification,
                $validated['passport_number'],
                $validated['country_of_passport'],
                $validated['date_of_birth']
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'VEVO verified' : 'Verification failed'),
                'data' => $result,
            ], $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            Log::error('Australian VEVO verification failed', [
                'verification_id' => $verification->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification service error',
            ], 500);
        }
    }

    /**
     * Delete a pending document.
     *
     * DELETE /api/worker/rtw/documents/{id}
     */
    public function deleteDocument(int $id): JsonResponse
    {
        $user = Auth::user();

        $document = RTWDocument::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', RTWDocument::STATUS_PENDING)
            ->firstOrFail();

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Check if user has valid RTW for a jurisdiction.
     *
     * GET /api/worker/rtw/valid/{jurisdiction}
     */
    public function hasValidRTW(string $jurisdiction): JsonResponse
    {
        $user = Auth::user();
        $jurisdiction = strtoupper($jurisdiction);

        $isValid = $this->rtwService->hasValidRTW($user, $jurisdiction);

        return response()->json([
            'success' => true,
            'data' => [
                'jurisdiction' => $jurisdiction,
                'has_valid_rtw' => $isValid,
            ],
        ]);
    }
}
