<?php

namespace App\Listeners;

use App\Events\ShiftCancelled;
use App\Mail\ShiftCancelledMail;
use App\Models\ShiftAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyShiftCancelled implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShiftCancelled $event): void
    {
        $shift = $event->shift;
        $reason = $event->reason;

        try {
            // Notify business
            Mail::to($shift->business->email)->send(
                new ShiftCancelledMail($shift, $shift->business, $reason)
            );

            // Notify all assigned workers
            $assignments = ShiftAssignment::where('shift_id', $shift->id)
                ->where('status', '!=', 'cancelled')
                ->with('worker')
                ->get();

            foreach ($assignments as $assignment) {
                Mail::to($assignment->worker->email)->send(
                    new ShiftCancelledMail($shift, $assignment->worker, $reason)
                );
            }
        } catch (\Exception $e) {
            \Log::error("Failed to send cancellation notifications: " . $e->getMessage());
        }
    }
}
