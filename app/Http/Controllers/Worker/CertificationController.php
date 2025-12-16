<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\SubmitCertificationRequest;
use App\Http\Requests\Worker\UpdateCertificationRequest;
use App\Models\CertificationType;
use App\Models\WorkerCertification;
use App\Services\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STAFF-REG-007: Worker Certification Controller
 *
 * Handles certification management for workers via API.
 */
class CertificationController extends Controller
{
    protected CertificationService $certificationService;

    public function __construct(CertificationService $certificationService)
    {
        $this->certificationService = $certificationService;
        $this->middleware('auth');
    }

    /**
     * Show certifications management page (web route).
     *
     * GET /worker/certifications
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $worker = auth()->user();
        $certifications = $this->certificationService->getWorkerCertifications($worker);
        $availableTypes = $this->certificationService->getAvailableCertificationTypes();
        return view('worker.certifications', compact('certifications', 'availableTypes'));
    }

    /**
     * Get available certification types.
     *
     * GET /api/worker/certifications/types
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableTypes(Request $request): JsonResponse
    {
        $industry = $request->query('industry');
        $country = $request->query('country');
        $state = $request->query('state');

        $types = $this->certificationService->getAvailableCertificationTypes(
            $industry,
            $country,
            $state
        );

        return response()->json([
            'success' => true,
            'data' => [
                'certification_types' => $types,
                'categories' => CertificationType::getCategoryOptions(),
            ],
        ]);
    }

    /**
     * Get the worker's certifications (API route).
     *
     * GET /api/worker/certifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCertifications(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access certifications.',
            ], 403);
        }

        $validOnly = $request->boolean('valid_only', false);
        $certifications = $this->certificationService->getWorkerCertifications($worker, $validOnly);

        // Group by status for UI convenience
        $grouped = [
            'verified' => $certifications->where('verification_status', WorkerCertification::STATUS_VERIFIED)->values(),
            'pending' => $certifications->where('verification_status', WorkerCertification::STATUS_PENDING)->values(),
            'rejected' => $certifications->where('verification_status', WorkerCertification::STATUS_REJECTED)->values(),
            'expired' => $certifications->where('verification_status', WorkerCertification::STATUS_EXPIRED)->values(),
        ];

        // Get expiring soon
        $expiringSoon = $certifications->filter(function ($cert) {
            return $cert->isExpiringSoon(60);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'certifications' => $certifications,
                'grouped' => $grouped,
                'expiring_soon' => $expiringSoon,
                'summary' => [
                    'total' => $certifications->count(),
                    'verified' => $grouped['verified']->count(),
                    'pending' => $grouped['pending']->count(),
                    'expiring_soon' => $expiringSoon->count(),
                ],
            ],
        ]);
    }

    /**
     * Get a single certification.
     *
     * GET /api/worker/certifications/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)
            ->with(['certificationType', 'documents'])
            ->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $certification,
        ]);
    }

    /**
     * Submit a new certification.
     *
     * POST /api/worker/certifications
     *
     * @param SubmitCertificationRequest $request
     * @return JsonResponse
     */
    public function store(SubmitCertificationRequest $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can submit certifications.',
            ], 403);
        }

        $document = $request->hasFile('document') ? $request->file('document') : null;

        $result = $this->certificationService->submitCertification(
            $worker,
            $request->certification_type_id,
            $request->validated(),
            $document
        );

        if (!$result['success']) {
            $statusCode = isset($result['existing_certification_id']) ? 409 : 422;
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'existing_certification_id' => $result['existing_certification_id'] ?? null,
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification submitted successfully.',
            'data' => $result['certification'],
            'requires_manual_review' => $result['requires_manual_review'],
        ], 201);
    }

    /**
     * Update a certification.
     *
     * PUT /api/worker/certifications/{id}
     *
     * @param UpdateCertificationRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCertificationRequest $request, int $id): JsonResponse
    {
        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        // Only allow updates to pending or rejected certifications
        if ($certification->verification_status === WorkerCertification::STATUS_VERIFIED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update verified certifications. Please submit a renewal instead.',
            ], 422);
        }

        $result = $this->certificationService->updateCertification(
            $certification,
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification updated successfully.',
            'data' => $result['certification'],
        ]);
    }

    /**
     * Delete a certification.
     *
     * DELETE /api/worker/certifications/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        $result = $this->certificationService->deleteCertification($certification);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification deleted successfully.',
        ]);
    }

    /**
     * Upload an additional document for a certification.
     *
     * POST /api/worker/certifications/{id}/documents
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function uploadDocument(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,gif,pdf|max:10240',
            'document_type' => 'required|string|in:certificate,id_card,wallet_card,renewal_proof,other',
        ]);

        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        $result = $this->certificationService->storeCertificationDocument(
            $certification,
            $request->file('document'),
            $worker,
            $request->document_type
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'data' => $result['document'],
        ], 201);
    }

    /**
     * Start renewal process for a certification.
     *
     * POST /api/worker/certifications/{id}/renew
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function startRenewal(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        // Mark old certification for renewal
        $certification->startRenewal();

        return response()->json([
            'success' => true,
            'message' => 'Renewal process started. Please submit your new certification.',
            'data' => [
                'renewal_of_certification_id' => $certification->id,
                'certification_type_id' => $certification->certification_type_id,
            ],
        ]);
    }

    /**
     * Get certification expiry status.
     *
     * GET /api/worker/certifications/{id}/expiry
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function checkExpiry(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();

        $certification = WorkerCertification::where('worker_id', $worker->id)->find($id);

        if (!$certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $certification->checkExpiry(),
        ]);
    }
}
