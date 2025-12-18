<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerTier;
use App\Models\WorkerTierHistory;
use App\Notifications\WorkerTierDowngradeWarningNotification;
use App\Notifications\WorkerTierUpgradeNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WKR-007: Worker Career Tiers Service
 *
 * Manages worker career progression tiers, including tier calculations,
 * upgrades, downgrades, and monthly reviews.
 */
class WorkerTierService
{
    /**
     * Calculate all metrics for a worker.
     *
     * @return array{shifts_completed: int, rating: float, hours_worked: float, months_active: int}
     */
    public function calculateWorkerMetrics(User $worker): array
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            return [
                'shifts_completed' => 0,
                'rating' => 0.0,
                'hours_worked' => 0.0,
                'months_active' => 0,
            ];
        }

        // Get total completed shifts
        $shiftsCompleted = $worker->shiftAssignments()
            ->where('status', 'completed')
            ->count();

        // Get average rating
        $rating = $worker->ratingsReceived()
            ->where('rater_type', 'business')
            ->avg('rating') ?? 0.0;

        // Calculate total hours worked from completed assignments
        $hoursWorked = $worker->shiftAssignments()
            ->where('status', 'completed')
            ->sum('hours_worked') ?? 0.0;

        // Use lifetime hours if stored and greater
        if ($profile->lifetime_hours > $hoursWorked) {
            $hoursWorked = $profile->lifetime_hours;
        }

        // Calculate months active since first shift
        $firstShift = $worker->shiftAssignments()
            ->orderBy('created_at', 'asc')
            ->first();

        $monthsActive = 0;
        if ($firstShift) {
            $monthsActive = Carbon::parse($firstShift->created_at)->diffInMonths(now());
        }

        return [
            'shifts_completed' => $shiftsCompleted,
            'rating' => round((float) $rating, 2),
            'hours_worked' => round((float) $hoursWorked, 2),
            'months_active' => $monthsActive,
        ];
    }

    /**
     * Determine the highest tier a worker is eligible for.
     */
    public function determineEligibleTier(User $worker): ?WorkerTier
    {
        $metrics = $this->calculateWorkerMetrics($worker);

        // Get all active tiers ordered by level descending (highest first)
        $tiers = WorkerTier::active()
            ->orderBy('level', 'desc')
            ->get();

        foreach ($tiers as $tier) {
            if ($tier->meetsRequirements($metrics)) {
                return $tier;
            }
        }

        // Return default tier if no tier requirements met
        return WorkerTier::getDefaultTier();
    }

    /**
     * Upgrade a worker to a new tier.
     */
    public function upgradeTier(User $worker, WorkerTier $newTier, ?string $notes = null): bool
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            Log::warning('WorkerTierService: Cannot upgrade tier - worker has no profile', [
                'user_id' => $worker->id,
            ]);

            return false;
        }

        $currentTier = $profile->workerTier;

        // Verify this is actually an upgrade
        if ($currentTier && $newTier->level <= $currentTier->level) {
            Log::warning('WorkerTierService: Attempted upgrade to same or lower tier', [
                'user_id' => $worker->id,
                'current_tier' => $currentTier->slug,
                'attempted_tier' => $newTier->slug,
            ]);

            return false;
        }

        $metrics = $this->calculateWorkerMetrics($worker);

        DB::transaction(function () use ($profile, $worker, $currentTier, $newTier, $metrics, $notes) {
            // Update worker profile
            $profile->update([
                'worker_tier_id' => $newTier->id,
                'tier_achieved_at' => now(),
                'tier_progress' => $this->getTierProgress($worker),
            ]);

            // Record history
            if ($currentTier) {
                WorkerTierHistory::recordUpgrade($worker, $currentTier, $newTier, $metrics, $notes);
            } else {
                WorkerTierHistory::recordInitial($worker, $newTier, $metrics, $notes);
            }
        });

        // Send notification
        try {
            $worker->notify(new WorkerTierUpgradeNotification($newTier, $currentTier, $metrics));
        } catch (\Exception $e) {
            Log::error('WorkerTierService: Failed to send upgrade notification', [
                'user_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('WorkerTierService: Worker tier upgraded', [
            'user_id' => $worker->id,
            'from_tier' => $currentTier?->slug ?? 'none',
            'to_tier' => $newTier->slug,
        ]);

        return true;
    }

    /**
     * Downgrade a worker to a lower tier.
     */
    public function downgradeTier(User $worker, WorkerTier $newTier, ?string $notes = null): bool
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            return false;
        }

        $currentTier = $profile->workerTier;

        // Verify this is actually a downgrade
        if (! $currentTier || $newTier->level >= $currentTier->level) {
            return false;
        }

        $metrics = $this->calculateWorkerMetrics($worker);

        DB::transaction(function () use ($profile, $worker, $currentTier, $newTier, $metrics, $notes) {
            // Update worker profile
            $profile->update([
                'worker_tier_id' => $newTier->id,
                'tier_achieved_at' => now(),
                'tier_progress' => $this->getTierProgress($worker),
            ]);

            // Record history
            WorkerTierHistory::recordDowngrade($worker, $currentTier, $newTier, $metrics, $notes);
        });

        // Send notification
        try {
            $worker->notify(new WorkerTierDowngradeWarningNotification($newTier, $currentTier, $metrics));
        } catch (\Exception $e) {
            Log::error('WorkerTierService: Failed to send downgrade notification', [
                'user_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('WorkerTierService: Worker tier downgraded', [
            'user_id' => $worker->id,
            'from_tier' => $currentTier->slug,
            'to_tier' => $newTier->slug,
        ]);

        return true;
    }

    /**
     * Get tier progress information for a worker.
     */
    public function getTierProgress(User $worker): array
    {
        $profile = $worker->workerProfile;
        $metrics = $this->calculateWorkerMetrics($worker);

        $currentTier = $profile?->workerTier ?? WorkerTier::getDefaultTier();
        $nextTier = $currentTier?->getNextTier();

        $progress = [
            'current_tier' => $currentTier ? [
                'id' => $currentTier->id,
                'name' => $currentTier->name,
                'slug' => $currentTier->slug,
                'level' => $currentTier->level,
                'badge_color' => $currentTier->badge_color,
                'badge_icon' => $currentTier->badge_icon,
                'benefits' => $currentTier->getAllBenefits(),
            ] : null,
            'metrics' => $metrics,
            'next_tier' => null,
            'overall_progress' => 100, // Default to 100% if at max tier
        ];

        if ($nextTier) {
            $tierProgress = $nextTier->getProgressTowards($metrics);

            // Calculate overall progress as the average of all requirements
            $totalPercent = 0;
            $requirementCount = 0;

            foreach ($tierProgress as $requirement => $data) {
                if ($data['required'] > 0) {
                    $totalPercent += $data['percent'];
                    $requirementCount++;
                }
            }

            $overallProgress = $requirementCount > 0 ? round($totalPercent / $requirementCount) : 0;

            $progress['next_tier'] = [
                'id' => $nextTier->id,
                'name' => $nextTier->name,
                'slug' => $nextTier->slug,
                'level' => $nextTier->level,
                'badge_color' => $nextTier->badge_color,
                'requirements' => [
                    'shifts' => $nextTier->min_shifts_completed,
                    'rating' => $nextTier->min_rating,
                    'hours' => $nextTier->min_hours_worked,
                    'months' => $nextTier->min_months_active,
                ],
                'progress' => $tierProgress,
                'benefits' => $nextTier->getAllBenefits(),
            ];
            $progress['overall_progress'] = $overallProgress;
        }

        return $progress;
    }

    /**
     * Process a completed shift for tier tracking.
     */
    public function processShiftCompletion(User $worker, Shift $shift): void
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            return;
        }

        // Get the shift assignment to get actual hours worked
        $assignment = $worker->shiftAssignments()
            ->where('shift_id', $shift->id)
            ->where('status', 'completed')
            ->first();

        if (! $assignment) {
            return;
        }

        // Update lifetime counters
        $profile->increment('lifetime_shifts');
        $profile->increment('lifetime_hours', $assignment->hours_worked ?? 0);

        // Update tier progress
        $profile->update([
            'tier_progress' => $this->getTierProgress($worker),
        ]);

        // Check for tier upgrade eligibility
        $this->checkAndApplyTierChange($worker);
    }

    /**
     * Check if worker qualifies for tier change and apply if needed.
     */
    public function checkAndApplyTierChange(User $worker): ?WorkerTier
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            return null;
        }

        $currentTier = $profile->workerTier;
        $eligibleTier = $this->determineEligibleTier($worker);

        if (! $eligibleTier) {
            return $currentTier;
        }

        // No current tier - assign initial tier
        if (! $currentTier) {
            $this->upgradeTier($worker, $eligibleTier, 'Initial tier assignment');

            return $eligibleTier;
        }

        // Check for upgrade
        if ($eligibleTier->level > $currentTier->level) {
            $this->upgradeTier($worker, $eligibleTier);

            return $eligibleTier;
        }

        // Check for downgrade (only during monthly review, not on shift completion)
        // Downgrades are handled separately in processMonthlyTierReview

        return $currentTier;
    }

    /**
     * Process monthly tier review for all workers.
     *
     * @return array{processed: int, upgraded: int, downgraded: int, unchanged: int}
     */
    public function processMonthlyTierReview(): array
    {
        $stats = [
            'processed' => 0,
            'upgraded' => 0,
            'downgraded' => 0,
            'unchanged' => 0,
        ];

        // Get all worker profiles with their users
        WorkerProfile::with(['user', 'workerTier'])
            ->whereNotNull('user_id')
            ->chunk(100, function ($profiles) use (&$stats) {
                foreach ($profiles as $profile) {
                    $worker = $profile->user;

                    if (! $worker || $worker->user_type !== 'worker') {
                        continue;
                    }

                    $stats['processed']++;

                    $currentTier = $profile->workerTier;
                    $eligibleTier = $this->determineEligibleTier($worker);

                    if (! $eligibleTier) {
                        $stats['unchanged']++;

                        continue;
                    }

                    // No current tier - assign initial
                    if (! $currentTier) {
                        $this->upgradeTier($worker, $eligibleTier, 'Monthly review - initial assignment');
                        $stats['upgraded']++;

                        continue;
                    }

                    // Upgrade
                    if ($eligibleTier->level > $currentTier->level) {
                        $this->upgradeTier($worker, $eligibleTier, 'Monthly review upgrade');
                        $stats['upgraded']++;

                        continue;
                    }

                    // Downgrade
                    if ($eligibleTier->level < $currentTier->level) {
                        $this->downgradeTier($worker, $eligibleTier, 'Monthly review - no longer meets requirements');
                        $stats['downgraded']++;

                        continue;
                    }

                    // Update progress even if tier unchanged
                    $profile->update([
                        'tier_progress' => $this->getTierProgress($worker),
                    ]);

                    $stats['unchanged']++;
                }
            });

        Log::info('WorkerTierService: Monthly tier review completed', $stats);

        return $stats;
    }

    /**
     * Get the leaderboard of top workers by tier and metrics.
     *
     * @return Collection<int, array>
     */
    public function getLeaderboard(int $limit = 100): Collection
    {
        return WorkerProfile::with(['user', 'workerTier'])
            ->whereNotNull('worker_tier_id')
            ->orderByDesc(function ($query) {
                $query->select('level')
                    ->from('worker_tiers')
                    ->whereColumn('worker_tiers.id', 'worker_profiles.worker_tier_id')
                    ->limit(1);
            })
            ->orderByDesc('lifetime_shifts')
            ->orderByDesc('rating_average')
            ->limit($limit)
            ->get()
            ->map(function ($profile, $index) {
                return [
                    'rank' => $index + 1,
                    'user_id' => $profile->user_id,
                    'name' => $profile->user->name ?? 'Unknown',
                    'tier' => $profile->workerTier ? [
                        'name' => $profile->workerTier->name,
                        'level' => $profile->workerTier->level,
                        'badge_color' => $profile->workerTier->badge_color,
                    ] : null,
                    'lifetime_shifts' => $profile->lifetime_shifts,
                    'lifetime_hours' => $profile->lifetime_hours,
                    'rating' => $profile->rating_average,
                    'reliability_score' => $profile->reliability_score,
                ];
            });
    }

    /**
     * Get all benefits for a specific tier.
     */
    public function getTierBenefits(WorkerTier $tier): array
    {
        return [
            'tier' => [
                'id' => $tier->id,
                'name' => $tier->name,
                'slug' => $tier->slug,
                'level' => $tier->level,
                'badge_color' => $tier->badge_color,
                'badge_icon' => $tier->badge_icon,
            ],
            'benefits' => [
                'fee_discount_percent' => $tier->fee_discount_percent,
                'priority_booking_hours' => $tier->priority_booking_hours,
                'instant_payout' => $tier->instant_payout,
                'premium_shifts_access' => $tier->premium_shifts_access,
            ],
            'benefits_list' => $tier->getAllBenefits(),
            'additional_benefits' => $tier->additional_benefits ?? [],
            'requirements' => [
                'min_shifts_completed' => $tier->min_shifts_completed,
                'min_rating' => $tier->min_rating,
                'min_hours_worked' => $tier->min_hours_worked,
                'min_months_active' => $tier->min_months_active,
            ],
        ];
    }

    /**
     * Get all tiers with worker counts and benefits.
     */
    public function getAllTiersWithStats(): Collection
    {
        return WorkerTier::active()
            ->orderBy('level')
            ->withCount('workerProfiles')
            ->get()
            ->map(function ($tier) {
                return array_merge(
                    $this->getTierBenefits($tier),
                    ['worker_count' => $tier->worker_profiles_count]
                );
            });
    }

    /**
     * Initialize tier for a new worker.
     */
    public function initializeWorkerTier(User $worker): ?WorkerTier
    {
        $profile = $worker->workerProfile;

        if (! $profile || $profile->worker_tier_id) {
            return $profile?->workerTier;
        }

        $defaultTier = WorkerTier::getDefaultTier();

        if (! $defaultTier) {
            Log::warning('WorkerTierService: No default tier available', [
                'user_id' => $worker->id,
            ]);

            return null;
        }

        $metrics = $this->calculateWorkerMetrics($worker);

        DB::transaction(function () use ($profile, $worker, $defaultTier, $metrics) {
            $profile->update([
                'worker_tier_id' => $defaultTier->id,
                'tier_achieved_at' => now(),
                'tier_progress' => $this->getTierProgress($worker),
            ]);

            WorkerTierHistory::recordInitial($worker, $defaultTier, $metrics, 'Account initialization');
        });

        Log::info('WorkerTierService: Worker initialized with default tier', [
            'user_id' => $worker->id,
            'tier' => $defaultTier->slug,
        ]);

        return $defaultTier;
    }

    /**
     * Get tier history for a worker.
     */
    public function getWorkerTierHistory(User $worker, int $limit = 10): Collection
    {
        return WorkerTierHistory::with(['fromTier', 'toTier'])
            ->where('user_id', $worker->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($history) {
                return [
                    'id' => $history->id,
                    'from_tier' => $history->fromTier ? [
                        'name' => $history->fromTier->name,
                        'level' => $history->fromTier->level,
                    ] : null,
                    'to_tier' => [
                        'name' => $history->toTier->name,
                        'level' => $history->toTier->level,
                        'badge_color' => $history->toTier->badge_color,
                    ],
                    'change_type' => $history->change_type,
                    'description' => $history->getChangeDescription(),
                    'metrics_at_change' => $history->metrics_at_change,
                    'date' => $history->created_at->toISOString(),
                    'date_formatted' => $history->created_at->format('M d, Y'),
                ];
            });
    }

    /**
     * Calculate fee with tier discount applied.
     */
    public function calculateFeeWithDiscount(User $worker, float $baseFee): array
    {
        $profile = $worker->workerProfile;
        $tier = $profile?->workerTier;

        if (! $tier || $tier->fee_discount_percent <= 0) {
            return [
                'base_fee' => $baseFee,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'final_fee' => $baseFee,
                'tier_applied' => null,
            ];
        }

        $discountAmount = round($baseFee * ($tier->fee_discount_percent / 100), 2);
        $finalFee = round($baseFee - $discountAmount, 2);

        return [
            'base_fee' => $baseFee,
            'discount_percent' => $tier->fee_discount_percent,
            'discount_amount' => $discountAmount,
            'final_fee' => max(0, $finalFee),
            'tier_applied' => $tier->name,
        ];
    }
}
