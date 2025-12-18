<?php

namespace App\Services;

use App\Models\ImprovementMetric;
use App\Models\ImprovementSuggestion;
use App\Models\Rating;
use App\Models\Shift;
use App\Models\SuggestionVote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * QUA-005: Continuous Improvement Service
 *
 * Manages platform improvement suggestions, voting, metrics tracking,
 * and generates improvement reports for the OvertimeStaff platform.
 */
class ContinuousImprovementService
{
    /**
     * Submit a new improvement suggestion.
     */
    public function submitSuggestion(User $user, array $data): ImprovementSuggestion
    {
        return ImprovementSuggestion::create([
            'submitted_by' => $user->id,
            'category' => $data['category'],
            'priority' => $data['priority'] ?? ImprovementSuggestion::PRIORITY_MEDIUM,
            'title' => $data['title'],
            'description' => $data['description'],
            'expected_impact' => $data['expected_impact'] ?? null,
            'status' => ImprovementSuggestion::STATUS_SUBMITTED,
            'votes' => 0,
        ]);
    }

    /**
     * Vote on a suggestion.
     */
    public function voteSuggestion(
        ImprovementSuggestion $suggestion,
        User $user,
        string $voteType = SuggestionVote::TYPE_UP
    ): array {
        // Prevent voting on own suggestion
        if ($suggestion->submitted_by === $user->id) {
            return [
                'success' => false,
                'message' => 'You cannot vote on your own suggestion.',
            ];
        }

        // Check if suggestion can be voted on
        if (! $suggestion->canBeVotedOn()) {
            return [
                'success' => false,
                'message' => 'This suggestion is no longer accepting votes.',
            ];
        }

        // Check for existing vote
        $existingVote = SuggestionVote::where('suggestion_id', $suggestion->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $voteType) {
                // Remove the vote (toggle off)
                $existingVote->delete();

                return [
                    'success' => true,
                    'message' => 'Your vote has been removed.',
                    'votes' => $suggestion->fresh()->votes,
                    'user_vote' => null,
                ];
            }

            // Change vote type
            $existingVote->update(['vote_type' => $voteType]);

            return [
                'success' => true,
                'message' => 'Your vote has been updated.',
                'votes' => $suggestion->fresh()->votes,
                'user_vote' => $voteType,
            ];
        }

        // Create new vote
        SuggestionVote::create([
            'suggestion_id' => $suggestion->id,
            'user_id' => $user->id,
            'vote_type' => $voteType,
        ]);

