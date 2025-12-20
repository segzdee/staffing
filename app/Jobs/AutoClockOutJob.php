<?php

namespace App\Jobs;

use App\Models\ShiftAssignment;
use App\Services\TimeTrackingService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Auto Clock-Out Job
 *
 * Automatically clocks out workers who forgot to clock out after their shift ended.
 * Runs every 5 minutes via scheduler.
 */
class AutoClockOutJob implements ShouldQueue
{
    use Queueable;

    /**
     * Grace period in minutes after shift ends before auto clock-out.
     */
    protected int $gracePeriodMinutes = 30;

    /**
     * Execute the job.
     */
    public function handle(TimeTrackingService $timeTrackingService): void
    {
        Log::info('AutoClockOutJob: Starting auto clock-out process');

        // Find assignments that are checked in and shift has ended + grace period
        $assignments = ShiftAssignment::query()
            ->where('status', 'checked_in')
            ->where(function ($query) {
                $query->whereNotNull('actual_clock_in')
                    ->orWhereNotNull('check_in_time');
            })
            ->whereNull('actual_clock_out')
            ->whereNull('check_out_time')
            ->whereHas('shift', function ($query) {
                // Shift end time + grace period has passed
                $now = now();
                $graceMinutes = $this->gracePeriodMinutes;

                $query->where(function ($q) use ($now, $graceMinutes) {
                    // Regular shifts (end_time > start_time - same day)
                    $q->whereRaw('end_time > start_time')
                        ->whereRaw("DATE_ADD(CONCAT(shift_date, ' ', end_time), INTERVAL ? MINUTE) < ?", [
                            $graceMinutes,
                            $now->format('Y-m-d H:i:s'),
                        ]);
                })->orWhere(function ($q) use ($now, $graceMinutes) {
                    // Overnight shifts (end_time < start_time - ends next day)
                    $q->whereRaw('end_time < start_time')
                        ->whereRaw("DATE_ADD(CONCAT(DATE_ADD(shift_date, INTERVAL 1 DAY), ' ', end_time), INTERVAL ? MINUTE) < ?", [
                            $graceMinutes,
                            $now->format('Y-m-d H:i:s'),
                        ]);
                });
            })
            ->with(['shift', 'worker'])
            ->get();

        $count = 0;

        foreach ($assignments as $assignment) {
            try {
                $this->processAutoClockOut($assignment, $timeTrackingService);
                $count++;
            } catch (\Exception $e) {
                Log::error('AutoClockOutJob: Failed to auto clock-out assignment', [
                    'assignment_id' => $assignment->id,
                    'worker_id' => $assignment->worker_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('AutoClockOutJob: Completed', [
            'processed' => $count,
            'total_found' => $assignments->count(),
        ]);
    }

    /**
     * Process auto clock-out for a single assignment.
     */
    protected function processAutoClockOut(ShiftAssignment $assignment, TimeTrackingService $timeTrackingService): void
    {
        $shift = $assignment->shift;

        // Calculate the shift end datetime
        $endDateTime = $this->getShiftEndDateTime($shift);

        // Calculate hours worked (from clock-in to shift end time)
        $clockInTime = Carbon::parse($assignment->actual_clock_in ?? $assignment->check_in_time);
        $hoursWorked = $clockInTime->diffInMinutes($endDateTime) / 60;

        // Get break time
        $breakMinutes = $assignment->total_break_minutes ?? 0;
        $netHours = $hoursWorked - ($breakMinutes / 60);

        // Update the assignment
        $assignment->update([
            'actual_clock_out' => $endDateTime,
            'check_out_time' => $endDateTime,
            'status' => 'checked_out',
            'auto_clocked_out' => true,
            'auto_clock_out_time' => now(),
            'auto_clock_out_reason' => 'Automatic clock-out: shift ended without manual clock-out',
            'hours_worked' => max(0, $netHours),
            'gross_hours' => max(0, $hoursWorked),
            'net_hours_worked' => max(0, $netHours),
            'break_deduction_hours' => $breakMinutes / 60,
        ]);

        Log::info('AutoClockOutJob: Auto clocked out worker', [
            'assignment_id' => $assignment->id,
            'worker_id' => $assignment->worker_id,
            'shift_id' => $shift->id,
            'clock_in' => $clockInTime->toDateTimeString(),
            'auto_clock_out' => $endDateTime->toDateTimeString(),
            'hours_worked' => $netHours,
        ]);
    }

    /**
     * Get the shift end datetime.
     */
    protected function getShiftEndDateTime($shift): Carbon
    {
        $shiftDate = Carbon::parse($shift->shift_date);
        $endTime = Carbon::parse($shift->end_time);

        // If overnight shift (end_time < start_time), end is next day
        if ($shift->end_time < $shift->start_time) {
            $shiftDate->addDay();
        }

        return $shiftDate->setTimeFrom($endTime);
    }
}
