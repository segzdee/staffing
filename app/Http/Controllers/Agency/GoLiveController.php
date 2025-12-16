<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyProfile;
use App\Services\AgencyGoLiveService;
use App\Services\AgencyComplianceService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * GoLiveController
 *
 * Handles agency go-live checklist and activation process.
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 *
 * Routes:
 * - GET  /agency/go-live           - Display go-live checklist
 * - POST /agency/go-live/verify    - Verify a checklist item
 * - POST /agency/go-live/request   - Submit go-live request
 * - GET  /agency/go-live/agreement - View commercial agreement
 * - POST /agency/go-live/sign      - Sign commercial agreement
 * - GET  /agency/go-live/status    - Get checklist status (JSON)
 */
class GoLiveController extends Controller
{
    protected AgencyGoLiveService $goLiveService;
    protected AgencyComplianceService $complianceService;

    public function __construct(
        AgencyGoLiveService $goLiveService,
        AgencyComplianceService $complianceService
    ) {
        $this->goLiveService = $goLiveService;
        $this->complianceService = $complianceService;
    }

    /**
     * Display the go-live readiness checklist.
     *
     * GET /agency/go-live
     *
     * @return View|RedirectResponse
     */
    public function showChecklist(): View|RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile.complete')
                ->with('error', 'Please complete your agency profile first.');
        }

        // If already live, redirect to dashboard
        if ($agency->is_live) {
            return redirect()->route('dashboard')
                ->with('info', 'Your agency is already live!');
        }

        // Get the complete checklist
        $checklistResult = $this->goLiveService->getChecklist($user->id);

        if (!$checklistResult['success']) {
            return redirect()->route('agency.profile')
                ->with('error', $checklistResult['error'] ?? 'Unable to load checklist.');
        }

        // Get compliance details
        $complianceResult = $this->complianceService->calculateComplianceScore($agency);

        return view('agency.go-live.checklist', [
            'agency' => $agency,
            'checklist' => $checklistResult['checklist'],
            'progress' => $checklistResult['progress'],
            'compliance' => $complianceResult,
            'isReady' => $checklistResult['is_ready'],
            'blockingItems' => $checklistResult['blocking_items'],
            'nextSteps' => $checklistResult['next_steps'],
            'isPendingReview' => $agency->verification_status === 'pending_review',
        ]);
    }

    /**
     * Verify/mark a checklist item as complete (self-verification for some items).
     *
     * POST /agency/go-live/verify/{item}
     *
     * @param Request $request
     * @param string $item
     * @return RedirectResponse|JsonResponse
     */
    public function verifyItem(Request $request, string $item): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Agency not found.'], 404);
            }
            return redirect()->route('agency.profile')
                ->with('error', 'Agency profile not found.');
        }

        // Handle verification based on item type
        $result = $this->handleItemVerification($agency, $item, $request);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('agency.go-live.checklist')
                ->with('success', $result['message']);
        }

        return redirect()->route('agency.go-live.checklist')
            ->with('error', $result['error'] ?? 'Verification failed.');
    }

    /**
     * Handle verification for different checklist items.
     *
     * @param AgencyProfile $agency
     * @param string $item
     * @param Request $request
     * @return array
     */
    protected function handleItemVerification(AgencyProfile $agency, string $item, Request $request): array
    {
        switch ($item) {
            case 'profile_complete':
                // Trigger profile completeness check
                return $this->verifyProfileComplete($agency);

            case 'stripe_configured':
                // Redirect handled elsewhere - just check status
                return [
                    'success' => $agency->canReceivePayouts(),
                    'message' => $agency->canReceivePayouts()
                        ? 'Stripe configuration verified.'
                        : 'Please complete Stripe Connect setup.',
                    'redirect' => route('agency.stripe.onboarding'),
                ];

            case 'workers_onboarded':
                // Check worker count
                return $this->verifyWorkersOnboarded($agency);

            case 'documents_verified':
                // Trigger compliance document check
                $result = $this->complianceService->checkDocumentsComplete($agency);
                return [
                    'success' => $result['verified'],
                    'message' => $result['message'],
                ];

            case 'agreement_signed':
                // Agreement verification handled by signAgreement method
                return [
                    'success' => $agency->agreement_signed ?? false,
                    'message' => $agency->agreement_signed
                        ? 'Agreement already signed.'
                        : 'Please sign the commercial agreement.',
                    'redirect' => route('agency.go-live.agreement'),
                ];

            case 'test_shift_completed':
                // Check if test shift is completed
                return $this->verifyTestShiftCompleted($agency);

            default:
                return [
                    'success' => false,
                    'error' => 'Unknown checklist item.',
                ];
        }
    }

    /**
     * Verify profile completeness.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function verifyProfileComplete(AgencyProfile $agency): array
    {
        $requiredFields = ['agency_name', 'phone', 'address', 'city', 'state', 'zip_code', 'country', 'description'];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($agency->$field)) {
                $missingFields[] = str_replace('_', ' ', $field);
            }
        }

        if (empty($missingFields)) {
            $agency->update(['is_complete' => true]);
            return [
                'success' => true,
                'message' => 'Profile completeness verified.',
            ];
        }

        return [
            'success' => false,
            'error' => 'Please complete the following fields: ' . implode(', ', $missingFields),
            'redirect' => route('agency.profile.edit'),
        ];
    }

    /**
     * Verify workers onboarded requirement.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function verifyWorkersOnboarded(AgencyProfile $agency): array
    {
        $activeWorkers = $agency->agencyWorkers()->where('status', 'active')->count();
        $required = AgencyGoLiveService::MIN_WORKERS_REQUIRED;

        if ($activeWorkers >= $required) {
            return [
                'success' => true,
                'message' => "Worker requirement met. You have {$activeWorkers} active workers.",
            ];
        }

        $remaining = $required - $activeWorkers;
        return [
            'success' => false,
            'error' => "You need {$remaining} more active worker(s). Currently have {$activeWorkers} of {$required} required.",
            'redirect' => route('agency.workers.add'),
        ];
    }

    /**
     * Verify test shift completed.
     *
     * @param AgencyProfile $agency
     * @return array
     */
    protected function verifyTestShiftCompleted(AgencyProfile $agency): array
    {
        if ($agency->test_shift_completed) {
            return [
                'success' => true,
                'message' => 'Test shift completion verified.',
            ];
        }

        // Check actual completed shifts
        $workerIds = $agency->agencyWorkers()->pluck('worker_id')->toArray();

        $completedShift = \App\Models\ShiftAssignment::whereIn('worker_id', $workerIds)
            ->where('status', 'completed')
            ->first();

        if ($completedShift) {
            $agency->update(['test_shift_completed' => true]);
            return [
                'success' => true,
                'message' => 'Test shift completion verified.',
            ];
        }

        return [
            'success' => false,
            'error' => 'Please have one of your workers complete a shift first.',
            'redirect' => route('agency.shifts.browse'),
        ];
    }

    /**
     * Submit go-live request.
     *
     * POST /agency/go-live/request
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function requestGoLive(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $result = $this->goLiveService->requestGoLive($user->id);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('agency.go-live.checklist')
                ->with('success', $result['message']);
        }

        return redirect()->route('agency.go-live.checklist')
            ->with('error', $result['error'])
            ->with('blocking_items', $result['blocking_items'] ?? []);
    }

    /**
     * Display commercial agreement for signing.
     *
     * GET /agency/go-live/agreement
     *
     * @return View|RedirectResponse
     */
    public function showAgreement(): View|RedirectResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return redirect()->route('agency.profile')
                ->with('error', 'Agency profile not found.');
        }

        return view('agency.go-live.agreement', [
            'agency' => $agency,
            'isSigned' => $agency->agreement_signed ?? false,
            'signedAt' => $agency->agreement_signed_at,
            'agreementVersion' => config('agency.agreement_version', '1.0'),
        ]);
    }

    /**
     * Sign the commercial agreement.
     *
     * POST /agency/go-live/sign
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function signAgreement(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'signature_name' => 'required|string|max:255',
            'signature_title' => 'required|string|max:255',
            'accept_terms' => 'required|accepted',
        ]);

        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => 'Agency not found.'], 404);
            }
            return redirect()->route('agency.profile')
                ->with('error', 'Agency profile not found.');
        }

        try {
            $agency->update([
                'agreement_signed' => true,
                'agreement_signed_at' => now(),
                'agreement_version' => config('agency.agreement_version', '1.0'),
                'agreement_signer_name' => $request->input('signature_name'),
                'agreement_signer_title' => $request->input('signature_title'),
                'agreement_signer_ip' => $request->ip(),
            ]);

            Log::info('Agency agreement signed', [
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'signer_name' => $request->input('signature_name'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Agreement signed successfully.',
                ]);
            }

            return redirect()->route('agency.go-live.checklist')
                ->with('success', 'Commercial agreement signed successfully.');

        } catch (\Exception $e) {
            Log::error('Agreement signing failed', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to sign agreement. Please try again.',
                ], 500);
            }

            return redirect()->route('agency.go-live.agreement')
                ->with('error', 'Failed to sign agreement. Please try again.');
        }
    }

    /**
     * Get checklist status as JSON (for AJAX updates).
     *
     * GET /agency/go-live/status
     *
     * @return JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        $user = auth()->user();

        $result = $this->goLiveService->getChecklist($user->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'checklist' => $result['checklist'],
            'progress' => $result['progress'],
            'compliance' => $result['compliance'],
            'is_ready' => $result['is_ready'],
        ]);
    }

    /**
     * Refresh compliance status.
     *
     * POST /agency/go-live/refresh-compliance
     *
     * @return JsonResponse
     */
    public function refreshCompliance(): JsonResponse
    {
        $user = auth()->user();
        $agency = AgencyProfile::where('user_id', $user->id)->first();

        if (!$agency) {
            return response()->json(['success' => false, 'error' => 'Agency not found.'], 404);
        }

        $complianceResult = $this->complianceService->runFullComplianceCheck($agency);

        return response()->json([
            'success' => true,
            'compliance' => [
                'score' => $complianceResult['score'],
                'grade' => $complianceResult['grade'],
                'grade_label' => $complianceResult['grade_label'],
                'is_compliant' => $complianceResult['is_compliant'],
                'is_go_live_ready' => $complianceResult['is_go_live_ready'],
            ],
            'category_scores' => $complianceResult['category_scores'],
            'next_steps' => $complianceResult['next_steps'],
            'expires_soon' => $complianceResult['expires_soon'],
        ]);
    }
}
