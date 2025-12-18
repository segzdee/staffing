<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SafetyCertification;
use App\Models\WorkerCertification;
use App\Notifications\CertificationRejectedNotification;
use App\Services\CertificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SAF-003: Admin Certification Management Controller
 *
 * Handles certification management for administrators including:
 * - Managing certification types
 * - Reviewing pending verifications
 * - Viewing expiring certifications dashboard
 * - Compliance reporting
 */
class CertificationManagementController extends Controller
{
    protected CertificationService $certificationService;

    public function __construct(CertificationService $certificationService)
    {
        $this->certificationService = $certificationService;
        $this->middleware(['auth', 'admin']);
    }

    // =========================================
    // Web Routes (Blade Views)
    // =========================================

    /**
     * Display certification management dashboard.
     *
     * GET /admin/certifications
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $pendingCount = WorkerCertification::pending()->count();
        $expiringCount = WorkerCertification::verified()->expiringSoon(30)->count();
        $certificationTypes = SafetyCertification::active()->orderBy('category')->orderBy('name')->get();
        $complianceReport = $this->certificationService->getComplianceReport();

        return view('admin.certifications.index', compact(
            'pendingCount',
            'expiringCount',
            'certificationTypes',
            'complianceReport'
        ));
    }

    /**
     * Display pending verifications page.
     *
     * GET /admin/certifications/pending
     *
     * @return \Illuminate\View\View
     */
    public function pendingVerifications()
    {
        $certifications = WorkerCertification::pending()
            ->with(['worker', 'safetyCertification', 'certificationType', 'currentDocument'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return view('admin.certifications.pending', compact('certifications'));
    }

    /**
     * Display expiring certifications dashboard.
     *
     * GET /admin/certifications/expiring
     *
     * @return \Illuminate\View\View
     */
    public function expiringDashboard()
    {
        $expiringIn7Days = $this->certificationService->getCertificationsExpiringIn(7);
        $expiringIn14Days = $this->certificationService->getCertificationsExpiringIn(14);
        $expiringIn30Days = $this->certificationService->getCertificationsExpiringIn(30);

        return view('admin.certifications.expiring', compact(
            'expiringIn7Days',
            'expiringIn14Days',
            'expiringIn30Days'
        ));
    }

    /**
     * Display compliance report page.
     *
     * GET /admin/certifications/compliance
     *
     * @return \Illuminate\View\View
     */
    public function complianceReport()
    {
        $report = $this->certificationService->getComplianceReport();

        return view('admin.certifications.compliance', compact('report'));
    }

    // =========================================
    // API Routes
    // =========================================

    /**
     * Get all safety certification types.
     *
     * GET /api/admin/certifications/types
     */
    public function getCertificationTypes(Request $request): JsonResponse
    {
        $query = SafetyCertification::query();

        if ($request->has('active_only') && $request->boolean('active_only')) {
            $query->active();
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        $types = $query->orderBy('category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'certification_types' => $types,
                'categories' => SafetyCertification::getCategoryOptions(),
            ],
        ]);
    }

    /**
     * Create a new safety certification type.
     *
     * POST /api/admin/certifications/types
     */
    public function storeCertificationType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:food_safety,health,security,industry_specific,general',
            'issuing_authority' => 'nullable|string|max:255',
            'validity_months' => 'nullable|integer|min:1|max:120',
            'requires_renewal' => 'boolean',
            'applicable_industries' => 'nullable|array',
            'applicable_positions' => 'nullable|array',
            'is_mandatory' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = true;

        // Check for duplicate slug
        if (SafetyCertification::where('slug', $validated['slug'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A certification with this name already exists.',
            ], 422);
        }

        $certification = SafetyCertification::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Certification type created successfully.',
            'data' => $certification,
        ], 201);
    }

    /**
     * Update a safety certification type.
     *
     * PUT /api/admin/certifications/types/{id}
     */
    public function updateCertificationType(Request $request, int $id): JsonResponse
    {
        $certification = SafetyCertification::find($id);

        if (! $certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification type not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'sometimes|in:food_safety,health,security,industry_specific,general',
            'issuing_authority' => 'nullable|string|max:255',
            'validity_months' => 'nullable|integer|min:1|max:120',
            'requires_renewal' => 'boolean',
            'applicable_industries' => 'nullable|array',
            'applicable_positions' => 'nullable|array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Update slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $certification->name) {
            $newSlug = Str::slug($validated['name']);
            if (SafetyCertification::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A certification with this name already exists.',
                ], 422);
            }
            $validated['slug'] = $newSlug;
        }

        $certification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Certification type updated successfully.',
            'data' => $certification->fresh(),
        ]);
    }

    /**
     * Delete a safety certification type.
     *
     * DELETE /api/admin/certifications/types/{id}
     */
    public function destroyCertificationType(int $id): JsonResponse
    {
        $certification = SafetyCertification::find($id);

        if (! $certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification type not found.',
            ], 404);
        }

        // Check if there are worker certifications using this type
        $usageCount = $certification->workerCertifications()->count();
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$usageCount} worker(s) have this certification. Deactivate it instead.",
            ], 422);
        }

        $certification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Certification type deleted successfully.',
        ]);
    }

    /**
     * Get pending verifications (API).
     *
     * GET /api/admin/certifications/pending
     */
    public function getPendingVerifications(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);

        $certifications = WorkerCertification::pending()
            ->with(['worker', 'safetyCertification', 'certificationType', 'currentDocument'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $certifications,
        ]);
    }

    /**
     * Get a single worker certification for review.
     *
     * GET /api/admin/certifications/review/{id}
     */
    public function getForReview(int $id): JsonResponse
    {
        $certification = WorkerCertification::with([
            'worker',
            'safetyCertification',
            'certificationType',
            'documents',
            'verifier',
        ])->find($id);

        if (! $certification) {
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
     * Verify a worker certification.
     *
     * POST /api/admin/certifications/{id}/verify
     */
    public function verifyCertification(Request $request, int $id): JsonResponse
    {
        $certification = WorkerCertification::find($id);

        if (! $certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        if ($certification->verification_status !== WorkerCertification::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This certification is not pending verification.',
            ], 422);
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->certificationService->verifyCertification(
            $certification,
            $request->user()->id,
            $request->notes
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification verified successfully.',
            'data' => $result['certification'],
        ]);
    }

    /**
     * Reject a worker certification.
     *
     * POST /api/admin/certifications/{id}/reject
     */
    public function rejectCertification(Request $request, int $id): JsonResponse
    {
        $certification = WorkerCertification::find($id);

        if (! $certification) {
            return response()->json([
                'success' => false,
                'message' => 'Certification not found.',
            ], 404);
        }

        if ($certification->verification_status !== WorkerCertification::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This certification is not pending verification.',
            ], 422);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $result = $this->certificationService->rejectCertification(
            $certification,
            $request->user()->id,
            $request->reason
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        // Send rejection notification
        try {
            $certification->worker->notify(new CertificationRejectedNotification(
                $certification,
                $request->reason
            ));
        } catch (\Exception $e) {
            \Log::warning('Failed to send certification rejection notification', [
                'certification_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certification rejected.',
            'data' => $result['certification'],
        ]);
    }

    /**
     * Get expiring certifications (API).
     *
     * GET /api/admin/certifications/expiring
     */
    public function getExpiringCertifications(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        $certifications = $this->certificationService->getCertificationsExpiringIn($days);

        $grouped = [
            'critical' => $certifications->filter(fn ($c) => $c->getDaysUntilExpiry() <= 7)->values(),
            'urgent' => $certifications->filter(fn ($c) => $c->getDaysUntilExpiry() > 7 && $c->getDaysUntilExpiry() <= 14)->values(),
            'warning' => $certifications->filter(fn ($c) => $c->getDaysUntilExpiry() > 14 && $c->getDaysUntilExpiry() <= 30)->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'certifications' => $certifications,
                'grouped' => $grouped,
                'summary' => [
                    'total' => $certifications->count(),
                    'critical' => $grouped['critical']->count(),
                    'urgent' => $grouped['urgent']->count(),
                    'warning' => $grouped['warning']->count(),
                ],
            ],
        ]);
    }

    /**
     * Get compliance report (API).
     *
     * GET /api/admin/certifications/compliance
     */
    public function getComplianceReport(): JsonResponse
    {
        $report = $this->certificationService->getComplianceReport();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Check expiry and process expired certifications.
     *
     * POST /api/admin/certifications/process-expiry
     */
    public function processExpiry(): JsonResponse
    {
        $expiryResult = $this->certificationService->checkCertificationExpiry();
        $processedResult = $this->certificationService->processExpiredCertifications();

        return response()->json([
            'success' => true,
            'message' => 'Expiry processing completed.',
            'data' => [
                'expiry_check' => $expiryResult,
                'processed' => $processedResult,
            ],
        ]);
    }

    /**
     * Send expiry reminders.
     *
     * POST /api/admin/certifications/send-reminders
     */
    public function sendExpiryReminders(): JsonResponse
    {
        $result = $this->certificationService->scheduleExpiryReminders();

        return response()->json([
            'success' => true,
            'message' => "Sent {$result['reminders_sent']} expiry reminders.",
            'data' => $result,
        ]);
    }
}
