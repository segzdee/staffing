<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShiftAssignment;
use App\Models\ReliabilityScoreHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReliabilityScoreService
{
    /**
     * Score component weights (must total 100%)
     */
    const WEIGHT_ATTENDANCE = 0.40;        // 40% - No-shows and completions
    const WEIGHT_CANCELLATION = 0.25;      // 25% - Cancellation timing
    const WEIGHT_PUNCTUALITY = 0.20;       // 20% - Clock-in timing
    const WEIGHT_RESPONSIVENESS = 0.15;    // 15% - Confirmation speed

    /**
     * Scoring period in days
     */
    const SCORING_PERIOD_DAYS = 90;

    /**
     * Minimum shifts required for a reliable score
     */
    const MINIMUM_SHIFTS_FOR_SCORE = 5;

    /**
     * Calculate reliability score for a worker
     *
     * @param User $worker
     * @param Carbon|null $periodEnd
     * @return array
     */
    public function calculateScore(User $worker, ?Carbon $periodEnd = null): array
    {
        $periodEnd = $periodEnd ?? Carbon::now();
        $periodStart = $periodEnd->copy()->subDays(self::SCORING_PERIOD_DAYS);

        // Get all shift assignments in the period
        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->with('shift')
            ->get();

        // If insufficient data, return default score
        if ($assignments->count() < self::MINIMUM_SHIFTS_FOR_SCORE) {
            return $this->getDefaultScore($worker, $periodStart, $periodEnd, $assignments->count());
        }

        // Calculate each component
        $attendanceScore = $this->calculateAttendanceScore($assignments);
        $cancellationScore = $this->calculateCancellationScore($assignments);
        $punctualityScore = $this->calculatePunctualityScore($assignments);
        $responsivenessScore = $this->calculateResponsivenessScore($assignments);

        // Calculate weighted total score
        $totalScore = round(
            ($attendanceScore * self::WEIGHT_ATTENDANCE) +
            ($cancellationScore * self::WEIGHT_CANCELLATION) +
            ($punctualityScore * self::WEIGHT_PUNCTUALITY) +
            ($responsivenessScore * self::WEIGHT_RESPONSIVENESS),
            2
        );

        // Compile metrics used
        $metrics = [
            'total_assignments' => $assignments->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'no_shows' => $assignments->where('status', 'no_show')->count(),
            'cancellations' => $assignments->whereIn('status', ['cancelled_by_worker', 'cancelled_by_business'])->count(),
            'late_cancellations' => $this->countLateCancellations($assignments),
            'early_clock_ins' => $this->countEarlyClockIns($assignments),
            'late_clock_ins' => $this->countLateClockIns($assignments),
            'avg_response_time_hours' => $this->calculateAverageResponseTime($assignments),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString()
        ];

        return [
            'score' => $totalScore,
            'attendance_score' => $attendanceScore,
            'cancellation_score' => $cancellationScore,
            'punctuality_score' => $punctualityScore,
            'responsiveness_score' => $responsivenessScore,
            'metrics' => $metrics,
            'grade' => $this->getScoreGrade($totalScore),
            'period_start' => $periodStart,
            'period_end' => $periodEnd
        ];
    }

    /**
     * Calculate attendance score (40% weight)
     * Based on completion rate and no-show rate
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return float
     */
    protected function calculateAttendanceScore($assignments): float
    {
        $total = $assignments->count();
        if ($total === 0) return 100.0;

        $completed = $assignments->where('status', 'completed')->count();
        $noShows = $assignments->where('status', 'no_show')->count();

        // Perfect score for 100% completion, 0 no-shows
        // Each no-show deducts 20 points
        // Low completion rate reduces score
        $completionRate = ($completed / $total) * 100;
        $noShowPenalty = min($noShows * 20, 80); // Max 80 point penalty

        $score = $completionRate - $noShowPenalty;

        return max(0, min(100, $score));
    }

    /**
     * Calculate cancellation score (25% weight)
     * Based on cancellation timing and frequency
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return float
     */
    protected function calculateCancellationScore($assignments): float
    {
        $total = $assignments->count();
        if ($total === 0) return 100.0;

        $cancellations = $assignments->filter(function ($assignment) {
            return in_array($assignment->status, ['cancelled_by_worker', 'cancelled_by_business']);
        });

        if ($cancellations->isEmpty()) {
            return 100.0;
        }

        $cancellationRate = ($cancellations->count() / $total) * 100;

        // Analyze cancellation timing
        $lateCancellations = 0;
        $moderateCancellations = 0;
        $earlyCancellations = 0;

        foreach ($cancellations as $assignment) {
            if (!$assignment->cancelled_at || !$assignment->shift) continue;

            $hoursBeforeShift = Carbon::parse($assignment->cancelled_at)
                ->diffInHours(Carbon::parse($assignment->shift->start_time), false);

            if ($hoursBeforeShift < 24) {
                $lateCancellations++;
            } elseif ($hoursBeforeShift < 48) {
                $moderateCancellations++;
            } else {
                $earlyCancellations++;
            }
        }

        // Scoring: Late cancellations are heavily penalized
        $score = 100;
        $score -= ($lateCancellations * 25);      // 25 points per late cancel
        $score -= ($moderateCancellations * 10);  // 10 points per moderate cancel
        $score -= ($earlyCancellations * 5);      // 5 points per early cancel
        $score -= ($cancellationRate * 0.5);      // Additional penalty for high cancellation rate

        return max(0, min(100, $score));
    }

    /**
     * Calculate punctuality score (20% weight)
     * Based on clock-in timing
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return float
     */
    protected function calculatePunctualityScore($assignments): float
    {
        $assignmentsWithClockIn = $assignments->filter(function ($assignment) {
            return $assignment->clock_in_time !== null && $assignment->shift;
        });

        if ($assignmentsWithClockIn->isEmpty()) {
            return 100.0; // Neutral score if no clock-in data
        }

        $earlyCount = 0;
        $onTimeCount = 0;
        $lateCount = 0;

        foreach ($assignmentsWithClockIn as $assignment) {
            $clockInTime = Carbon::parse($assignment->clock_in_time);
            $shiftStart = Carbon::parse($assignment->shift->start_time);
            $minutesDifference = $clockInTime->diffInMinutes($shiftStart, false);

            if ($minutesDifference <= -5) {
                $earlyCount++; // Clocked in 5+ minutes early
            } elseif ($minutesDifference >= 15) {
                $lateCount++; // Clocked in 15+ minutes late
            } else {
                $onTimeCount++; // Within acceptable range
            }
        }

        $total = $assignmentsWithClockIn->count();
        $onTimeRate = (($earlyCount + $onTimeCount) / $total) * 100;
        $lateRate = ($lateCount / $total) * 100;

        // Score based on on-time percentage, with penalty for lateness
        $score = $onTimeRate - ($lateRate * 2); // Double penalty for late clock-ins

        return max(0, min(100, $score));
    }

    /**
     * Calculate responsiveness score (15% weight)
     * Based on how quickly worker confirms shift assignments
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return float
     */
    protected function calculateResponsivenessScore($assignments): float
    {
        $assignmentsWithConfirmation = $assignments->filter(function ($assignment) {
            return $assignment->confirmed_at !== null;
        });

        if ($assignmentsWithConfirmation->isEmpty()) {
            return 50.0; // Neutral-low score if no confirmation data
        }

        $totalResponseTime = 0;
        $count = 0;

        foreach ($assignmentsWithConfirmation as $assignment) {
            $assignedAt = Carbon::parse($assignment->created_at);
            $confirmedAt = Carbon::parse($assignment->confirmed_at);
            $hoursToConfirm = $assignedAt->diffInHours($confirmedAt);

            $totalResponseTime += $hoursToConfirm;
            $count++;
        }

        $avgResponseTimeHours = $count > 0 ? $totalResponseTime / $count : 0;

        // Perfect score for responding within 2 hours
        // Score decreases as response time increases
        if ($avgResponseTimeHours <= 2) {
            $score = 100;
        } elseif ($avgResponseTimeHours <= 6) {
            $score = 90;
        } elseif ($avgResponseTimeHours <= 12) {
            $score = 75;
        } elseif ($avgResponseTimeHours <= 24) {
            $score = 60;
        } elseif ($avgResponseTimeHours <= 48) {
            $score = 40;
        } else {
            $score = 20;
        }

        return $score;
    }

    /**
     * Save reliability score to history
     *
     * @param User $worker
     * @param array $scoreData
     * @return ReliabilityScoreHistory
     */
    public function saveScoreHistory(User $worker, array $scoreData): ReliabilityScoreHistory
    {
        return ReliabilityScoreHistory::create([
            'user_id' => $worker->id,
            'score' => $scoreData['score'],
            'attendance_score' => $scoreData['attendance_score'],
            'cancellation_score' => $scoreData['cancellation_score'],
            'punctuality_score' => $scoreData['punctuality_score'],
            'responsiveness_score' => $scoreData['responsiveness_score'],
            'metrics' => json_encode($scoreData['metrics']),
            'period_start' => $scoreData['period_start'],
            'period_end' => $scoreData['period_end']
        ]);
    }

    /**
     * Recalculate and save score for a worker
     *
     * @param User $worker
     * @return array
     */
    public function recalculateAndSave(User $worker): array
    {
        try {
            $scoreData = $this->calculateScore($worker);
            $this->saveScoreHistory($worker, $scoreData);

            Log::info("Reliability score calculated", [
                'worker_id' => $worker->id,
                'score' => $scoreData['score'],
                'grade' => $scoreData['grade']
            ]);

            return $scoreData;
        } catch (\Exception $e) {
            Log::error("Failed to calculate reliability score", [
                'worker_id' => $worker->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get default score for workers with insufficient data
     *
     * @param User $worker
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param int $assignmentCount
     * @return array
     */
    protected function getDefaultScore(User $worker, Carbon $periodStart, Carbon $periodEnd, int $assignmentCount): array
    {
        // New workers start with a neutral score
        $defaultScore = 70.0;

        return [
            'score' => $defaultScore,
            'attendance_score' => $defaultScore,
            'cancellation_score' => $defaultScore,
            'punctuality_score' => $defaultScore,
            'responsiveness_score' => $defaultScore,
            'metrics' => [
                'total_assignments' => $assignmentCount,
                'insufficient_data' => true,
                'minimum_required' => self::MINIMUM_SHIFTS_FOR_SCORE
            ],
            'grade' => $this->getScoreGrade($defaultScore),
            'period_start' => $periodStart,
            'period_end' => $periodEnd
        ];
    }

    /**
     * Get letter grade for score
     *
     * @param float $score
     * @return string
     */
    protected function getScoreGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F'
        };
    }

    /**
     * Helper: Count late cancellations
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return int
     */
    protected function countLateCancellations($assignments): int
    {
        return $assignments->filter(function ($assignment) {
            if (!in_array($assignment->status, ['cancelled_by_worker', 'cancelled_by_business'])) {
                return false;
            }
            if (!$assignment->cancelled_at || !$assignment->shift) {
                return false;
            }

            $hoursBeforeShift = Carbon::parse($assignment->cancelled_at)
                ->diffInHours(Carbon::parse($assignment->shift->start_time), false);

            return $hoursBeforeShift < 24;
        })->count();
    }

    /**
     * Helper: Count early clock-ins
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return int
     */
    protected function countEarlyClockIns($assignments): int
    {
        return $assignments->filter(function ($assignment) {
            if (!$assignment->clock_in_time || !$assignment->shift) {
                return false;
            }

            $clockInTime = Carbon::parse($assignment->clock_in_time);
            $shiftStart = Carbon::parse($assignment->shift->start_time);
            $minutesDifference = $clockInTime->diffInMinutes($shiftStart, false);

            return $minutesDifference <= -5;
        })->count();
    }

    /**
     * Helper: Count late clock-ins
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return int
     */
    protected function countLateClockIns($assignments): int
    {
        return $assignments->filter(function ($assignment) {
            if (!$assignment->clock_in_time || !$assignment->shift) {
                return false;
            }

            $clockInTime = Carbon::parse($assignment->clock_in_time);
            $shiftStart = Carbon::parse($assignment->shift->start_time);
            $minutesDifference = $clockInTime->diffInMinutes($shiftStart, false);

            return $minutesDifference >= 15;
        })->count();
    }

    /**
     * Helper: Calculate average response time
     *
     * @param \Illuminate\Support\Collection $assignments
     * @return float
     */
    protected function calculateAverageResponseTime($assignments): float
    {
        $assignmentsWithConfirmation = $assignments->filter(function ($assignment) {
            return $assignment->confirmed_at !== null;
        });

        if ($assignmentsWithConfirmation->isEmpty()) {
            return 0;
        }

        $totalResponseTime = 0;
        foreach ($assignmentsWithConfirmation as $assignment) {
            $assignedAt = Carbon::parse($assignment->created_at);
            $confirmedAt = Carbon::parse($assignment->confirmed_at);
            $totalResponseTime += $assignedAt->diffInHours($confirmedAt);
        }

        return round($totalResponseTime / $assignmentsWithConfirmation->count(), 2);
    }

    /**
     * Get score history for a worker
     *
     * @param User $worker
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScoreHistory(User $worker, int $limit = 12)
    {
        return ReliabilityScoreHistory::where('user_id', $worker->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get matching priority multiplier based on reliability score
     * Higher scores get priority in matching algorithm
     *
     * @param float $score
     * @return float Multiplier between 0.5 and 1.5
     */
    public function getMatchingPriorityMultiplier(float $score): float
    {
        return match (true) {
            $score >= 90 => 1.5,   // A grade: 50% boost
            $score >= 80 => 1.25,  // B grade: 25% boost
            $score >= 70 => 1.0,   // C grade: neutral
            $score >= 60 => 0.85,  // D grade: 15% penalty
            default => 0.5         // F grade: 50% penalty
        };
    }
}
