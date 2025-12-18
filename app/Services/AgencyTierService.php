<?php

namespace App\Services;

use App\Models\AgencyTier;
use App\Models\AgencyTierHistory;
use App\Models\AgencyWorker;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AgencyTierService
 *
 * Manages agency tier calculations, upgrades, downgrades, and reviews.
 *
 * TASK: AGY-001 Agency Tier System
 *
 * Features:
 * - Calculate agency performance metrics
 * - Determine eligible tier based on metrics
 * - Process tier upgrades and downgrades
 * - Monthly tier review automation
 * - Dashboard metrics and progress tracking
 */
class AgencyTierService
{
    /**
     * Grace period before downgrade (in days).
     */
    public const DOWNGRADE_GRACE_PERIOD_DAYS = 30;

    /**
     * Minimum days at current tier before upgrade review.
     */
    public const MINIMUM_DAYS_FOR_UPGRADE = 30;

    /**
     * Calculate current performance metrics for an agency.
     *
     * @return array{monthly_revenue: float, active_workers: int, fill_rate: float, rating: float, total_shifts: int, completed_shifts: int, calculated_at: string}
     */
    public function calculateAgencyMetrics(User $agency): array
    {
        $profile = $agency->agencyProfile;

        if (! $profile) {
            return $this->getEmptyMetrics();
        }

        // Calculate monthly revenue (last 30 days)
        $monthlyRevenue = $this->calculateMonthlyRevenue($agency);

        // Count active workers
        $activeWorkers = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        // Calculate fill rate (completed / total assigned in last 90 days)
        $fillRateData = $this->calculateFillRate($agency);

        // Calculate average rating
        $averageRating = $this->calculateAverageRating($agency);

        return [
            'monthly_revenue' => $monthlyRevenue,
            'active_workers' => $activeWorkers,
            'fill_rate' => $fillRateData['fill_rate'],
            'rating' => $averageRating,
            'total_shifts' => $fillRateData['total_shifts'],
            'completed_shifts' => $fillRateData['completed_shifts'],
            'cancelled_shifts' => $fillRateData['cancelled_shifts'],
            'no_show_shifts' => $fillRateData['no_show_shifts'],
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate monthly revenue for an agency.
     */
    protected function calculateMonthlyRevenue(User $agency): float
    {
        $startDate = now()->subDays(30)->startOfDay();

        // Get completed shift payments for agency workers
        $workerIds = AgencyWorker::where('agency_id', $agency->id)
            ->pluck('worker_id');

        if ($workerIds->isEmpty()) {
            return 0.0;
        }

        $revenue = ShiftPayment::whereIn('worker_id', $workerIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        return (float) $revenue;
    }

    /**
     * Calculate fill rate metrics.
     *
     * @return array{fill_rate: float, total_shifts: int, completed_shifts: int, cancelled_shifts: int, no_show_shifts: int}
     */
    protected function calculateFillRate(User $agency): array
    {
        $startDate = now()->subDays(90)->startOfDay();

        $workerIds = AgencyWorker::where('agency_id', $agency->id)
            ->pluck('worker_id');

        if ($workerIds->isEmpty()) {
            return [
                'fill_rate' => 0.0,
                'total_shifts' => 0,
                'completed_shifts' => 0,
                'cancelled_shifts' => 0,
                'no_show_shifts' => 0,
            ];
        }

        $assignments = ShiftAssignment::whereIn('worker_id', $workerIds)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalShifts = $assignments->count();
        $completedShifts = $assignments->where('status', 'completed')->count();
        $cancelledShifts = $assignments->where('status', 'cancelled')->count();
        $noShowShifts = $assignments->where('status', 'no_show')->count();

        $fillRate = $totalShifts > 0
            ? ($completedShifts / $totalShifts) * 100
            : 0.0;

        return [
            'fill_rate' => round($fillRate, 2),
            'total_shifts' => $totalShifts,
            'completed_shifts' => $completedShifts,
            'cancelled_shifts' => $cancelledShifts,
            'no_show_shifts' => $noShowShifts,
        ];
    }

    /**
     * Calculate average rating for agency workers.
     */
    protected function calculateAverageRating(User $agency): float
    {
        $workerIds = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->pluck('worker_id');

        if ($workerIds->isEmpty()) {
            return 0.0;
        }

        $averageRating = DB::table('ratings')
            ->whereIn('rated_id', $workerIds)
            ->where('rated_type', 'worker')
            ->avg('rating');

        return round((float) ($averageRating ?? 0), 2);
    }

    /**
     * Get empty metrics array for agencies without data.
     */
    protected function getEmptyMetrics(): array
    {
        return [
            'monthly_revenue' => 0.0,
            'active_workers' => 0,
            'fill_rate' => 0.0,
            'rating' => 0.0,
            'total_shifts' => 0,
            'completed_shifts' => 0,
            'cancelled_shifts' => 0,
            'no_show_shifts' => 0,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine the eligible tier for an agency based on their metrics.
     */
    public function determineEligibleTier(User $agency): ?AgencyTier
    {
        $metrics = $this->calculateAgencyMetrics($agency);

        // Get all active tiers ordered by level descending (highest first)
        $tiers = AgencyTier::active()
            ->orderBy('level', 'desc')
            ->get();

        foreach ($tiers as $tier) {
            if ($tier->meetsRequirements($metrics)) {
                return $tier;
            }
        }

        // Return lowest tier if no requirements met
        return AgencyTier::active()->orderBy('level')->first();
    }

    /**
     * Upgrade an agency to a new tier.
     */
    public function upgradeTier(User $agency, AgencyTier $newTier, ?int $processedBy = null): AgencyTierHistory
    {
        // Reload profile with tier relationship to ensure fresh data
        $profile = $agency->agencyProfile()->with('tier')->first();
        $currentTier = $profile->tier;
        $metrics = $this->calculateAgencyMetrics($agency);

        return DB::transaction(function () use ($agency, $profile, $currentTier, $newTier, $metrics, $processedBy) {
            // Update profile with new tier
            $profile->update([
                'agency_tier_id' => $newTier->id,
                'tier_achieved_at' => now(),
                'tier_review_at' => now()->addMonth(),
                'tier_metrics_snapshot' => $metrics,
                'commission_rate' => $newTier->commission_rate,
            ]);

            // Create history record
            $history = AgencyTierHistory::create([
                'agency_id' => $agency->id,
                'from_tier_id' => $currentTier?->id,
                'to_tier_id' => $newTier->id,
                'change_type' => $currentTier ? AgencyTierHistory::CHANGE_TYPE_UPGRADE : AgencyTierHistory::CHANGE_TYPE_INITIAL,
                'metrics_at_change' => $metrics,
                'notes' => $currentTier
                    ? "Upgraded from {$currentTier->name} to {$newTier->name}"
                    : "Initial tier assignment: {$newTier->name}",
                'processed_by' => $processedBy,
            ]);

            Log::info('Agency tier upgraded', [
                'agency_id' => $agency->id,
                'from_tier' => $currentTier?->name,
                'to_tier' => $newTier->name,
                'metrics' => $metrics,
            ]);

            return $history;
        });
    }

    /**
     * Downgrade an agency to a new tier.
     */
    public function downgradeTier(User $agency, AgencyTier $newTier, ?int $processedBy = null, ?string $reason = null): AgencyTierHistory
    {
        // Reload profile with tier relationship to ensure fresh data
        $profile = $agency->agencyProfile()->with('tier')->first();
        $currentTier = $profile->tier;
        $metrics = $this->calculateAgencyMetrics($agency);

        return DB::transaction(function () use ($agency, $profile, $currentTier, $newTier, $metrics, $processedBy, $reason) {
            // Update profile with new tier
            $profile->update([
                'agency_tier_id' => $newTier->id,
                'tier_achieved_at' => now(),
                'tier_review_at' => now()->addMonth(),
                'tier_metrics_snapshot' => $metrics,
                'commission_rate' => $newTier->commission_rate,
            ]);

            $fromTierName = $currentTier?->name ?? 'None';
            $notes = $reason ?? "Downgraded from {$fromTierName} to {$newTier->name} due to metrics not meeting tier requirements";

            // Create history record
            $history = AgencyTierHistory::create([
                'agency_id' => $agency->id,
                'from_tier_id' => $currentTier?->id,
                'to_tier_id' => $newTier->id,
                'change_type' => AgencyTierHistory::CHANGE_TYPE_DOWNGRADE,
                'metrics_at_change' => $metrics,
                'notes' => $notes,
                'processed_by' => $processedBy,
            ]);

            Log::warning('Agency tier downgraded', [
                'agency_id' => $agency->id,
                'from_tier' => $currentTier?->name,
                'to_tier' => $newTier->name,
                'reason' => $reason,
                'metrics' => $metrics,
            ]);

            return $history;
        });
    }

    /**
     * Process monthly tier review for all agencies.
     *
     * @return array{total_reviewed: int, upgrades: int, downgrades: int, no_change: int, errors: int}
     */
    public function processMonthlyTierReview(): array
    {
        $summary = [
            'total_reviewed' => 0,
            'upgrades' => 0,
            'downgrades' => 0,
            'no_change' => 0,
            'errors' => 0,
        ];

        // Get all agencies with tier review due
        $agencies = User::where('user_type', 'agency')
            ->whereHas('agencyProfile', function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('tier_review_at')
                        ->orWhere('tier_review_at', '<=', now());
                });
            })
            ->with('agencyProfile.tier')
            ->get();

        foreach ($agencies as $agency) {
            try {
                $result = $this->reviewAgencyTier($agency);
                $summary['total_reviewed']++;

                match ($result) {
                    'upgrade' => $summary['upgrades']++,
                    'downgrade' => $summary['downgrades']++,
                    default => $summary['no_change']++,
                };
            } catch (\Exception $e) {
                $summary['errors']++;
                Log::error('Error reviewing agency tier', [
                    'agency_id' => $agency->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Monthly tier review completed', $summary);

        return $summary;
    }

    /**
     * Review and update a single agency's tier.
     *
     * @return string 'upgrade', 'downgrade', or 'no_change'
     */
    public function reviewAgencyTier(User $agency): string
    {
        $profile = $agency->agencyProfile;
        $currentTier = $profile->tier;
        $eligibleTier = $this->determineEligibleTier($agency);

        if (! $eligibleTier) {
            // Assign to lowest tier if none exists
            $lowestTier = AgencyTier::active()->orderBy('level')->first();
            if ($lowestTier && (! $currentTier || $currentTier->id !== $lowestTier->id)) {
                $this->upgradeTier($agency, $lowestTier);

                return 'initial';
            }

            return 'no_change';
        }

        // No current tier - assign initial tier
        if (! $currentTier) {
            $this->upgradeTier($agency, $eligibleTier);

            return 'initial';
        }

        // Check for upgrade
        if ($eligibleTier->level > $currentTier->level) {
            // Verify minimum days at current tier
            $daysAtCurrentTier = $profile->tier_achieved_at
                ? now()->diffInDays($profile->tier_achieved_at)
                : self::MINIMUM_DAYS_FOR_UPGRADE;

            if ($daysAtCurrentTier >= self::MINIMUM_DAYS_FOR_UPGRADE) {
                $this->upgradeTier($agency, $eligibleTier);

                return 'upgrade';
            }
        }

        // Check for downgrade
        if ($eligibleTier->level < $currentTier->level) {
            // Apply grace period
            $daysAtCurrentTier = $profile->tier_achieved_at
                ? now()->diffInDays($profile->tier_achieved_at)
                : 0;

            if ($daysAtCurrentTier >= self::DOWNGRADE_GRACE_PERIOD_DAYS) {
                $this->downgradeTier($agency, $eligibleTier);

                return 'downgrade';
            } else {
                // Update review date but don't downgrade yet
                $profile->update(['tier_review_at' => now()->addDays(7)]);
            }
        }

        // Update next review date
        $profile->update(['tier_review_at' => now()->addMonth()]);

        return 'no_change';
    }

    /**
     * Get dashboard metrics for an agency.
     *
     * @return array{current_tier: array|null, metrics: array, next_tier: array|null, tier_history: array}
     */
    public function getAgencyDashboardMetrics(User $agency): array
    {
        $profile = $agency->agencyProfile;
        $currentTier = $profile?->tier;
        $metrics = $this->calculateAgencyMetrics($agency);

        $nextTier = $currentTier?->getNextTier();

        return [
            'current_tier' => $currentTier ? [
                'id' => $currentTier->id,
                'name' => $currentTier->name,
                'level' => $currentTier->level,
                'slug' => $currentTier->slug,
                'badge_color' => $currentTier->badge_color,
                'commission_rate' => $currentTier->commission_rate,
                'benefits' => $currentTier->getAllBenefits(),
                'achieved_at' => $profile->tier_achieved_at?->toIso8601String(),
            ] : null,
            'metrics' => $metrics,
            'next_tier' => $nextTier ? [
                'id' => $nextTier->id,
                'name' => $nextTier->name,
                'level' => $nextTier->level,
                'requirements' => $nextTier->getRequirements(),
                'commission_rate' => $nextTier->commission_rate,
            ] : null,
            'tier_review_at' => $profile?->tier_review_at?->toIso8601String(),
            'tier_history' => $this->getRecentTierHistory($agency),
        ];
    }

    /**
     * Get progress towards the next tier.
     *
     * @return array{next_tier: array|null, progress: array, overall_progress: float, can_upgrade: bool}
     */
    public function getTierProgress(User $agency): array
    {
        $profile = $agency->agencyProfile;
        $currentTier = $profile?->tier;
        $metrics = $this->calculateAgencyMetrics($agency);

        if (! $currentTier) {
            return [
                'next_tier' => null,
                'progress' => [],
                'overall_progress' => 0,
                'can_upgrade' => false,
            ];
        }

        $nextTier = $currentTier->getNextTier();

        if (! $nextTier) {
            return [
                'next_tier' => null,
                'progress' => [],
                'overall_progress' => 100,
                'can_upgrade' => false,
                'is_max_tier' => true,
            ];
        }

        $requirements = $nextTier->getRequirements();
        $progress = [];
        $totalProgress = 0;
        $requirementCount = 0;

        // Calculate progress for each requirement
        if (isset($requirements['revenue'])) {
            $revenueProgress = min(100, ($metrics['monthly_revenue'] / $requirements['revenue']['value']) * 100);
            $progress['revenue'] = [
                'label' => 'Monthly Revenue',
                'current' => $metrics['monthly_revenue'],
                'required' => $requirements['revenue']['value'],
                'formatted_current' => '$'.number_format($metrics['monthly_revenue'], 0),
                'formatted_required' => $requirements['revenue']['formatted'],
                'progress' => round($revenueProgress, 1),
                'met' => $metrics['monthly_revenue'] >= $requirements['revenue']['value'],
            ];
            $totalProgress += $revenueProgress;
            $requirementCount++;
        }

        if (isset($requirements['workers'])) {
            $workersProgress = min(100, ($metrics['active_workers'] / $requirements['workers']['value']) * 100);
            $progress['workers'] = [
                'label' => 'Active Workers',
                'current' => $metrics['active_workers'],
                'required' => $requirements['workers']['value'],
                'formatted_current' => number_format($metrics['active_workers']),
                'formatted_required' => $requirements['workers']['formatted'],
                'progress' => round($workersProgress, 1),
                'met' => $metrics['active_workers'] >= $requirements['workers']['value'],
            ];
            $totalProgress += $workersProgress;
            $requirementCount++;
        }

        if (isset($requirements['fill_rate'])) {
            $fillRateProgress = min(100, ($metrics['fill_rate'] / $requirements['fill_rate']['value']) * 100);
            $progress['fill_rate'] = [
                'label' => 'Fill Rate',
                'current' => $metrics['fill_rate'],
                'required' => $requirements['fill_rate']['value'],
                'formatted_current' => number_format($metrics['fill_rate'], 1).'%',
                'formatted_required' => $requirements['fill_rate']['formatted'],
                'progress' => round($fillRateProgress, 1),
                'met' => $metrics['fill_rate'] >= $requirements['fill_rate']['value'],
            ];
            $totalProgress += $fillRateProgress;
            $requirementCount++;
        }

        if (isset($requirements['rating'])) {
            $ratingProgress = $metrics['rating'] > 0
                ? min(100, ($metrics['rating'] / $requirements['rating']['value']) * 100)
                : 0;
            $progress['rating'] = [
                'label' => 'Average Rating',
                'current' => $metrics['rating'],
                'required' => $requirements['rating']['value'],
                'formatted_current' => number_format($metrics['rating'], 2),
                'formatted_required' => $requirements['rating']['formatted'],
                'progress' => round($ratingProgress, 1),
                'met' => $metrics['rating'] >= $requirements['rating']['value'],
            ];
            $totalProgress += $ratingProgress;
            $requirementCount++;
        }

        $overallProgress = $requirementCount > 0 ? $totalProgress / $requirementCount : 0;
        $canUpgrade = collect($progress)->every(fn ($item) => $item['met']);

        return [
            'next_tier' => [
                'id' => $nextTier->id,
                'name' => $nextTier->name,
                'level' => $nextTier->level,
                'commission_rate' => $nextTier->commission_rate,
                'badge_color' => $nextTier->badge_color,
            ],
            'progress' => $progress,
            'overall_progress' => round($overallProgress, 1),
            'can_upgrade' => $canUpgrade,
            'is_max_tier' => false,
        ];
    }

    /**
     * Get recent tier history for an agency.
     *
     * @return array<array{id: int, from_tier: string|null, to_tier: string, change_type: string, created_at: string}>
     */
    protected function getRecentTierHistory(User $agency, int $limit = 5): array
    {
        return AgencyTierHistory::where('agency_id', $agency->id)
            ->with(['fromTier', 'toTier'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($history) => [
                'id' => $history->id,
                'from_tier' => $history->fromTier?->name,
                'to_tier' => $history->toTier?->name,
                'change_type' => $history->change_type,
                'description' => $history->description,
                'created_at' => $history->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Assign initial tier to a new agency.
     */
    public function assignInitialTier(User $agency): ?AgencyTierHistory
    {
        $profile = $agency->agencyProfile;

        if (! $profile) {
            return null;
        }

        // Already has a tier
        if ($profile->agency_tier_id) {
            return null;
        }

        $lowestTier = AgencyTier::active()->orderBy('level')->first();

        if (! $lowestTier) {
            Log::warning('No active tiers available for initial assignment', [
                'agency_id' => $agency->id,
            ]);

            return null;
        }

        return $this->upgradeTier($agency, $lowestTier);
    }

    /**
     * Get tier distribution statistics.
     *
     * @return array<array{tier_id: int, tier_name: string, tier_level: int, agency_count: int, percentage: float}>
     */
    public function getTierDistribution(): array
    {
        $tiers = AgencyTier::active()
            ->withCount('agencyProfiles')
            ->orderBy('level')
            ->get();

        $totalAgencies = $tiers->sum('agency_profiles_count');

        return $tiers->map(fn ($tier) => [
            'tier_id' => $tier->id,
            'tier_name' => $tier->name,
            'tier_level' => $tier->level,
            'tier_slug' => $tier->slug,
            'badge_color' => $tier->badge_color,
            'agency_count' => $tier->agency_profiles_count,
            'percentage' => $totalAgencies > 0
                ? round(($tier->agency_profiles_count / $totalAgencies) * 100, 1)
                : 0,
        ])->toArray();
    }

    /**
     * Manually adjust an agency's tier (admin action).
     */
    public function manualTierAdjustment(User $agency, AgencyTier $newTier, int $adminId, string $reason): AgencyTierHistory
    {
        $profile = $agency->agencyProfile;
        $currentTier = $profile->tier;

        if ($currentTier && $newTier->level > $currentTier->level) {
            return $this->upgradeTier($agency, $newTier, $adminId);
        }

        return $this->downgradeTier($agency, $newTier, $adminId, $reason);
    }
}
