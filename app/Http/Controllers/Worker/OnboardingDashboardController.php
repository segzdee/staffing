<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Services\OnboardingProgressService;
use App\Services\OnboardingReminderService;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\OnboardingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Worker Onboarding Dashboard Controller
 *
 * Handles all worker onboarding dashboard functionality including
 * progress tracking, step completion, and help resources.
 */
class OnboardingDashboardController extends Controller
{
    protected OnboardingProgressService $progressService;
    protected OnboardingReminderService $reminderService;

    public function __construct(
        OnboardingProgressService $progressService,
        OnboardingReminderService $reminderService
    ) {
        $this->progressService = $progressService;
        $this->reminderService = $reminderService;
    }

    /**
     * Display the worker onboarding dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();

        // Check if already completed - redirect to main dashboard
        if ($user->onboarding_completed) {
            return redirect()->route('dashboard')
                ->with('info', 'You have already completed onboarding!');
        }

        // Get full progress data
        $progressData = $this->progressService->getProgressData($user);

        // Log dashboard view
        OnboardingEvent::log(
            $user->id,
            OnboardingEvent::EVENT_DASHBOARD_VIEWED,
            null,
            ['overall_progress' => $progressData['overall_progress']]
        );

        return view('worker.onboarding.dashboard', [
            'user' => $user,
            'progress' => $progressData,
            'requiredSteps' => $progressData['required_steps'],
            'recommendedSteps' => $progressData['recommended_steps'],
            'nextStep' => $progressData['next_step'],
            'overallProgress' => $progressData['overall_progress'],
            'canActivate' => $progressData['can_activate'],
            'estimatedTimeRemaining' => $progressData['estimated_time_remaining'],
            'categories' => $progressData['categories'],
            'stats' => $progressData['stats'],
        ]);
    }

    /**
     * Get progress data via AJAX
     */
    public function getProgress(): JsonResponse
    {
        $user = auth()->user();
        $progressData = $this->progressService->getProgressData($user);

        return response()->json([
            'success' => true,
            'data' => $progressData,
        ]);
    }

