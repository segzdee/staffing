<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShiftAssignment;
use App\Notifications\WorkerSuspendedNotification;
use App\Notifications\WorkerReinstatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkerSuspensionService
{
    /**
     * Suspension durations in days based on violation count
     */
    const NO_SHOW_FIRST_OFFENSE_DAYS = 7;
    const NO_SHOW_SECOND_OFFENSE_DAYS = 30;
    const NO_SHOW_THIRD_OFFENSE_DAYS = 90;
    const LATE_CANCELLATION_PATTERN_DAYS = 14;

    /**
     * Thresholds for automatic suspension
     */
    const LATE_CANCELLATION_THRESHOLD = 2; // 2 late cancellations in 30 days
    const LATE_CANCELLATION_WINDOW_DAYS = 30;
    const NO_SHOW_LOOKBACK_DAYS = 90;

    /**
     * Check for no-show violations and suspend if necessary
     *
     * @param User $worker
     * @return array
     */
    public function checkNoShowViolations(User $worker): array
    {
        // Count no-shows in the last 90 days
        $noShowCount = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'no_show')
            ->where('created_at', '>=', Carbon::now()->subDays(self::NO_SHOW_LOOKBACK_DAYS))
            ->count();

        if ($noShowCount === 0) {
            return ['suspended' => false, 'reason' => null, 'days' => 0];
        }

        // Get the most recent no-show
        $latestNoShow = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'no_show')
            ->latest()
            ->first();

        // Check if already suspended for this violation
        if ($worker->isSuspended() && $worker->last_suspended_at >= $latestNoShow->updated_at) {
            return ['suspended' => false, 'reason' => 'Already suspended for this violation', 'days' => 0];
        }

        // Determine suspension duration based on count
        $suspensionDays = match (true) {
            $noShowCount >= 3 => self::NO_SHOW_THIRD_OFFENSE_DAYS,
            $noShowCount === 2 => self::NO_SHOW_SECOND_OFFENSE_DAYS,
            $noShowCount === 1 => self::NO_SHOW_FIRST_OFFENSE_DAYS,
            default => 0
        };

        if ($suspensionDays > 0) {
            $reason = "No-show violation (Offense #{$noShowCount} in 90 days)";
            $this->suspendWorker($worker, $suspensionDays, $reason, 'no_show');

            return [
                'suspended' => true,
                'reason' => $reason,
                'days' => $suspensionDays,
                'no_show_count' => $noShowCount
            ];
        }

        return ['suspended' => false, 'reason' => null, 'days' => 0];
    }

    /**
     * Check for late cancellation pattern violations
     *
     * @param User $worker
     * @return array
     */
    public function checkLateCancellationPattern(User $worker): array
    {
        $windowStart = Carbon::now()->subDays(self::LATE_CANCELLATION_WINDOW_DAYS);

        // Count late cancellations (cancelled within 24 hours of shift start)
        $lateCancellations = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'cancelled_by_worker')
            ->where('cancelled_at', '>=', $windowStart)
            ->whereNotNull('cancelled_at')
            ->get()
            ->filter(function ($assignment) {
                // Consider it a "late cancellation" if cancelled within 24 hours of shift start
                $shiftStart = Carbon::parse($assignment->shift->start_time);
                $cancelledAt = Carbon::parse($assignment->cancelled_at);
                return $shiftStart->diffInHours($cancelledAt) < 24;
            });

        $lateCancellationCount = $lateCancellations->count();

        if ($lateCancellationCount < self::LATE_CANCELLATION_THRESHOLD) {
            return ['suspended' => false, 'reason' => null, 'days' => 0];
        }

        // Check if already suspended for this pattern
        $latestCancellation = $lateCancellations->sortByDesc('cancelled_at')->first();

        if ($worker->isSuspended() && $worker->last_suspended_at >= $latestCancellation->cancelled_at) {
            return ['suspended' => false, 'reason' => 'Already suspended for this violation', 'days' => 0];
        }

        $reason = "Late cancellation pattern ({$lateCancellationCount} cancellations within 24h of shift start in {$windowStart->diffInDays(Carbon::now())} days)";
        $this->suspendWorker($worker, self::LATE_CANCELLATION_PATTERN_DAYS, $reason, 'late_cancellation_pattern');

        return [
            'suspended' => true,
            'reason' => $reason,
            'days' => self::LATE_CANCELLATION_PATTERN_DAYS,
            'cancellation_count' => $lateCancellationCount
        ];
    }

    /**
     * Suspend a worker
     *
     * @param User $worker
     * @param int $days
     * @param string $reason
     * @param string $type
     * @return void
     */
    public function suspendWorker(User $worker, int $days, string $reason, string $type = 'manual'): void
    {
        DB::transaction(function () use ($worker, $days, $reason, $type) {
            $suspendedUntil = Carbon::now()->addDays($days);

            $worker->update([
                'status' => 'suspended',
                'suspended_until' => $suspendedUntil,
                'suspension_reason' => $reason,
                'suspension_count' => DB::raw('suspension_count + 1'),
                'last_suspended_at' => Carbon::now()
            ]);

            // Cancel any pending shift applications
            $this->cancelPendingApplications($worker);

            // Log the suspension
            Log::info("Worker suspended", [
                'worker_id' => $worker->id,
                'worker_email' => $worker->email,
                'days' => $days,
                'reason' => $reason,
                'type' => $type,
                'suspended_until' => $suspendedUntil->toDateTimeString()
            ]);

            // Send notification to worker
            try {
                $worker->notify(new WorkerSuspendedNotification($suspendedUntil, $reason, $days));
            } catch (\Exception $e) {
                Log::error("Failed to send suspension notification", [
                    'worker_id' => $worker->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    /**
     * Reinstate a suspended worker
     *
     * @param User $worker
     * @param string|null $note
     * @return void
     */
    public function reinstateWorker(User $worker, ?string $note = null): void
    {
        DB::transaction(function () use ($worker, $note) {
            $worker->update([
                'status' => 'active',
                'suspended_until' => null,
                'suspension_reason' => null
            ]);

            // Log the reinstatement
            Log::info("Worker reinstated", [
                'worker_id' => $worker->id,
                'worker_email' => $worker->email,
                'note' => $note
            ]);

            // Send notification to worker
            try {
                $worker->notify(new WorkerReinstatedNotification($note));
            } catch (\Exception $e) {
                Log::error("Failed to send reinstatement notification", [
                    'worker_id' => $worker->id,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    /**
     * Automatically reinstate workers whose suspension period has ended
     *
     * @return int Number of workers reinstated
     */
    public function autoReinstateWorkers(): int
    {
        $expiredSuspensions = User::where('status', 'suspended')
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredSuspensions as $worker) {
            $this->reinstateWorker($worker, 'Automatic reinstatement after suspension period ended');
            $count++;
        }

        if ($count > 0) {
            Log::info("Auto-reinstated {$count} workers");
        }

        return $count;
    }

    /**
     * Cancel pending shift applications for a suspended worker
     *
     * @param User $worker
     * @return int Number of applications cancelled
     */
    protected function cancelPendingApplications(User $worker): int
    {
        $pendingApplications = $worker->shiftApplications()
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($pendingApplications as $application) {
            $application->update([
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'cancellation_reason' => 'Worker suspended due to: ' . $worker->suspension_reason
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Get suspension summary for a worker
     *
     * @param User $worker
     * @return array
     */
    public function getSuspensionSummary(User $worker): array
    {
        $noShowCount = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'no_show')
            ->where('created_at', '>=', Carbon::now()->subDays(self::NO_SHOW_LOOKBACK_DAYS))
            ->count();

        $windowStart = Carbon::now()->subDays(self::LATE_CANCELLATION_WINDOW_DAYS);
        $lateCancellations = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'cancelled_by_worker')
            ->where('cancelled_at', '>=', $windowStart)
            ->whereNotNull('cancelled_at')
            ->get()
            ->filter(function ($assignment) {
                $shiftStart = Carbon::parse($assignment->shift->start_time);
                $cancelledAt = Carbon::parse($assignment->cancelled_at);
                return $shiftStart->diffInHours($cancelledAt) < 24;
            })->count();

        return [
            'is_suspended' => $worker->isSuspended(),
            'suspended_until' => $worker->suspended_until,
            'suspension_reason' => $worker->suspension_reason,
            'suspension_count' => $worker->suspension_count,
            'last_suspended_at' => $worker->last_suspended_at,
            'recent_violations' => [
                'no_shows_90_days' => $noShowCount,
                'late_cancellations_30_days' => $lateCancellations
            ],
            'risk_level' => $this->calculateRiskLevel($noShowCount, $lateCancellations)
        ];
    }

    /**
     * Calculate risk level based on violation counts
     *
     * @param int $noShowCount
     * @param int $lateCancellationCount
     * @return string
     */
    protected function calculateRiskLevel(int $noShowCount, int $lateCancellationCount): string
    {
        if ($noShowCount >= 2 || $lateCancellationCount >= 2) {
            return 'high';
        }

        if ($noShowCount === 1 || $lateCancellationCount === 1) {
            return 'medium';
        }

        return 'low';
    }
}
