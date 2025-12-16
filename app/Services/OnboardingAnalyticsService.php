<?php

namespace App\Services;

use App\Models\User;
use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use App\Models\OnboardingEvent;
use App\Models\OnboardingCohort;
use App\Models\OnboardingReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * OnboardingAnalyticsService
 *
 * Provides analytics and reporting for onboarding funnel analysis,
 * A/B testing, dropout analysis, and cohort performance tracking.
 */
class OnboardingAnalyticsService
{
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Calculate overall completion rate for a time period
     */
    public function calculateCompletionRate(?string $userType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $query = User::whereBetween('created_at', [$startDate, $endDate]);

        if ($userType) {
            $query->where('user_type', $userType);
        }

        $totalUsers = $query->count();
        $completedUsers = (clone $query)->where('onboarding_completed', true)->count();

        // Get partial completion breakdown
        $partialCompletion = OnboardingProgress::whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
            if ($userType) {
                $q->where('user_type', $userType);
            }
        })
        ->selectRaw('user_id, COUNT(*) as total_steps, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_steps')
        ->groupBy('user_id')
        ->get();

        // Calculate distribution
        $distribution = [
            '0-25%' => 0,
            '26-50%' => 0,
            '51-75%' => 0,
            '76-99%' => 0,
            '100%' => 0,
        ];

        foreach ($partialCompletion as $progress) {
            $percentage = $progress->total_steps > 0
                ? ($progress->completed_steps / $progress->total_steps) * 100
                : 0;

            if ($percentage >= 100) {
                $distribution['100%']++;
            } elseif ($percentage >= 76) {
                $distribution['76-99%']++;
            } elseif ($percentage >= 51) {
                $distribution['51-75%']++;
            } elseif ($percentage >= 26) {
                $distribution['26-50%']++;
            } else {
                $distribution['0-25%']++;
            }
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_type' => $userType ?? 'all',
            'total_users' => $totalUsers,
            'completed_users' => $completedUsers,
            'completion_rate' => $totalUsers > 0 ? round(($completedUsers / $totalUsers) * 100, 2) : 0,
            'distribution' => $distribution,
        ];
    }

    /**
     * Get average time to activation
     */
    public function getAverageTimeToActivation(?string $userType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $completedUsers = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('onboarding_completed', true)
            ->when($userType, fn($q) => $q->where('user_type', $userType))
            ->get();

        if ($completedUsers->isEmpty()) {
            return [
                'average_hours' => null,
                'median_hours' => null,
                'min_hours' => null,
                'max_hours' => null,
                'sample_size' => 0,
            ];
        }

        $times = [];

        foreach ($completedUsers as $user) {
            // Get completion event
            $completionEvent = OnboardingEvent::forUser($user->id)
                ->ofType(OnboardingEvent::EVENT_ONBOARDING_COMPLETED)
                ->first();

            if ($completionEvent) {
                $hours = $user->created_at->diffInHours($completionEvent->created_at);
                $times[] = $hours;
            }
        }

        if (empty($times)) {
            return [
                'average_hours' => null,
                'median_hours' => null,
                'min_hours' => null,
                'max_hours' => null,
                'sample_size' => 0,
            ];
        }

        sort($times);
        $count = count($times);
        $median = $count % 2 === 0
            ? ($times[$count / 2 - 1] + $times[$count / 2]) / 2
            : $times[floor($count / 2)];

        return [
            'average_hours' => round(array_sum($times) / $count, 2),
            'median_hours' => round($median, 2),
            'min_hours' => round(min($times), 2),
            'max_hours' => round(max($times), 2),
            'sample_size' => $count,
            'by_day' => $this->getTimeToActivationByDay($userType, $startDate, $endDate),
        ];
    }

    /**
     * Get time to activation grouped by day
     */
    protected function getTimeToActivationByDay(?string $userType, Carbon $startDate, Carbon $endDate): array
    {
        $events = OnboardingEvent::ofType(OnboardingEvent::EVENT_ONBOARDING_COMPLETED)
            ->inDateRange($startDate, $endDate)
            ->with('user')
            ->get();

        $byDay = [];

        foreach ($events as $event) {
            if (!$event->user) continue;
            if ($userType && $event->user->user_type !== $userType) continue;

            $day = $event->created_at->toDateString();
            $hours = $event->user->created_at->diffInHours($event->created_at);

            if (!isset($byDay[$day])) {
                $byDay[$day] = ['times' => [], 'count' => 0];
            }

            $byDay[$day]['times'][] = $hours;
            $byDay[$day]['count']++;
        }

        $result = [];
        foreach ($byDay as $day => $data) {
            $result[] = [
                'date' => $day,
                'average_hours' => round(array_sum($data['times']) / count($data['times']), 2),
                'completions' => $data['count'],
            ];
        }

        return $result;
    }

    /**
     * Get dropout rates by step
     */
    public function getDropoffRates(?string $userType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $steps = OnboardingStep::when($userType, fn($q) => $q->forUserType($userType))
            ->active()
            ->ordered()
            ->get();

        $results = [];
        $previousCompleted = null;

        foreach ($steps as $step) {
            // Count users who reached this step
            $reached = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->where('status', '!=', 'pending')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->count();

            // Count users who completed this step
            $completed = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->where('status', 'completed')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->count();

            // Calculate dropoff
            $dropoff = $reached > 0 ? round((($reached - $completed) / $reached) * 100, 2) : 0;

            // Calculate step-to-step conversion
            $conversionFromPrevious = $previousCompleted !== null && $previousCompleted > 0
                ? round(($reached / $previousCompleted) * 100, 2)
                : 100;

            $results[] = [
                'step_id' => $step->step_id,
                'name' => $step->name,
                'order' => $step->order,
                'step_type' => $step->step_type,
                'reached' => $reached,
                'completed' => $completed,
                'dropoff_rate' => $dropoff,
                'conversion_from_previous' => $conversionFromPrevious,
            ];

            $previousCompleted = $completed;
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_type' => $userType ?? 'all',
            'steps' => $results,
            'worst_dropoff' => collect($results)->sortByDesc('dropoff_rate')->first(),
        ];
    }

    /**
     * Get funnel data for visualization
     */
    public function getFunnelData(?string $userType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        // Get total signups
        $totalSignups = User::whereBetween('created_at', [$startDate, $endDate])
            ->when($userType, fn($q) => $q->where('user_type', $userType))
            ->count();

        // Get users who started onboarding
        $startedOnboarding = OnboardingEvent::ofType(OnboardingEvent::EVENT_ONBOARDING_STARTED)
            ->inDateRange($startDate, $endDate)
            ->when($userType, function ($q) use ($userType) {
                $q->whereHas('user', fn($sq) => $sq->where('user_type', $userType));
            })
            ->distinct('user_id')
            ->count('user_id');

        // Get step completion counts
        $steps = OnboardingStep::when($userType, fn($q) => $q->forUserType($userType))
            ->active()
            ->required()
            ->ordered()
            ->get();

        $funnelSteps = [
            [
                'name' => 'Signed Up',
                'count' => $totalSignups,
                'percentage' => 100,
            ],
            [
                'name' => 'Started Onboarding',
                'count' => $startedOnboarding,
                'percentage' => $totalSignups > 0 ? round(($startedOnboarding / $totalSignups) * 100, 1) : 0,
            ],
        ];

        foreach ($steps as $step) {
            $completed = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->where('status', 'completed')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->count();

            $funnelSteps[] = [
                'name' => $step->name,
                'step_id' => $step->step_id,
                'count' => $completed,
                'percentage' => $totalSignups > 0 ? round(($completed / $totalSignups) * 100, 1) : 0,
            ];
        }

        // Add completed onboarding
        $completedOnboarding = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('onboarding_completed', true)
            ->when($userType, fn($q) => $q->where('user_type', $userType))
            ->count();

        $funnelSteps[] = [
            'name' => 'Completed Onboarding',
            'count' => $completedOnboarding,
            'percentage' => $totalSignups > 0 ? round(($completedOnboarding / $totalSignups) * 100, 1) : 0,
        ];

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_type' => $userType ?? 'all',
            'funnel' => $funnelSteps,
        ];
    }

    /**
     * Get step conversion rates
     */
    public function getStepConversionRates(?string $userType = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $steps = OnboardingStep::when($userType, fn($q) => $q->forUserType($userType))
            ->active()
            ->ordered()
            ->get();

        $rates = [];

        foreach ($steps as $step) {
            // Users who started the step
            $started = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->whereNotNull('started_at')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->count();

            // Users who completed the step
            $completed = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->where('status', 'completed')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->count();

            // Average time to complete
            $avgTime = OnboardingProgress::where('onboarding_step_id', $step->id)
                ->where('status', 'completed')
                ->whereHas('user', function ($q) use ($startDate, $endDate, $userType) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                    if ($userType) {
                        $q->where('user_type', $userType);
                    }
                })
                ->avg('time_spent_seconds');

            $rates[] = [
                'step_id' => $step->step_id,
                'name' => $step->name,
                'step_type' => $step->step_type,
                'started' => $started,
                'completed' => $completed,
                'conversion_rate' => $started > 0 ? round(($completed / $started) * 100, 2) : 0,
                'avg_time_minutes' => $avgTime ? round($avgTime / 60, 1) : null,
            ];
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_type' => $userType ?? 'all',
            'rates' => $rates,
        ];
    }

    /**
     * Get cohort performance comparison
     */
    public function getCohortPerformance(?string $experimentName = null): array
    {
        $query = OnboardingCohort::query();

        if ($experimentName) {
            $query->forExperiment($experimentName);
        }

        $cohorts = $query->orderBy('experiment_name')->orderBy('variant')->get();

        // Refresh metrics for active cohorts
        foreach ($cohorts as $cohort) {
            if ($cohort->isRunning()) {
                $cohort->refreshMetrics();
            }
        }

        // Group by experiment
        $experiments = [];
        foreach ($cohorts as $cohort) {
            $expName = $cohort->experiment_name;
            if (!isset($experiments[$expName])) {
                $experiments[$expName] = [
                    'experiment_name' => $expName,
                    'cohorts' => [],
                    'winner' => null,
                ];
            }

            $cohortData = [
                'cohort_id' => $cohort->cohort_id,
                'name' => $cohort->name,
                'variant' => $cohort->variant,
                'status' => $cohort->status,
                'total_users' => $cohort->total_users,
                'completed_users' => $cohort->completed_users,
                'completion_rate' => $cohort->completion_rate,
                'avg_time_hours' => $cohort->avg_time_to_activation_hours,
                'dropout_rate' => $cohort->dropout_rate,
                'is_winner' => $cohort->is_winner,
            ];

            $experiments[$expName]['cohorts'][] = $cohortData;

            if ($cohort->is_winner) {
                $experiments[$expName]['winner'] = $cohort->variant;
            }
        }

        return [
            'experiments' => array_values($experiments),
            'total_experiments' => count($experiments),
            'active_experiments' => collect($cohorts)->where('status', 'active')->count(),
        ];
    }

    /**
     * Get intervention opportunities (users who need help)
     */
    public function getInterventionOpportunities(int $limit = 50): array
    {
        $opportunities = [];

        // Users stuck on a step for 3+ days
        $stuckUsers = OnboardingProgress::where('status', 'in_progress')
            ->where('started_at', '<', now()->subDays(3))
            ->with(['user', 'step'])
            ->limit($limit)
            ->get();

        foreach ($stuckUsers as $progress) {
            if (!$progress->user || $progress->user->onboarding_completed) continue;

            $opportunities[] = [
                'user_id' => $progress->user_id,
                'user_name' => $progress->user->name,
                'email' => $progress->user->email,
                'user_type' => $progress->user->user_type,
                'issue' => 'stuck_on_step',
                'stuck_step' => $progress->step?->name,
                'days_stuck' => $progress->started_at->diffInDays(now()),
                'overall_progress' => $this->calculateUserProgress($progress->user_id),
                'suggested_action' => 'send_support_offer',
            ];
        }

        // Users who signed up but never started (7+ days ago)
        $neverStarted = User::where('onboarding_completed', false)
            ->where('created_at', '<', now()->subDays(7))
            ->where('created_at', '>', now()->subDays(30))
            ->whereDoesntHave('onboardingProgress', function ($q) {
                $q->where('status', '!=', 'pending');
            })
            ->limit($limit)
            ->get();

        foreach ($neverStarted as $user) {
            $opportunities[] = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'issue' => 'never_started',
                'days_since_signup' => $user->created_at->diffInDays(now()),
                'overall_progress' => 0,
                'suggested_action' => 'send_reengagement',
            ];
        }

        // Users with failed steps
        $failedSteps = OnboardingProgress::where('status', 'failed')
            ->where('failed_at', '>', now()->subDays(7))
            ->with(['user', 'step'])
            ->limit($limit)
            ->get();

        foreach ($failedSteps as $progress) {
            if (!$progress->user || $progress->user->onboarding_completed) continue;

            $opportunities[] = [
                'user_id' => $progress->user_id,
                'user_name' => $progress->user->name,
                'email' => $progress->user->email,
                'user_type' => $progress->user->user_type,
                'issue' => 'failed_step',
                'failed_step' => $progress->step?->name,
                'failure_reason' => $progress->failure_reason,
                'overall_progress' => $this->calculateUserProgress($progress->user_id),
                'suggested_action' => 'manual_review',
            ];
        }

        // Sort by priority (stuck users first, then by days)
        usort($opportunities, function ($a, $b) {
            $priority = ['failed_step' => 1, 'stuck_on_step' => 2, 'never_started' => 3];
            $aPriority = $priority[$a['issue']] ?? 4;
            $bPriority = $priority[$b['issue']] ?? 4;

            if ($aPriority !== $bPriority) {
                return $aPriority - $bPriority;
            }

            return ($b['days_stuck'] ?? $b['days_since_signup'] ?? 0) - ($a['days_stuck'] ?? $a['days_since_signup'] ?? 0);
        });

        return [
            'opportunities' => array_slice($opportunities, 0, $limit),
            'total_count' => count($opportunities),
            'by_issue' => [
                'stuck_on_step' => collect($opportunities)->where('issue', 'stuck_on_step')->count(),
                'never_started' => collect($opportunities)->where('issue', 'never_started')->count(),
                'failed_step' => collect($opportunities)->where('issue', 'failed_step')->count(),
            ],
        ];
    }

    /**
     * Get support ticket correlation
     */
    public function getSupportTicketCorrelation(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // This would integrate with a support ticket system
        // For now, return placeholder data

        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'correlation' => [
                'onboarding_tickets' => 0,
                'total_tickets' => 0,
                'most_common_issues' => [],
                'steps_with_most_tickets' => [],
            ],
            'note' => 'Support ticket integration not yet configured',
        ];
    }

    /**
     * Get overall analytics overview
     */
    public function getOverview(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousPeriodEnd = $startDate->copy()->subDay();

        // Current period metrics
        $totalSignups = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedOnboarding = User::whereBetween('created_at', [$startDate, $endDate])
            ->where('onboarding_completed', true)
            ->count();

        // Previous period for comparison
        $prevSignups = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])->count();
        $prevCompleted = User::whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->where('onboarding_completed', true)
            ->count();

        // Calculate changes
        $signupsChange = $prevSignups > 0
            ? round((($totalSignups - $prevSignups) / $prevSignups) * 100, 1)
            : 0;

        $completionRate = $totalSignups > 0 ? round(($completedOnboarding / $totalSignups) * 100, 1) : 0;
        $prevCompletionRate = $prevSignups > 0 ? round(($prevCompleted / $prevSignups) * 100, 1) : 0;
        $completionChange = $completionRate - $prevCompletionRate;

        // Active users in onboarding
        $activeInOnboarding = User::where('onboarding_completed', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        // Reminder stats
        $reminderStats = OnboardingReminder::getStatistics($startDate, $endDate);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => [
                'total_signups' => $totalSignups,
                'signups_change' => $signupsChange,
                'completed_onboarding' => $completedOnboarding,
                'completion_rate' => $completionRate,
                'completion_change' => $completionChange,
                'active_in_onboarding' => $activeInOnboarding,
            ],
            'reminders' => $reminderStats,
            'by_user_type' => [
                'worker' => $this->calculateCompletionRate('worker', $startDate, $endDate),
                'business' => $this->calculateCompletionRate('business', $startDate, $endDate),
            ],
        ];
    }

    /**
     * Calculate progress for a specific user
     */
    protected function calculateUserProgress(int $userId): float
    {
        $progress = OnboardingProgress::forUser($userId)->with('step')->get();

        if ($progress->isEmpty()) {
            return 0;
        }

        $totalWeight = $progress->sum(fn($p) => $p->step?->weight ?? 0);
        $completedWeight = $progress
            ->where('status', 'completed')
            ->sum(fn($p) => $p->step?->weight ?? 0);

        return $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 1) : 0;
    }

    /**
     * Generate daily onboarding report
     */
    public function generateDailyReport(?Carbon $date = null): array
    {
        $date = $date ?? now()->subDay();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return [
            'date' => $date->toDateString(),
            'summary' => $this->getOverview($startOfDay, $endOfDay),
            'funnel' => $this->getFunnelData(null, $startOfDay, $endOfDay),
            'dropoff' => $this->getDropoffRates(null, $startOfDay, $endOfDay),
            'time_to_activation' => $this->getAverageTimeToActivation(null, $startOfDay, $endOfDay),
            'interventions_needed' => $this->getInterventionOpportunities(20),
        ];
    }
}
