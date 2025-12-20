<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use App\Models\WorkerBadge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check and award badges based on trigger event
     *
     * @param  string  $trigger  - 'shift_completed', 'rating_received', 'checked_in', 'time_based'
     * @return array
     */
    public function checkAndAward(User $worker, string $trigger)
    {
        if (! $worker->isWorker()) {
            return [];
        }

        $awarded = [];

        // Map triggers to badge types to check
        $triggerMap = [
            'shift_completed' => ['first_shift', 'five_shifts', 'ten_shifts', 'fifty_shifts', 'hundred_shifts', 'perfect_week', 'reliable', 'top_earner'],
            'rating_received' => ['five_star', 'reliable'],
            'checked_in' => ['early_bird'],
            'time_based' => ['veteran'],
        ];

        $badgeTypes = $triggerMap[$trigger] ?? [];

        foreach ($badgeTypes as $badgeType) {
            $badge = $this->checkAndAwardBadge($worker, $badgeType);
            if ($badge) {
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    /**
     * Check and award all applicable badges for a worker
     */
    public function evaluateWorkerBadges(User $worker)
    {
        if (! $worker->isWorker()) {
            return;
        }

        $definitions = WorkerBadge::getBadgeDefinitions();
        $awarded = [];

        foreach ($definitions as $type => $definition) {
            $badge = $this->checkAndAwardBadge($worker, $type);
            if ($badge) {
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    /**
     * Check and award specific badge type
     */
    public function checkAndAwardBadge(User $worker, $badgeType)
    {
        $definitions = WorkerBadge::getBadgeDefinitions();

        if (! isset($definitions[$badgeType])) {
            return null;
        }

        $definition = $definitions[$badgeType];
        $stats = $this->getWorkerStats($worker);

        // Check each level (highest first)
        for ($level = 3; $level >= 1; $level--) {
            if (! isset($definition['levels'][$level])) {
                continue;
            }

            $criteria = $definition['levels'][$level];

            // Check if worker already has this badge at this level
            $existingBadge = WorkerBadge::where('worker_id', $worker->id)
                ->where('badge_type', $badgeType)
                ->where('level', '>=', $level)
                ->first();

            if ($existingBadge) {
                continue; // Already has this or higher level
            }

            // Check if criteria is met
            if ($this->meetsCriteria($stats, $criteria)) {
                return $this->awardBadge($worker, $badgeType, $level, $definition, $criteria);
            }
        }

        return null;
    }

    /**
     * Get worker statistics for badge evaluation
     */
    protected function getWorkerStats(User $worker)
    {
        $workerProfile = $worker->workerProfile;

        $completedShifts = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->get();

        $totalShifts = $completedShifts->count();

        // Morning shifts (6am - 12pm)
        $morningShifts = $completedShifts->filter(function ($assignment) {
            $startHour = Carbon::parse($assignment->shift->start_time)->hour;

            return $startHour >= 6 && $startHour < 12;
        })->count();

        // Night shifts (10pm - 6am)
        $nightShifts = $completedShifts->filter(function ($assignment) {
            $startHour = Carbon::parse($assignment->shift->start_time)->hour;

            return $startHour >= 22 || $startHour < 6;
        })->count();

        // Fast applications (within 1 hour of shift posting)
        $fastApplications = DB::table('shift_applications')
            ->join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
            ->where('shift_applications.worker_id', $worker->id)
            ->whereRaw('TIMESTAMPDIFF(MINUTE, shifts.created_at, shift_applications.applied_at) <= 60')
            ->count();

        // Verified skills
        $verifiedSkills = $worker->skills()->where('verified', true)->count();

        // Skills with max proficiency
        $maxProficiencySkills = DB::table('worker_skills')
            ->where('worker_id', $worker->id)
            ->where('proficiency_level', 'expert')
            ->count();

        // Months active
        $monthsActive = $worker->created_at->diffInMonths(Carbon::now());

        // Early check-ins (10+ mins early)
        $earlyCheckins = ShiftAssignment::where('worker_id', $worker->id)
            ->whereNotNull('check_in_time')
            ->get()
            ->filter(function ($assignment) {
                if (! $assignment->shift || ! $assignment->check_in_time) {
                    return false;
                }
                // Use start_datetime if available, otherwise combine date + time
                if ($assignment->shift->start_datetime) {
                    $shiftStart = Carbon::parse($assignment->shift->start_datetime);
                } else {
                    $shiftDate = $assignment->shift->shift_date instanceof Carbon
                        ? $assignment->shift->shift_date->format('Y-m-d')
                        : $assignment->shift->shift_date;
                    $startTime = $assignment->shift->start_time instanceof Carbon
                        ? $assignment->shift->start_time->format('H:i:s')
                        : $assignment->shift->start_time;
                    $shiftStart = Carbon::parse($shiftDate.' '.$startTime);
                }
                $checkIn = Carbon::parse($assignment->check_in_time);

                return $checkIn->diffInMinutes($shiftStart) >= 10;
            })
            ->count();

        // Perfect weeks (a week where worker completed all assigned shifts without issues)
        $perfectWeeks = $this->calculatePerfectWeeks($worker);

        // Monthly earnings (current month)
        $currentMonthEarnings = ShiftPayment::where('worker_id', $worker->id)
            ->where('status', 'paid_out')
            ->whereMonth('payout_completed_at', Carbon::now()->month)
            ->whereYear('payout_completed_at', Carbon::now()->year)
            ->sum('amount_net') ?? 0;

        return [
            'total_shifts' => $totalShifts,
            'reliability_score' => $workerProfile ? $workerProfile->reliability_score : 1.0,
            'rating' => $worker->rating_as_worker ?? 0,
            'morning_shifts' => $morningShifts,
            'night_shifts' => $nightShifts,
            'fast_applications' => $fastApplications,
            'verified_skills' => $verifiedSkills,
            'max_proficiency_skills' => $maxProficiencySkills,
            'months_active' => $monthsActive,
            'early_checkins' => $earlyCheckins,
            'perfect_weeks' => $perfectWeeks,
            'monthly_earnings' => $currentMonthEarnings,
        ];
    }

    /**
     * Check if stats meet criteria
     */
    protected function meetsCriteria($stats, $criteria)
    {
        foreach ($criteria as $key => $value) {
            $statKey = $this->mapCriteriaToStat($key);

            if (! isset($stats[$statKey])) {
                continue;
            }

            if ($stats[$statKey] < $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Map criteria keys to stat keys
     */
    protected function mapCriteriaToStat($criteriaKey)
    {
        $mapping = [
            'min_shifts' => 'total_shifts',
            'min_reliability' => 'reliability_score',
            'min_rating' => 'rating',
            'applications_within_1h' => 'fast_applications',
            'verified_skills' => 'verified_skills',
            'months_active' => 'months_active',
            'morning_shifts' => 'morning_shifts',
            'night_shifts' => 'night_shifts',
            'early_checkins' => 'early_checkins',
            'perfect_weeks' => 'perfect_weeks',
            'monthly_earnings' => 'monthly_earnings',
            'max_proficiency_skills' => 'max_proficiency_skills',
        ];

        return $mapping[$criteriaKey] ?? $criteriaKey;
    }

    /**
     * Award badge to worker
     */
    protected function awardBadge(User $worker, $badgeType, $level, $definition, $criteria)
    {
        $levelName = $definition['levels'][$level]['name'];

        $badge = WorkerBadge::create([
            'worker_id' => $worker->id,
            'badge_type' => $badgeType,
            'badge_name' => $levelName,
            'description' => $definition['description'],
            'criteria' => $criteria,
            'level' => $level,
            'earned_at' => now(),
            'is_active' => true,
            'display_on_profile' => true,
        ]);

        // Send notification
        $this->notificationService->send(
            $worker,
            'badge_earned',
            'New Badge Earned! 🏆',
            "Congratulations! You've earned the {$levelName} badge!",
            [
                'badge_id' => $badge->id,
                'badge_type' => $badgeType,
                'level' => $level,
            ]
        );

        return $badge;
    }

    /**
     * Revoke badge (if criteria no longer met)
     */
    public function revokeBadge(WorkerBadge $badge)
    {
        $badge->update(['is_active' => false]);
    }

    /**
     * Get displayable badges for worker profile
     */
    public function getDisplayableBadges(User $worker)
    {
        return WorkerBadge::where('worker_id', $worker->id)
            ->displayable()
            ->orderBy('level', 'desc')
            ->orderBy('earned_at', 'desc')
            ->get();
    }

    /**
     * Get badge progress for worker (how close to next badge)
     */
    public function getBadgeProgress(User $worker)
    {
        $definitions = WorkerBadge::getBadgeDefinitions();
        $stats = $this->getWorkerStats($worker);
        $progress = [];

        foreach ($definitions as $type => $definition) {
            // Find highest earned level for this badge type
            $earnedLevel = WorkerBadge::where('worker_id', $worker->id)
                ->where('badge_type', $type)
                ->max('level') ?? 0;

            // Get next level
            $nextLevel = $earnedLevel + 1;

            if (! isset($definition['levels'][$nextLevel])) {
                continue; // Max level already achieved
            }

            $criteria = $definition['levels'][$nextLevel];
            $criteriaProgress = [];

            foreach ($criteria as $key => $value) {
                if ($key === 'name') {
                    continue;
                }

                $statKey = $this->mapCriteriaToStat($key);
                $current = $stats[$statKey] ?? 0;
                $percentage = min(100, ($current / $value) * 100);

                $criteriaProgress[$key] = [
                    'current' => $current,
                    'required' => $value,
                    'percentage' => round($percentage, 1),
                ];
            }

            $progress[$type] = [
                'badge_name' => $definition['levels'][$nextLevel]['name'],
                'current_level' => $earnedLevel,
                'next_level' => $nextLevel,
                'criteria' => $criteriaProgress,
            ];
        }

        return $progress;
    }

    /**
     * Calculate the number of perfect weeks for a worker.
     *
     * A perfect week is defined as a week where:
     * - Worker had at least 1 shift assigned
     * - All assigned shifts were completed (no no-shows, no cancellations)
     * - No late arrivals (was_late = false)
     * - Average rating for the week was 4.0 or higher (if ratings exist)
     *
     * @return int Number of perfect weeks
     */
    protected function calculatePerfectWeeks(User $worker): int
    {
        $perfectWeeks = 0;

        // Get all completed assignments grouped by week
        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->whereIn('status', ['completed', 'no_show', 'cancelled'])
            ->with(['shift', 'ratings'])
            ->get();

        if ($assignments->isEmpty()) {
            return 0;
        }

        // Group assignments by ISO week (year-week format)
        $assignmentsByWeek = $assignments->groupBy(function ($assignment) {
            return Carbon::parse($assignment->shift->shift_date)->format('o-W');
        });

        foreach ($assignmentsByWeek as $weekKey => $weekAssignments) {
            // Skip if no assignments in this week
            if ($weekAssignments->isEmpty()) {
                continue;
            }

            // Check if all shifts were completed (no no-shows or cancellations)
            $allCompleted = $weekAssignments->every(function ($assignment) {
                return $assignment->status === 'completed';
            });

            if (! $allCompleted) {
                continue;
            }

            // Check for any late arrivals
            $anyLate = $weekAssignments->contains(function ($assignment) {
                return $assignment->was_late || $assignment->late_minutes > 0;
            });

            if ($anyLate) {
                continue;
            }

            // Check ratings for the week (if any exist)
            $weekRatings = $weekAssignments->flatMap(function ($assignment) {
                return $assignment->ratings->where('rater_type', 'business');
            });

            if ($weekRatings->isNotEmpty()) {
                $averageRating = $weekRatings->avg('rating');
                if ($averageRating < 4.0) {
                    continue;
                }
            }

            // This week is a perfect week
            $perfectWeeks++;
        }

        return $perfectWeeks;
    }
}
