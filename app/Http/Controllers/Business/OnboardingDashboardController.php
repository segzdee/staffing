<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\OnboardingProgressService;
use App\Services\OnboardingReminderService;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\OnboardingEvent;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Business Onboarding Dashboard Controller
 *
 * Handles all business onboarding dashboard functionality including
 * progress tracking, step completion, team setup progress, and help resources.
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
     * Display the business onboarding dashboard
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

        // Get team setup progress
        $teamProgress = $this->getTeamSetupProgress($user);

        // Get first shift data
        $firstShiftData = $this->getFirstShiftData($user);

        // Log dashboard view
        OnboardingEvent::log(
            $user->id,
            OnboardingEvent::EVENT_DASHBOARD_VIEWED,
            null,
            ['overall_progress' => $progressData['overall_progress']]
        );

        return view('business.onboarding.dashboard', [
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
            'teamProgress' => $teamProgress,
            'firstShiftData' => $firstShiftData,
            'successStories' => $this->getSuccessStories(),
            'tips' => $this->getOnboardingTips($progressData['overall_progress']),
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
     * Get team setup progress
     */
    public function getTeamProgress(): JsonResponse
    {
        $user = auth()->user();
        $teamProgress = $this->getTeamSetupProgress($user);

        return response()->json([
            'success' => true,
            'data' => $teamProgress,
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
     * Get team setup progress data
     */
    protected function getTeamSetupProgress($user): array
    {
        // Check if TeamMember model exists and has data
        try {
            $teamMembers = TeamMember::where('business_id', $user->id)->get();
            $totalMembers = $teamMembers->count();
            $activeMembers = $teamMembers->where('status', 'active')->count();
            $pendingInvites = $teamMembers->where('status', 'pending')->count();
        } catch (\Exception $e) {
            $totalMembers = 0;
            $activeMembers = 0;
            $pendingInvites = 0;
        }

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'pending_invites' => $pendingInvites,
            'has_team' => $totalMembers > 0,
            'suggested_roles' => [
                'Shift Manager',
                'HR Administrator',
                'Finance/Billing',
            ],
            'invite_url' => route('business.team.create'),
        ];
    }

    /**
     * Get first shift data/countdown
     */
    protected function getFirstShiftData($user): array
    {
        $firstShift = $user->postedShifts()
            ->orderBy('shift_date', 'asc')
            ->first();

        if ($firstShift) {
            $daysUntil = now()->diffInDays($firstShift->shift_date, false);

            return [
                'has_shift' => true,
                'shift_id' => $firstShift->id,
                'shift_date' => $firstShift->shift_date->format('M d, Y'),
                'days_until' => max(0, $daysUntil),
                'position' => $firstShift->position,
                'status' => $firstShift->status,
            ];
        }

        return [
            'has_shift' => false,
            'prompt' => 'Post your first shift to start hiring!',
            'create_url' => route('shifts.create'),
            'benefits' => [
                'Access to verified workers',
                'Real-time application notifications',
                'Streamlined time tracking',
            ],
        ];
    }

    /**
     * Get success stories for motivation
     */
    protected function getSuccessStories(): array
    {
        return [
            [
                'quote' => 'We filled 50 shifts in our first month with OvertimeStaff. The quality of workers has been exceptional.',
                'author' => 'Sarah M.',
                'company' => 'Event Solutions Co.',
                'industry' => 'Events',
            ],
            [
                'quote' => 'The onboarding was simple and we were posting shifts within an hour of signing up.',
                'author' => 'James T.',
                'company' => 'Retail Plus',
                'industry' => 'Retail',
            ],
            [
                'quote' => 'Finally, a platform that understands the needs of businesses looking for flexible staffing.',
                'author' => 'Maria L.',
                'company' => 'Healthcare Partners',
                'industry' => 'Healthcare',
            ],
        ];
    }

    /**
     * Get onboarding tips based on progress
     */
    protected function getOnboardingTips(float $progress): array
    {
        if ($progress < 25) {
            return [
                [
                    'title' => 'Complete Your Profile',
                    'description' => 'A complete business profile attracts more qualified workers.',
                    'icon' => 'building',
                ],
                [
                    'title' => 'Verify Your Business',
                    'description' => 'Verified businesses get priority in search results.',
                    'icon' => 'badge-check',
                ],
            ];
        } elseif ($progress < 50) {
            return [
                [
                    'title' => 'Set Up Payment',
                    'description' => 'Add a payment method to start posting shifts immediately.',
                    'icon' => 'credit-card',
                ],
                [
                    'title' => 'Invite Team Members',
                    'description' => 'Add colleagues to help manage shifts and workers.',
                    'icon' => 'users',
                ],
            ];
        } elseif ($progress < 75) {
            return [
                [
                    'title' => 'Post Your First Shift',
                    'description' => 'You are ready to post your first shift!',
                    'icon' => 'plus-circle',
                ],
                [
                    'title' => 'Create Shift Templates',
                    'description' => 'Save time by creating templates for recurring shifts.',
                    'icon' => 'template',
                ],
            ];
        }

        return [
            [
                'title' => 'Almost There!',
                'description' => 'Just a few more steps to complete your setup.',
                'icon' => 'flag',
            ],
            [
                'title' => 'Explore Features',
                'description' => 'Check out analytics and reporting tools.',
                'icon' => 'chart-bar',
            ],
        ];
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
                'company_name' => $user->businessProfile?->company_name ?? $user->name,
                'total_time_spent' => $progressData['stats']['total_time_spent'],
                'steps_completed' => $progressData['stats']['required']['completed'] + $progressData['stats']['recommended']['completed'],
                'next_actions' => [
                    [
                        'title' => 'Post Your First Shift',
                        'description' => 'Start finding great workers',
                        'url' => route('shifts.create'),
                        'icon' => 'plus-circle',
                        'primary' => true,
                    ],
                    [
                        'title' => 'Browse Available Workers',
                        'description' => 'See who is available in your area',
                        'url' => route('business.available-workers'),
                        'icon' => 'users',
                    ],
                    [
                        'title' => 'Invite Team Members',
                        'description' => 'Add colleagues to help manage',
                        'url' => route('business.team.index'),
                        'icon' => 'user-plus',
                    ],
                    [
                        'title' => 'View Analytics',
                        'description' => 'Track your hiring metrics',
                        'url' => route('business.analytics'),
                        'icon' => 'chart-bar',
                    ],
                ],
                'special_offer' => [
                    'enabled' => true,
                    'title' => 'First Month Free',
                    'description' => 'Enjoy premium features at no cost for your first month.',
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

        return redirect()->route('business.onboarding');
    }

    /**
     * Get wizard navigation state
     */
    public function getWizardState(): JsonResponse
    {
        $user = auth()->user();
        $progressData = $this->progressService->getProgressData($user);

        $wizardSteps = [];
        $currentStepIndex = 0;

        foreach ($progressData['required_steps'] as $index => $step) {
            $wizardSteps[] = [
                'id' => $step['step_id'],
                'name' => $step['name'],
                'status' => $step['status'],
                'is_current' => $step['status'] === 'in_progress' || (!$step['is_completed'] && $step['dependencies_met']),
            ];

            if ($step['status'] === 'in_progress') {
                $currentStepIndex = $index;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'steps' => $wizardSteps,
                'current_index' => $currentStepIndex,
                'total_steps' => count($wizardSteps),
                'can_skip_to_end' => false,
            ],
        ]);
    }
}
