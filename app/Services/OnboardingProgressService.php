<?php

namespace App\Services;

use App\Models\User;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\OnboardingEvent;
use App\Models\OnboardingCohort;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * OnboardingProgressService
 *
 * Manages all onboarding progress tracking, calculations, and status updates.
 * This is the main service for handling user onboarding workflows.
 */
class OnboardingProgressService
{
    // Required steps weight percentage (70% of total)
    const REQUIRED_WEIGHT_PERCENTAGE = 70;

    // Recommended steps weight percentage (30% of total)
    const RECOMMENDED_WEIGHT_PERCENTAGE = 30;

    // Minimum overall progress to activate (80%)
    const ACTIVATION_THRESHOLD = 80;

    // Cache TTL in seconds
    const CACHE_TTL = 300; // 5 minutes

    /**
     * Initialize onboarding for a new user
     */
    public function initializeOnboarding(User $user): array
    {
        return DB::transaction(function () use ($user) {
            // Get all steps for user type
            $steps = OnboardingStep::getStepsForUserType($user->user_type);

            if ($steps->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No onboarding steps configured for this user type',
                ];
            }

            // Create progress records for each step
            $progressRecords = [];
            foreach ($steps as $step) {
                $progressRecords[] = OnboardingProgress::getOrCreate($user->id, $step->id);
            }

            // Try to assign to A/B test cohort
            $cohort = OnboardingCohort::assignCohort($user);

            // Log onboarding started event
            OnboardingEvent::log(
                $user->id,
                OnboardingEvent::EVENT_ONBOARDING_STARTED,
                null,
                [
                    'user_type' => $user->user_type,
                    'total_steps' => $steps->count(),
                    'cohort_id' => $cohort?->cohort_id,
                ]
            );

            // Mark account_created step as completed (auto-complete)
            $this->autoCompleteStep($user, 'account_created');

            // Clear any cached progress
            $this->clearProgressCache($user->id);

            return [
                'success' => true,
                'message' => 'Onboarding initialized successfully',
                'total_steps' => $steps->count(),
                'cohort' => $cohort?->cohort_id,
            ];
        });
    }

    /**
     * Update progress for a specific step
     */
    public function updateProgress(User $user, string $stepId, string $status, ?array $data = null): array
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step) {
            return [
                'success' => false,
                'message' => 'Step not found',
            ];
        }

        $progress = OnboardingProgress::getOrCreate($user->id, $step->id);

        // Check dependencies if starting or completing
        if (in_array($status, ['in_progress', 'completed']) && $step->hasDependencies()) {
            $dependenciesMet = $this->checkDependencies($user, $step);
            if (!$dependenciesMet) {
                return [
                    'success' => false,
                    'message' => 'Step dependencies not met',
                    'missing_dependencies' => $step->getDependencyIds(),
                ];
            }
        }

        // Update based on status
        switch ($status) {
            case 'in_progress':
                $progress->start();
                OnboardingEvent::log($user->id, OnboardingEvent::EVENT_STEP_STARTED, $stepId);
                break;

            case 'completed':
                $progress->complete('user');
                OnboardingEvent::log(
                    $user->id,
                    OnboardingEvent::EVENT_STEP_COMPLETED,
                    $stepId,
                    ['time_spent' => $progress->time_spent_seconds]
                );
                break;

            case 'failed':
                $progress->fail($data['reason'] ?? null);
                OnboardingEvent::log(
                    $user->id,
                    OnboardingEvent::EVENT_STEP_FAILED,
                    $stepId,
                    $data
                );
                break;

            case 'skipped':
                if (!$step->canBeSkipped()) {
                    return [
                        'success' => false,
                        'message' => 'This step cannot be skipped',
                    ];
                }
                $progress->skip($data['reason'] ?? null);
                OnboardingEvent::log($user->id, OnboardingEvent::EVENT_STEP_SKIPPED, $stepId);
                break;
        }

        // Update progress data if provided
        if ($data && isset($data['progress_percentage'])) {
            $progress->updateProgress($data['progress_percentage'], $data);
        }

        // Clear cache and check overall completion
        $this->clearProgressCache($user->id);

        // Check if user can now be activated
        if ($status === 'completed' && $this->canActivate($user)) {
            $this->activateUser($user);
        }

        return [
            'success' => true,
            'message' => 'Progress updated successfully',
            'step_status' => $progress->status,
            'overall_progress' => $this->calculateOverallProgress($user),
            'can_activate' => $this->canActivate($user),
        ];
    }

    /**
     * Auto-complete a step based on system events
     */
    public function autoCompleteStep(User $user, string $stepId, ?array $data = null): bool
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step || !$step->auto_complete) {
            return false;
        }

        $progress = OnboardingProgress::getOrCreate($user->id, $step->id);

        if ($progress->isCompleted()) {
            return true; // Already completed
        }

        $progress->complete('system');

        OnboardingEvent::log(
            $user->id,
            OnboardingEvent::EVENT_STEP_COMPLETED,
            $stepId,
            array_merge(['auto_completed' => true], $data ?? [])
        );

        $this->clearProgressCache($user->id);

        // Check if user can now be activated
        if ($this->canActivate($user)) {
            $this->activateUser($user);
        }

        return true;
    }

    /**
     * Calculate overall progress percentage for a user
     */
    public function calculateOverallProgress(User $user): float
    {
        $cacheKey = "onboarding_progress_{$user->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $steps = OnboardingStep::getStepsForUserType($user->user_type);

            if ($steps->isEmpty()) {
                return 0;
            }

            $totalWeight = 0;
            $completedWeight = 0;

            foreach ($steps as $step) {
                $progress = OnboardingProgress::where('user_id', $user->id)
                    ->where('onboarding_step_id', $step->id)
                    ->first();

                // Calculate weighted contribution
                $weight = $step->weight;

                // Apply type multiplier (required = 70%, recommended = 30%)
                if ($step->step_type === 'required') {
                    $weight = $weight * (self::REQUIRED_WEIGHT_PERCENTAGE / 100);
                } else {
                    $weight = $weight * (self::RECOMMENDED_WEIGHT_PERCENTAGE / 100);
                }

                $totalWeight += $weight;

                if ($progress && $progress->isCompleted()) {
                    $completedWeight += $weight;
                } elseif ($progress && $progress->isSkipped() && $step->canBeSkipped()) {
                    // Skipped optional steps count toward progress
                    $completedWeight += $weight;
                } elseif ($progress && $progress->progress_percentage > 0) {
                    // Partial progress
                    $completedWeight += $weight * ($progress->progress_percentage / 100);
                }
            }

            return $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 1) : 0;
        });
    }

    /**
     * Calculate progress for a specific category
     */
    public function calculateCategoryProgress(User $user, string $category): float
    {
        $steps = OnboardingStep::getStepsForUserType($user->user_type)
            ->where('category', $category);

        if ($steps->isEmpty()) {
            return 0;
        }

        $totalWeight = $steps->sum('weight');
        $completedWeight = 0;

        foreach ($steps as $step) {
            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if ($progress && $progress->isCompleted()) {
                $completedWeight += $step->weight;
            } elseif ($progress && $progress->progress_percentage > 0) {
                $completedWeight += $step->weight * ($progress->progress_percentage / 100);
            }
        }

        return $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 1) : 0;
    }

    /**
     * Get the next required step for a user
     */
    public function getNextRequiredStep(User $user): ?OnboardingStep
    {
        $requiredSteps = OnboardingStep::getRequiredSteps($user->user_type);

        foreach ($requiredSteps as $step) {
            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || !$progress->isFinished()) {
                // Check dependencies
                if ($step->hasDependencies() && !$this->checkDependencies($user, $step)) {
                    continue;
                }
                return $step;
            }
        }

        // All required steps done, return first incomplete recommended
        return $this->getNextRecommendedStep($user);
    }

    /**
     * Get the next recommended step for a user
     */
    public function getNextRecommendedStep(User $user): ?OnboardingStep
    {
        $recommendedSteps = OnboardingStep::getRecommendedSteps($user->user_type);

        foreach ($recommendedSteps as $step) {
            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || !$progress->isFinished()) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Get all missing/incomplete steps for a user
     */
    public function getMissingSteps(User $user): array
    {
        $steps = OnboardingStep::getStepsForUserType($user->user_type);
        $missing = [
            'required' => [],
            'recommended' => [],
            'optional' => [],
        ];

        foreach ($steps as $step) {
            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || !$progress->isFinished()) {
                $missing[$step->step_type][] = [
                    'step_id' => $step->step_id,
                    'name' => $step->name,
                    'status' => $progress?->status ?? 'not_started',
                    'progress' => $progress?->progress_percentage ?? 0,
                    'estimated_minutes' => $step->estimated_minutes,
                    'route_url' => $step->getRouteUrl(),
                ];
            }
        }

        return $missing;
    }

    /**
     * Check if user can be activated (all requirements met)
     */
    public function canActivate(User $user): bool
    {
        // Check all required steps are completed
        $requiredSteps = OnboardingStep::getRequiredSteps($user->user_type);

        foreach ($requiredSteps as $step) {
            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $step->id)
                ->first();

            if (!$progress || !$progress->isCompleted()) {
                return false;
            }
        }

        // Check overall progress meets threshold
        $overallProgress = $this->calculateOverallProgress($user);

        return $overallProgress >= self::ACTIVATION_THRESHOLD;
    }

    /**
     * Activate a user after completing onboarding
     */
    public function activateUser(User $user): void
    {
        if ($user->onboarding_completed) {
            return; // Already activated
        }

        $user->update([
            'onboarding_completed' => true,
            'onboarding_step' => 'completed',
        ]);

        OnboardingEvent::log(
            $user->id,
            OnboardingEvent::EVENT_ONBOARDING_COMPLETED,
            null,
            [
                'time_to_completion' => $user->created_at->diffInMinutes(now()),
                'overall_progress' => $this->calculateOverallProgress($user),
            ]
        );

        // Update cohort metrics if assigned
        $cohort = OnboardingCohort::getForUser($user->id);
        if ($cohort) {
            $cohort->increment('completed_users');
            $cohort->refreshMetrics();
        }
    }

    /**
     * Track an onboarding event
     */
    public function trackEvent(User $user, string $eventType, ?array $metadata = null): void
    {
        $cohort = OnboardingCohort::getForUser($user->id);

        OnboardingEvent::logWithCohort(
            $user->id,
            $eventType,
            null,
            $metadata,
            $cohort?->cohort_id,
            $cohort?->variant
        );
    }

    /**
     * Get full progress data for a user (for dashboard)
     */
    public function getProgressData(User $user): array
    {
        $steps = OnboardingStep::getStepsForUserType($user->user_type);
        $progressMap = OnboardingProgress::forUser($user->id)
            ->get()
            ->keyBy('onboarding_step_id');

        $requiredSteps = [];
        $recommendedSteps = [];
        $optionalSteps = [];
        $categories = [];

        foreach ($steps as $step) {
            $progress = $progressMap->get($step->id);

            $stepData = [
                'id' => $step->id,
                'step_id' => $step->step_id,
                'name' => $step->name,
                'description' => $step->description,
                'category' => $step->category,
                'order' => $step->order,
                'weight' => $step->weight,
                'estimated_minutes' => $step->estimated_minutes,
                'estimated_time_string' => $step->getEstimatedTimeString(),
                'icon' => $step->icon,
                'color' => $step->color,
                'route_url' => $step->getRouteUrl(),
                'help_text' => $step->help_text,
                'help_url' => $step->help_url,
                'can_skip' => $step->canBeSkipped(),
                'has_threshold' => $step->hasThreshold(),
                'threshold' => $step->threshold,
                'has_target' => $step->hasTarget(),
                'target' => $step->target,
                'status' => $progress?->status ?? 'pending',
                'progress_percentage' => $progress?->progress_percentage ?? 0,
                'started_at' => $progress?->started_at?->toIso8601String(),
                'completed_at' => $progress?->completed_at?->toIso8601String(),
                'time_spent' => $progress?->getTimeSpentString(),
                'is_completed' => $progress?->isCompleted() ?? false,
                'is_skipped' => $progress?->isSkipped() ?? false,
                'dependencies_met' => !$step->hasDependencies() || $this->checkDependencies($user, $step),
            ];

            switch ($step->step_type) {
                case 'required':
                    $requiredSteps[] = $stepData;
                    break;
                case 'recommended':
                    $recommendedSteps[] = $stepData;
                    break;
                case 'optional':
                    $optionalSteps[] = $stepData;
                    break;
            }

            // Track category progress
            if ($step->category) {
                if (!isset($categories[$step->category])) {
                    $categories[$step->category] = [
                        'name' => $step->category,
                        'total_steps' => 0,
                        'completed_steps' => 0,
                    ];
                }
                $categories[$step->category]['total_steps']++;
                if ($progress?->isCompleted()) {
                    $categories[$step->category]['completed_steps']++;
                }
            }
        }

        // Calculate category percentages
        foreach ($categories as $key => $cat) {
            $categories[$key]['progress'] = $cat['total_steps'] > 0
                ? round(($cat['completed_steps'] / $cat['total_steps']) * 100, 1)
                : 0;
        }

        $nextStep = $this->getNextRequiredStep($user);

        return [
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'overall_progress' => $this->calculateOverallProgress($user),
            'can_activate' => $this->canActivate($user),
            'is_activated' => $user->onboarding_completed,
            'required_steps' => $requiredSteps,
            'recommended_steps' => $recommendedSteps,
            'optional_steps' => $optionalSteps,
            'categories' => array_values($categories),
            'next_step' => $nextStep ? [
                'step_id' => $nextStep->step_id,
                'name' => $nextStep->name,
                'route_url' => $nextStep->getRouteUrl(),
                'estimated_minutes' => $nextStep->estimated_minutes,
            ] : null,
            'stats' => $this->getProgressStats($user),
            'estimated_time_remaining' => $this->calculateEstimatedTimeRemaining($user),
        ];
    }

    /**
     * Get progress statistics for a user
     */
    public function getProgressStats(User $user): array
    {
        $steps = OnboardingStep::getStepsForUserType($user->user_type);
        $progressMap = OnboardingProgress::forUser($user->id)
            ->get()
            ->keyBy('onboarding_step_id');

        $required = ['total' => 0, 'completed' => 0];
        $recommended = ['total' => 0, 'completed' => 0];

        foreach ($steps as $step) {
            $progress = $progressMap->get($step->id);

            if ($step->step_type === 'required') {
                $required['total']++;
                if ($progress?->isCompleted()) {
                    $required['completed']++;
                }
            } elseif ($step->step_type === 'recommended') {
                $recommended['total']++;
                if ($progress?->isCompleted() || $progress?->isSkipped()) {
                    $recommended['completed']++;
                }
            }
        }

        return [
            'required' => $required,
            'recommended' => $recommended,
            'total_time_spent' => $progressMap->sum('time_spent_seconds'),
        ];
    }

    /**
     * Calculate estimated time remaining to complete onboarding
     */
    public function calculateEstimatedTimeRemaining(User $user): int
    {
        $missing = $this->getMissingSteps($user);
        $totalMinutes = 0;

        foreach (['required', 'recommended'] as $type) {
            foreach ($missing[$type] as $step) {
                // Account for partial progress
                $remainingPercentage = 100 - ($step['progress'] ?? 0);
                $totalMinutes += ($step['estimated_minutes'] * $remainingPercentage / 100);
            }
        }

        return (int) round($totalMinutes);
    }

    /**
     * Check if step dependencies are met
     */
    protected function checkDependencies(User $user, OnboardingStep $step): bool
    {
        if (!$step->hasDependencies()) {
            return true;
        }

        foreach ($step->getDependencyIds() as $depStepId) {
            $depStep = OnboardingStep::findByStepId($depStepId);
            if (!$depStep) {
                continue;
            }

            $progress = OnboardingProgress::where('user_id', $user->id)
                ->where('onboarding_step_id', $depStep->id)
                ->first();

            if (!$progress || !$progress->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear cached progress data
     */
    protected function clearProgressCache(int $userId): void
    {
        Cache::forget("onboarding_progress_{$userId}");
    }

    /**
     * Get help content for a specific step
     */
    public function getHelpForStep(string $stepId): ?array
    {
        $step = OnboardingStep::findByStepId($stepId);

        if (!$step) {
            return null;
        }

        return [
            'step_id' => $step->step_id,
            'name' => $step->name,
            'description' => $step->description,
            'help_text' => $step->help_text,
            'help_url' => $step->help_url,
            'estimated_minutes' => $step->estimated_minutes,
            'tips' => $this->getStepTips($step),
            'common_issues' => $this->getCommonIssues($step),
        ];
    }

    /**
     * Get tips for completing a step
     */
    protected function getStepTips(OnboardingStep $step): array
    {
        $tips = [
            'profile_complete' => [
                'Add a professional profile photo to stand out',
                'Write a compelling bio highlighting your experience',
                'Include relevant work history and skills',
            ],
            'identity_verified' => [
                'Ensure your ID document is clearly visible',
                'Use good lighting when taking photos',
                'Make sure all text is readable',
            ],
            'payment_setup' => [
                'Double-check your bank account details',
                'Set up instant payouts for faster access to earnings',
                'Keep your payment information up to date',
            ],
            'skills_added' => [
                'Add skills that are in high demand',
                'Be specific about your experience level',
                'Include both hard and soft skills',
            ],
        ];

        return $tips[$step->step_id] ?? [
            'Take your time to complete this step thoroughly',
            'Contact support if you encounter any issues',
        ];
    }

    /**
     * Get common issues for a step
     */
    protected function getCommonIssues(OnboardingStep $step): array
    {
        $issues = [
            'identity_verified' => [
                'Document photo is blurry or unreadable',
                'Document has expired',
                'Name on document doesn\'t match account',
            ],
            'rtw_verified' => [
                'Work authorization document not recognized',
                'Document has expired',
                'Missing required documentation',
            ],
        ];

        return $issues[$step->step_id] ?? [];
    }
}
