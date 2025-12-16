<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\OnboardingService;
use App\Services\ProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Worker Onboarding Controller
 * STAFF-REG-010: Onboarding Progress Tracking
 *
 * Handles worker onboarding flow, progress tracking, and step completion.
 */
class OnboardingController extends Controller
{
    protected OnboardingService $onboardingService;
    protected ProfileCompletionService $profileCompletionService;

    public function __construct(
        OnboardingService $onboardingService,
        ProfileCompletionService $profileCompletionService
    ) {
        $this->middleware(['auth', 'worker']);
        $this->onboardingService = $onboardingService;
        $this->profileCompletionService = $profileCompletionService;
    }

    /**
     * Show the profile completion page for workers.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function completeProfile()
    {
        $user = Auth::user();
        $user->load('workerProfile');

        // Calculate profile completeness using service
        $completion = $this->profileCompletionService->calculateCompletion($user);

        // If profile is already complete (>=80%), redirect to dashboard
        if ($completion['percentage'] >= 80) {
            return redirect()->route('worker.dashboard')
                ->with('success', 'Your profile is complete! Start browsing shifts.');
        }

        // Get detailed onboarding progress
        $progress = $this->onboardingService->getDetailedProgress($user);

        return view('worker.onboarding.complete-profile', [
            'user' => $user,
            'completeness' => $completion['percentage'],
            'completion' => $completion,
            'progress' => $progress,
            'missingFields' => $this->getMissingFields($user),
        ]);
    }

    /**
     * Get current onboarding progress.
     *
     * @return JsonResponse
     */
    public function getProgress(): JsonResponse
    {
        $worker = Auth::user();

        // Auto-validate and complete steps based on current data
        $this->onboardingService->autoValidateSteps($worker);

        $progress = $this->onboardingService->getDetailedProgress($worker);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Get the next step to complete.
     *
     * @return JsonResponse
     */
    public function getNextStep(): JsonResponse
    {
        $worker = Auth::user();
        $nextStep = $this->onboardingService->getNextRequiredStep($worker);

        if (!$nextStep) {
            return response()->json([
                'success' => true,
                'all_complete' => true,
                'message' => 'All required steps are complete!',
            ]);
        }

        return response()->json([
            'success' => true,
            'all_complete' => false,
            'next_step' => [
                'id' => $nextStep['step']->id,
                'step_id' => $nextStep['step']->step_id,
                'name' => $nextStep['step']->name,
                'description' => $nextStep['step']->description,
                'help_text' => $nextStep['step']->help_text,
                'route_url' => $nextStep['route_url'],
                'estimated_time' => $nextStep['step']->getEstimatedTimeString(),
                'current_progress' => $nextStep['progress']?->progress_percentage ?? 0,
            ],
        ]);
    }

    /**
     * Complete a specific step.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $worker = Auth::user();
        $result = $this->onboardingService->completeStep(
            $worker,
            $request->step_id,
            [
                'completed_by' => 'user',
                'notes' => $request->notes,
            ]
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Step '{$result['step']->name}' completed!",
            'step' => $result['step'],
            'progress' => $result['overall_progress'],
            'next_step' => $result['next_step'],
        ]);
    }

    /**
     * Skip an optional step.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function skipOptionalStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $worker = Auth::user();
        $result = $this->onboardingService->skipOptionalStep(
            $worker,
            $request->step_id,
            $request->reason
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Step skipped successfully.',
            'progress' => $result['overall_progress'],
        ]);
    }

    /**
     * Show the onboarding dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $worker = Auth::user();
        $worker->load('workerProfile');

        // Auto-validate steps
        $this->onboardingService->autoValidateSteps($worker);

        $progress = $this->onboardingService->getDetailedProgress($worker);
        $nextStep = $this->onboardingService->getNextRequiredStep($worker);
        $completion = $this->profileCompletionService->calculateCompletion($worker);

        return view('worker.onboarding.dashboard', [
            'user' => $worker,
            'progress' => $progress,
            'nextStep' => $nextStep,
            'completion' => $completion,
        ]);
    }

    /**
     * Initialize onboarding for a new worker.
     *
     * @return JsonResponse
     */
    public function initialize(): JsonResponse
    {
        $worker = Auth::user();
        $result = $this->onboardingService->initializeOnboarding($worker);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Onboarding initialized successfully.',
            'progress' => $result['progress'],
        ]);
    }

    /**
     * Get missing profile fields (legacy method for backwards compatibility).
     *
     * @param \App\Models\User $user
     * @return array
     */
    private function getMissingFields($user): array
    {
        $missing = [];

        // Check basic user fields
        if (!$user->phone) {
            $missing[] = [
                'field' => 'phone',
                'label' => 'Phone Number',
                'description' => 'Employers need to contact you about shifts',
                'priority' => 'high'
            ];
        }

        if (!$user->city || !$user->state) {
            $missing[] = [
                'field' => 'location',
                'label' => 'Location',
                'description' => 'We need your location to show you nearby shifts',
                'priority' => 'high'
            ];
        }

        if (!$user->avatar || $user->avatar == 'avatar.jpg') {
            $missing[] = [
                'field' => 'avatar',
                'label' => 'Profile Photo',
                'description' => 'A photo helps employers recognize you',
                'priority' => 'medium'
            ];
        }

        // Check worker profile fields
        $profile = $user->workerProfile;

        if (!$profile || !$profile->years_experience) {
            $missing[] = [
                'field' => 'experience_level',
                'label' => 'Experience Level',
                'description' => 'Help employers find workers with the right experience',
                'priority' => 'high'
            ];
        }

        if (!$profile || $user->skills()->count() === 0) {
            $missing[] = [
                'field' => 'skills',
                'label' => 'Skills',
                'description' => 'List your skills to match with relevant shifts',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->preferred_industries) {
            $missing[] = [
                'field' => 'industries',
                'label' => 'Preferred Industries',
                'description' => 'Select industries you want to work in',
                'priority' => 'medium'
            ];
        }

        if (!$profile || !$profile->transportation) {
            $missing[] = [
                'field' => 'transportation',
                'label' => 'Transportation',
                'description' => 'Let employers know if you have reliable transportation',
                'priority' => 'medium'
            ];
        }

        if (!$profile || !$profile->max_commute_distance) {
            $missing[] = [
                'field' => 'max_distance',
                'label' => 'Maximum Commute Distance',
                'description' => 'How far are you willing to travel for work?',
                'priority' => 'low'
            ];
        }

        return $missing;
    }
}
