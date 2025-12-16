<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\WorkerActivationService;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Activation Controller
 * STAFF-REG-010: Worker Activation Flow
 *
 * Handles worker activation eligibility checks and activation process.
 */
class ActivationController extends Controller
{
    protected WorkerActivationService $activationService;
    protected OnboardingService $onboardingService;

    public function __construct(
        WorkerActivationService $activationService,
        OnboardingService $onboardingService
    ) {
        $this->middleware(['auth', 'worker']);
        $this->activationService = $activationService;
        $this->onboardingService = $onboardingService;
    }

    /**
     * Check eligibility for activation.
     *
     * @return JsonResponse
     */
    public function checkEligibility(): JsonResponse
    {
        $worker = Auth::user();

        // Auto-validate onboarding steps first
        $this->onboardingService->autoValidateSteps($worker);

        $eligibility = $this->activationService->checkActivationEligibility($worker);

        return response()->json([
            'success' => true,
            'data' => $eligibility,
        ]);
    }

    /**
     * Activate the worker.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activate(Request $request): JsonResponse
    {
        $worker = Auth::user();

        // Auto-validate onboarding steps first
        $this->onboardingService->autoValidateSteps($worker);

        $result = $this->activationService->activateWorker($worker);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'eligibility' => $result['eligibility'] ?? null,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'worker' => $result['worker'],
            'profile' => $result['profile'],
        ]);
    }

    /**
     * Get current activation status.
     *
     * @return JsonResponse
     */
    public function getActivationStatus(): JsonResponse
    {
        $worker = Auth::user();
        $status = $this->activationService->getActivationStatus($worker);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Apply a referral code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyReferralCode(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string|size:8',
        ]);

        $worker = Auth::user();
        $result = $this->activationService->applyReferralCode($worker, $request->referral_code);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'referrer_name' => $result['referrer_name'],
        ]);
    }

    /**
     * Show the activation page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $worker = Auth::user();
        $worker->load('workerProfile');

        // Check if already activated
        if ($worker->workerProfile && $worker->workerProfile->is_activated) {
            return redirect()->route('worker.dashboard')
                ->with('info', 'Your account is already activated!');
        }

        // Auto-validate onboarding steps first
        $this->onboardingService->autoValidateSteps($worker);

        $eligibility = $this->activationService->checkActivationEligibility($worker);
        $status = $this->activationService->getActivationStatus($worker);

        return view('worker.activation.index', [
            'user' => $worker,
            'eligibility' => $eligibility,
            'status' => $status,
        ]);
    }

    /**
     * Show the activation checklist.
     *
     * @return \Illuminate\View\View
     */
    public function checklist()
    {
        $worker = Auth::user();
        $worker->load('workerProfile');

        // Auto-validate onboarding steps
        $this->onboardingService->autoValidateSteps($worker);

        $eligibility = $this->activationService->checkActivationEligibility($worker);

        // Separate required and recommended checks
        $requiredChecks = array_filter($eligibility['checks'], fn($c) => $c['required']);
        $recommendedChecks = array_filter($eligibility['checks'], fn($c) => !$c['required']);

        return view('worker.activation.checklist', [
            'user' => $worker,
            'eligibility' => $eligibility,
            'requiredChecks' => $requiredChecks,
            'recommendedChecks' => $recommendedChecks,
        ]);
    }

    /**
     * Show the welcome/first shift guidance page.
     *
     * @return \Illuminate\View\View
     */
    public function welcome()
    {
        $worker = Auth::user();
        $worker->load('workerProfile');

        // Mark guidance as shown
        if ($worker->workerProfile && !$worker->workerProfile->first_shift_guidance_shown) {
            $worker->workerProfile->update([
                'first_shift_guidance_shown' => true,
            ]);
        }

        return view('worker.activation.welcome', [
            'user' => $worker,
        ]);
    }
}
