<?php

namespace App\Jobs;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Notifications\BreakReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * SL-006: Break Enforcement Job
 *
 * Monitors active shifts and sends reminders to workers who need to take breaks.
 * Runs every 5 minutes during business hours to ensure compliance.
 *
 * Scheduled to run: */5 * * * * (every 5 minutes)
 */
class EnforceBreakCompliance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Starting break compliance enforcement check');

        $startTime = now();
        $stats = [
            'shifts_checked' => 0,
            'assignments_checked' => 0,
            'reminders_sent' => 0,
            'compliance_flags' => 0,
        ];

        try {
            // Get all active shifts that are currently in progress
            $activeShifts = $this->getActiveShifts();
            $stats['shifts_checked'] = $activeShifts->count();

            foreach ($activeShifts as $shift) {
                try {
                    $this->enforceShiftBreakCompliance($shift, $stats);
                } catch (\Exception $e) {
                    Log::error('Failed to enforce break compliance for shift', [
                        'shift_id' => $shift->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue processing other shifts
                }
            }

            $duration = now()->diffInSeconds($startTime);

            Log::info('Break compliance enforcement completed', [
                'duration_seconds' => $duration,
                'shifts_checked' => $stats['shifts_checked'],
                'assignments_checked' => $stats['assignments_checked'],
                'reminders_sent' => $stats['reminders_sent'],
                'compliance_flags' => $stats['compliance_flags'],
            ]);

        } catch (\Exception $e) {
            Log::error('Break compliance enforcement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Get all active shifts that need break monitoring.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getActiveShifts()
    {
        $now = Carbon::now();

        return Shift::with(['assignments.worker', 'business'])
            ->where('status', 'in_progress')
            ->where('shift_date', '=', $now->toDateString())
            ->where('duration_hours', '>=', 6) // Only shifts that require breaks
            ->whereHas('assignments', function ($query) {
                $query->where('status', 'checked_in');
            })
            ->get();
    }

    /**
     * Enforce break compliance for a specific shift.
     *
     * @param Shift $shift
     * @param array &$stats
     * @return void
     */
    protected function enforceShiftBreakCompliance(Shift $shift, array &$stats)
    {
        // Check if shift requires breaks
        if (!$shift->requiresBreak()) {
            return;
        }

        // Get all checked-in assignments for this shift
        $assignments = $shift->assignments()
            ->where('status', 'checked_in')
            ->with('worker')
            ->get();

        $stats['assignments_checked'] += $assignments->count();

        foreach ($assignments as $assignment) {
            try {
                $this->enforceAssignmentBreakCompliance($assignment, $shift, $stats);
            } catch (\Exception $e) {
                Log::error('Failed to enforce break compliance for assignment', [
                    'assignment_id' => $assignment->id,
                    'worker_id' => $assignment->worker_id,
                    'error' => $e->getMessage(),
                ]);
                // Continue processing other assignments
            }
        }
    }

    /**
     * Enforce break compliance for a specific assignment.
     *
     * @param ShiftAssignment $assignment
     * @param Shift $shift
     * @param array &$stats
     * @return void
     */
    protected function enforceAssignmentBreakCompliance(ShiftAssignment $assignment, Shift $shift, array &$stats)
    {
        // Check compliance status
        $compliance = $assignment->checkBreakCompliance();

        // If worker needs a reminder, send notification
        if ($compliance['needs_reminder']) {
            $this->sendBreakReminder($assignment, $shift, $compliance);
            $stats['reminders_sent']++;

            // Record that warning was sent
            $assignment->recordBreakWarning();
        }

        // Flag non-compliant assignments (working 6+ hours without proper break)
        if (!$compliance['compliant'] && $compliance['requires_break']) {
            $this->flagNonCompliance($assignment, $compliance);
            $stats['compliance_flags']++;
        }
    }

    /**
     * Send break reminder notification to worker.
     *
     * @param ShiftAssignment $assignment
     * @param Shift $shift
     * @param array $compliance
     * @return void
     */
    protected function sendBreakReminder(ShiftAssignment $assignment, Shift $shift, array $compliance)
    {
        $worker = $assignment->worker;

        if (!$worker) {
            Log::warning('Cannot send break reminder - worker not found', [
                'assignment_id' => $assignment->id,
            ]);
            return;
        }

        try {
            $worker->notify(new BreakReminderNotification($assignment, $shift, $compliance));

            Log::info('Break reminder sent', [
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'hours_worked' => $compliance['hours_worked'],
                'required_minutes' => $compliance['required_minutes'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send break reminder notification', [
                'assignment_id' => $assignment->id,
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Flag assignment for non-compliance.
     *
     * @param ShiftAssignment $assignment
     * @param array $compliance
     * @return void
     */
    protected function flagNonCompliance(ShiftAssignment $assignment, array $compliance)
    {
        // Update assignment to indicate break compliance issue
        if (!$assignment->break_compliance_met) {
            $assignment->break_required_by = now()->toDateTimeString();
            $assignment->save();

            Log::warning('Assignment flagged for break non-compliance', [
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'shift_id' => $assignment->shift_id,
                'hours_worked' => $compliance['hours_worked'],
                'break_minutes' => $compliance['break_minutes'],
                'required_minutes' => $compliance['required_minutes'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::critical('Break compliance enforcement job failed after all retries', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