        return [
            'success' => true,
            'message' => 'Your vote has been recorded.',
            'votes' => $suggestion->fresh()->votes,
            'user_vote' => $voteType,
        ];
    }

    /**
     * Review a suggestion (admin action).
     */
    public function reviewSuggestion(
        ImprovementSuggestion $suggestion,
        string $status,
        ?string $notes = null,
        ?int $assignedTo = null,
        ?string $rejectionReason = null
    ): ImprovementSuggestion {
        $updateData = [
            'status' => $status,
            'reviewed_at' => $suggestion->reviewed_at ?? now(),
        ];

        if ($notes) {
            $updateData['admin_notes'] = $notes;
        }

        if ($assignedTo) {
            $updateData['assigned_to'] = $assignedTo;
        }

        if ($status === ImprovementSuggestion::STATUS_REJECTED && $rejectionReason) {
            $updateData['rejection_reason'] = $rejectionReason;
        }

        if ($status === ImprovementSuggestion::STATUS_COMPLETED) {
            $updateData['completed_at'] = now();
        }

        $suggestion->update($updateData);

        return $suggestion->fresh();
    }

    /**
     * Get top voted suggestions.
     */
    public function getTopSuggestions(int $limit = 10): Collection
    {
        return ImprovementSuggestion::with(['submitter', 'assignee'])
            ->public()
            ->topVoted()
            ->limit($limit)
            ->get();
    }

    /**
     * Get suggestions by category.
     */
    public function getSuggestionsByCategory(string $category): Collection
    {
        return ImprovementSuggestion::with(['submitter', 'assignee'])
            ->withCategory($category)
            ->public()
            ->recent()
            ->get();
    }

    /**
     * Get suggestions by status.
     */
    public function getSuggestionsByStatus(string $status): Collection
    {
        return ImprovementSuggestion::with(['submitter', 'assignee'])
            ->withStatus($status)
            ->recent()
            ->get();
    }

    /**
     * Get suggestions pending review.
     */
    public function getPendingSuggestions(): Collection
    {
        return ImprovementSuggestion::with(['submitter'])
            ->pendingReview()
            ->topVoted()
            ->get();
    }

    /**
     * Get user's submitted suggestions.
     */
    public function getUserSuggestions(User $user): Collection
    {
        return ImprovementSuggestion::with(['assignee'])
            ->where('submitted_by', $user->id)
            ->recent()
            ->get();
    }

    /**
     * Record a metric value.
     */
    public function recordMetric(string $key, float $value, ?string $name = null): ImprovementMetric
    {
        $metric = ImprovementMetric::findOrCreateByKey($key, [
            'name' => $name ?? ucwords(str_replace('_', ' ', $key)),
        ]);

        $metric->recordValue($value);

        return $metric->fresh();
    }

    /**
     * Get trend data for a metric.
     */
    public function getMetricTrend(string $key, int $days = 30): array
    {
        $metric = ImprovementMetric::byKey($key)->first();

        if (! $metric) {
            return [
                'metric' => null,
                'trend_data' => [],
                'average' => 0,
                'change_percent' => 0,
            ];
        }

        $trendData = $metric->getTrendForDays($days);
        $average = $metric->getAverageForDays($days);

        // Calculate change percentage
        $changePercent = 0;
        if (count($trendData) >= 2) {
            $firstValue = $trendData[0]['value'] ?? 0;
            $lastValue = end($trendData)['value'] ?? 0;

            if ($firstValue > 0) {
                $changePercent = (($lastValue - $firstValue) / $firstValue) * 100;
            }
        }

        return [
            'metric' => $metric,
            'trend_data' => $trendData,
            'average' => round($average, 2),
            'change_percent' => round($changePercent, 2),
        ];
    }

    /**
     * Generate a comprehensive improvement report.
     */
    public function generateImprovementReport(): array
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        return [
            'generated_at' => $now->toIso8601String(),
            'period' => [
                'start' => $thirtyDaysAgo->toDateString(),
                'end' => $now->toDateString(),
            ],
            'suggestions' => $this->getSuggestionStats(),
            'metrics' => $this->getMetricsSummary(),
            'platform_health' => $this->calculatePlatformHealthScore(),
            'top_priorities' => $this->getTopPriorities(),
            'recent_completions' => $this->getRecentCompletions(),
            'trends' => $this->getOverallTrends(),
        ];
    }

    /**
     * Calculate platform health score (0-100).
     */
    public function calculatePlatformHealthScore(): array
    {
        $scores = [];
        $weights = [];

        // Shift Fill Rate (weight: 25%)
        $shiftFillRate = $this->calculateShiftFillRate();
        $scores['shift_fill_rate'] = min(100, $shiftFillRate);
        $weights['shift_fill_rate'] = 0.25;

        // Worker Satisfaction (weight: 20%)
        $workerSatisfaction = $this->calculateWorkerSatisfaction();
        $scores['worker_satisfaction'] = min(100, $workerSatisfaction * 20); // Convert 5-star to 100
        $weights['worker_satisfaction'] = 0.20;

        // Business Retention (weight: 20%)
        $businessRetention = $this->calculateBusinessRetention();
        $scores['business_retention'] = min(100, $businessRetention);
        $weights['business_retention'] = 0.20;

        // Cancellation Rate (lower is better, weight: 15%)
        $cancellationRate = $this->calculateCancellationRate();
        $scores['cancellation_rate'] = max(0, 100 - $cancellationRate);
        $weights['cancellation_rate'] = 0.15;

        // Response Time Score (weight: 10%)
        $responseTimeScore = $this->calculateResponseTimeScore();
        $scores['response_time'] = min(100, $responseTimeScore);
        $weights['response_time'] = 0.10;

        // Suggestion Implementation Rate (weight: 10%)
        $implementationRate = $this->calculateSuggestionImplementationRate();
        $scores['implementation_rate'] = min(100, $implementationRate);
        $weights['implementation_rate'] = 0.10;

        // Calculate weighted score
        $totalScore = 0;
        foreach ($scores as $key => $score) {
            $totalScore += $score * $weights[$key];
        }

        // Determine grade
        $grade = match (true) {
            $totalScore >= 90 => 'A',
            $totalScore >= 80 => 'B',
            $totalScore >= 70 => 'C',
            $totalScore >= 60 => 'D',
            default => 'F',
        };

        // Record the score as a metric
        $this->recordMetric(
            ImprovementMetric::METRIC_PLATFORM_HEALTH,
            $totalScore,
            'Platform Health Score'
        );

        return [
            'overall_score' => round($totalScore, 1),
            'grade' => $grade,
            'components' => $scores,
            'weights' => $weights,
        ];
    }

    /**
     * Get suggestion statistics.
     */
    protected function getSuggestionStats(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $allSuggestions = ImprovementSuggestion::all();
        $recentSuggestions = ImprovementSuggestion::where('created_at', '>=', $thirtyDaysAgo)->get();

        return [
            'total' => $allSuggestions->count(),
            'by_status' => [
                'submitted' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_SUBMITTED)->count(),
                'under_review' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_UNDER_REVIEW)->count(),
                'approved' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_APPROVED)->count(),
                'in_progress' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_IN_PROGRESS)->count(),
                'completed' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_COMPLETED)->count(),
                'rejected' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_REJECTED)->count(),
                'deferred' => $allSuggestions->where('status', ImprovementSuggestion::STATUS_DEFERRED)->count(),
            ],
            'by_category' => [
                'feature' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_FEATURE)->count(),
                'bug' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_BUG)->count(),
                'ux' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_UX)->count(),
                'process' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_PROCESS)->count(),
                'performance' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_PERFORMANCE)->count(),
                'other' => $allSuggestions->where('category', ImprovementSuggestion::CATEGORY_OTHER)->count(),
            ],
            'last_30_days' => [
                'submitted' => $recentSuggestions->count(),
                'completed' => $recentSuggestions->where('status', ImprovementSuggestion::STATUS_COMPLETED)->count(),
            ],
            'total_votes' => SuggestionVote::count(),
            'avg_votes_per_suggestion' => $allSuggestions->count() > 0
                ? round(SuggestionVote::count() / $allSuggestions->count(), 1)
                : 0,
        ];
    }

    /**
     * Get metrics summary.
     */
    protected function getMetricsSummary(): array
    {
        $metrics = ImprovementMetric::all();

        return [
            'total_tracked' => $metrics->count(),
            'improving' => $metrics->where('trend', ImprovementMetric::TREND_UP)->count(),
            'declining' => $metrics->where('trend', ImprovementMetric::TREND_DOWN)->count(),
            'stable' => $metrics->where('trend', ImprovementMetric::TREND_STABLE)->count(),
            'on_target' => $metrics->filter(fn ($m) => $m->isOnTarget())->count(),
            'below_target' => $metrics->filter(fn ($m) => ! $m->isOnTarget() && $m->target_value)->count(),
            'key_metrics' => $metrics->map(fn ($m) => [
                'key' => $m->metric_key,
                'name' => $m->name,
                'value' => $m->current_value,
                'formatted_value' => $m->formatted_value,
                'trend' => $m->trend,
                'target' => $m->target_value,
                'on_target' => $m->isOnTarget(),
            ])->toArray(),
        ];
    }

    /**
     * Get top priority items.
     */
    protected function getTopPriorities(): array
    {
        $criticalSuggestions = ImprovementSuggestion::where('priority', ImprovementSuggestion::PRIORITY_CRITICAL)
            ->active()
            ->with('submitter')
            ->topVoted()
            ->limit(5)
            ->get();

        $decliningMetrics = ImprovementMetric::withTrend(ImprovementMetric::TREND_DOWN)
            ->belowTarget()
            ->limit(5)
            ->get();

        return [
            'critical_suggestions' => $criticalSuggestions->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'category' => $s->category,
                'votes' => $s->votes,
                'status' => $s->status,
            ])->toArray(),
            'declining_metrics' => $decliningMetrics->map(fn ($m) => [
                'key' => $m->metric_key,
                'name' => $m->name,
                'current_value' => $m->current_value,
                'target_value' => $m->target_value,
                'gap' => $m->target_value ? round($m->target_value - $m->current_value, 2) : null,
            ])->toArray(),
        ];
    }

    /**
     * Get recent completions.
     */
    protected function getRecentCompletions(): array
    {
        return ImprovementSuggestion::where('status', ImprovementSuggestion::STATUS_COMPLETED)
            ->where('completed_at', '>=', Carbon::now()->subDays(30))
            ->with(['submitter', 'assignee'])
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'category' => $s->category,
                'completed_at' => $s->completed_at?->toDateString(),
                'submitted_by' => $s->submitter?->name,
                'assigned_to' => $s->assignee?->name,
                'days_to_complete' => $s->created_at->diffInDays($s->completed_at),
            ])
            ->toArray();
    }

    /**
     * Get overall trends.
     */
    protected function getOverallTrends(): array
    {
        $trends = [];

        $keyMetrics = [
            ImprovementMetric::METRIC_SHIFT_FILL_RATE,
            ImprovementMetric::METRIC_WORKER_SATISFACTION,
            ImprovementMetric::METRIC_BUSINESS_RETENTION,
            ImprovementMetric::METRIC_AVG_RESPONSE_TIME,
        ];

        foreach ($keyMetrics as $metricKey) {
            $trendData = $this->getMetricTrend($metricKey, 30);
            if ($trendData['metric']) {
                $trends[$metricKey] = [
                    'name' => $trendData['metric']->name,
                    'trend' => $trendData['metric']->trend,
                    'change_percent' => $trendData['change_percent'],
                    'current_value' => $trendData['metric']->current_value,
                ];
            }
        }

        return $trends;
    }

    /**
     * Calculate shift fill rate.
     */
    protected function calculateShiftFillRate(): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $shifts = Shift::where('shift_date', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['completed', 'in_progress', 'filled'])
            ->get();

        if ($shifts->isEmpty()) {
            return 100;
        }

        $totalSlots = $shifts->sum('workers_needed');
        $filledSlots = $shifts->sum('filled_workers');

        if ($totalSlots === 0) {
            return 100;
        }

        $fillRate = ($filledSlots / $totalSlots) * 100;

        // Record the metric
        $this->recordMetric(
            ImprovementMetric::METRIC_SHIFT_FILL_RATE,
            $fillRate,
            'Shift Fill Rate'
        );

        return $fillRate;
    }

    /**
     * Calculate worker satisfaction score.
     */
    protected function calculateWorkerSatisfaction(): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $avgRating = Rating::where('created_at', '>=', $thirtyDaysAgo)
            ->where('rater_type', 'worker')
            ->avg('rating');

        $satisfaction = $avgRating ?? 4.0;

        // Record the metric
        $this->recordMetric(
            ImprovementMetric::METRIC_WORKER_SATISFACTION,
            $satisfaction,
            'Worker Satisfaction Score'
        );

        return $satisfaction;
    }

    /**
     * Calculate business retention rate.
     */
    protected function calculateBusinessRetention(): float
    {
        $sixtyDaysAgo = Carbon::now()->subDays(60);
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Businesses active in the first period
        $businessesFirstPeriod = Shift::where('shift_date', '>=', $sixtyDaysAgo)
            ->where('shift_date', '<', $thirtyDaysAgo)
            ->distinct('business_id')
            ->pluck('business_id');

        if ($businessesFirstPeriod->isEmpty()) {
            return 100;
        }

        // How many of them are still active in the second period
        $retainedBusinesses = Shift::where('shift_date', '>=', $thirtyDaysAgo)
            ->whereIn('business_id', $businessesFirstPeriod)
            ->distinct('business_id')
            ->count();

        $retentionRate = ($retainedBusinesses / $businessesFirstPeriod->count()) * 100;

        // Record the metric
        $this->recordMetric(
            ImprovementMetric::METRIC_BUSINESS_RETENTION,
            $retentionRate,
            'Business Retention Rate'
        );

        return $retentionRate;
    }

    /**
     * Calculate cancellation rate.
     */
    protected function calculateCancellationRate(): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $totalShifts = Shift::where('shift_date', '>=', $thirtyDaysAgo)->count();

        if ($totalShifts === 0) {
            return 0;
        }

        $cancelledShifts = Shift::where('shift_date', '>=', $thirtyDaysAgo)
            ->where('status', 'cancelled')
            ->count();

        $cancellationRate = ($cancelledShifts / $totalShifts) * 100;

        // Record the metric
        $this->recordMetric(
            ImprovementMetric::METRIC_CANCELLATION_RATE,
            $cancellationRate,
            'Cancellation Rate'
        );

        return $cancellationRate;
    }

    /**
     * Calculate response time score (0-100).
     */
    protected function calculateResponseTimeScore(): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Average time from shift creation to first application
        $avgResponseSeconds = Shift::where('created_at', '>=', $thirtyDaysAgo)
            ->whereHas('applications')
            ->with(['applications' => function ($q) {
                $q->orderBy('created_at')->limit(1);
            }])
            ->get()
            ->map(function ($shift) {
                $firstApplication = $shift->applications->first();
                if ($firstApplication) {
                    return $shift->created_at->diffInSeconds($firstApplication->created_at);
                }

                return null;
            })
            ->filter()
            ->avg();

        if (! $avgResponseSeconds) {
            return 100;
        }

        // Record the metric
        $this->recordMetric(
            ImprovementMetric::METRIC_AVG_RESPONSE_TIME,
            $avgResponseSeconds,
            'Average Response Time'
        );

        // Score based on response time (target: under 30 minutes = 100, over 4 hours = 0)
        $targetSeconds = 30 * 60; // 30 minutes
        $maxSeconds = 4 * 60 * 60; // 4 hours

        if ($avgResponseSeconds <= $targetSeconds) {
            return 100;
        }

        if ($avgResponseSeconds >= $maxSeconds) {
            return 0;
        }

        return 100 - (($avgResponseSeconds - $targetSeconds) / ($maxSeconds - $targetSeconds)) * 100;
    }

    /**
     * Calculate suggestion implementation rate.
     */
    protected function calculateSuggestionImplementationRate(): float
    {
        $totalApproved = ImprovementSuggestion::whereIn('status', [
            ImprovementSuggestion::STATUS_APPROVED,
            ImprovementSuggestion::STATUS_IN_PROGRESS,
            ImprovementSuggestion::STATUS_COMPLETED,
        ])->count();

        if ($totalApproved === 0) {
            return 100;
        }

        $completed = ImprovementSuggestion::where('status', ImprovementSuggestion::STATUS_COMPLETED)
            ->count();

        return ($completed / $totalApproved) * 100;
    }

    /**
     * Update all platform metrics (to be run periodically).
     */
    public function updateAllMetrics(): void
    {
        $this->calculateShiftFillRate();
        $this->calculateWorkerSatisfaction();
        $this->calculateBusinessRetention();
        $this->calculateCancellationRate();
        $this->calculateResponseTimeScore();
        $this->calculatePlatformHealthScore();
    }

    /**
     * Get dashboard data for admins.
     */
    public function getAdminDashboard(): array
    {
        return [
            'pending_suggestions' => $this->getPendingSuggestions()->count(),
            'in_progress' => ImprovementSuggestion::where('status', ImprovementSuggestion::STATUS_IN_PROGRESS)->count(),
            'completed_this_month' => ImprovementSuggestion::where('status', ImprovementSuggestion::STATUS_COMPLETED)
                ->where('completed_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
            'health_score' => $this->calculatePlatformHealthScore(),
            'top_suggestions' => $this->getTopSuggestions(5),
            'declining_metrics' => ImprovementMetric::withTrend(ImprovementMetric::TREND_DOWN)->get(),
            'recent_activity' => ImprovementSuggestion::with('submitter')
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}