    /**
     * Get next step recommendation
     */
    public function getNextStep(): JsonResponse
    {
        $user = auth()->user();
        $nextStep = $this->progressService->getNextRequiredStep($user);

        if (!$nextStep) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'All steps completed!',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'step_id' => $nextStep->step_id,
                'name' => $nextStep->name,
                'description' => $nextStep->description,
                'estimated_minutes' => $nextStep->estimated_minutes,
                'route_url' => $nextStep->getRouteUrl(),
                'icon' => $nextStep->icon,
                'color' => $nextStep->color,
            ],
        ]);
    }

    /**
     * Mark a step as complete
     */
    public function completeStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string|max:50',
            'progress_data' => 'nullable|array',
        ]);

        $user = auth()->user();
        $result = $this->progressService->updateProgress(
            $user,
            $request->step_id,
            'completed',
            $request->progress_data
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // Check for milestone notifications
        $overallProgress = $result['overall_progress'];
        foreach ([25, 50, 75] as $milestone) {
            if ($overallProgress >= $milestone && $overallProgress < $milestone + 10) {
                $this->reminderService->sendMilestoneNotification($user, $milestone);
            }
        }

        // Send celebration if just activated
        if ($result['can_activate'] && !$user->onboarding_completed) {
            $this->reminderService->sendCompletionCelebration($user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Step completed successfully!',
            'data' => [
                'step_status' => $result['step_status'],
                'overall_progress' => $result['overall_progress'],
                'can_activate' => $result['can_activate'],
                'next_step' => $this->progressService->getNextRequiredStep($user)?->toArray(),
            ],
        ]);
    }

    /**
     * Skip an optional step
     */
    public function skipStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string|max:50',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // Verify step can be skipped
        $step = OnboardingStep::findByStepId($request->step_id);
        if (!$step || !$step->canBeSkipped()) {
            return response()->json([
                'success' => false,
                'message' => 'This step cannot be skipped.',
            ], 400);
        }

        $result = $this->progressService->updateProgress(
            $user,
            $request->step_id,
            'skipped',
            ['reason' => $request->reason]
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Step skipped.' : $result['message'],
            'data' => [
                'overall_progress' => $result['overall_progress'] ?? null,
            ],
        ]);
    }

    /**
     * Get context-specific help for a step
     */
    public function getHelpForStep(string $stepId): JsonResponse
    {
        $help = $this->progressService->getHelpForStep($stepId);

        if (!$help) {
            return response()->json([
                'success' => false,
                'message' => 'Step not found.',
            ], 404);
        }

        // Log help viewed
        OnboardingEvent::log(
            auth()->id(),
            OnboardingEvent::EVENT_HELP_VIEWED,
            $stepId
        );

        return response()->json([
            'success' => true,
            'data' => $help,
        ]);
    }

    /**
     * Start a step (mark as in progress)
     */
    public function startStep(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string|max:50',
        ]);

        $user = auth()->user();
        $result = $this->progressService->updateProgress(
            $user,
            $request->step_id,
            'in_progress'
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Step started.' : $result['message'],
            'data' => [
                'step_status' => $result['step_status'] ?? null,
                'redirect_url' => OnboardingStep::findByStepId($request->step_id)?->getRouteUrl(),
            ],
        ]);
    }

    /**
     * Get missing steps list
     */
    public function getMissingSteps(): JsonResponse
    {
        $user = auth()->user();
        $missing = $this->progressService->getMissingSteps($user);

        return response()->json([
            'success' => true,
            'data' => $missing,
            'counts' => [
                'required' => count($missing['required']),
                'recommended' => count($missing['recommended']),
                'optional' => count($missing['optional']),
            ],
        ]);
    }

    /**
     * Request support for a step
     */
    public function requestSupport(Request $request): JsonResponse
    {
        $request->validate([
            'step_id' => 'required|string|max:50',
            'message' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();

        // Log support request
        OnboardingEvent::log(
            $user->id,
            OnboardingEvent::EVENT_SUPPORT_CONTACTED,
            $request->step_id,
            ['message' => $request->message]
        );

        // In production, this would create a support ticket
        // For now, we'll just acknowledge the request

        return response()->json([
            'success' => true,
            'message' => 'Support request received. Our team will reach out to you shortly.',
        ]);
    }

    /**
     * Get celebration data after completion
     */
    public function getCelebrationData(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->onboarding_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Onboarding not yet completed.',
            ], 400);
        }

        $progressData = $this->progressService->getProgressData($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user_name' => $user->first_name,
                'total_time_spent' => $progressData['stats']['total_time_spent'],
                'steps_completed' => $progressData['stats']['required']['completed'] + $progressData['stats']['recommended']['completed'],
                'next_actions' => [
                    [
                        'title' => 'Browse Available Shifts',
                        'description' => 'Find your first shift opportunity',
                        'url' => route('worker.market'),
                        'icon' => 'briefcase',
                    ],
                    [
                        'title' => 'Set Your Availability',
                        'description' => 'Let businesses know when you can work',
                        'url' => route('worker.calendar'),
                        'icon' => 'calendar',
                    ],
                    [
                        'title' => 'Complete Your Profile',
                        'description' => 'Add more details to stand out',
                        'url' => route('worker.profile'),
                        'icon' => 'user',
                    ],
                ],
                'referral_bonus' => [
                    'enabled' => true,
                    'amount' => 25,
                    'url' => route('referrals'),
                ],
            ],
        ]);
    }

    /**
     * Resume onboarding from where user left off
     */
    public function resume(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        $nextStep = $this->progressService->getNextRequiredStep($user);

        if ($nextStep && $nextStep->getRouteUrl()) {
            return redirect($nextStep->getRouteUrl());
        }

        return redirect()->route('worker.onboarding');
    }
}
