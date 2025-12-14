<?php

namespace App\Listeners;

use App\Events\ShiftCompleted;
use App\Mail\ShiftCompletedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyShiftCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShiftCompleted $event): void
    {
        $assignment = $event->assignment;
        $worker = $assignment->worker;
        $business = $assignment->shift->business;

        try {
            // Notify worker
            Mail::to($worker->email)->send(new ShiftCompletedMail($assignment));
            
            // Notify business
            Mail::to($business->email)->send(new ShiftCompletedMail($assignment));
        } catch (\Exception $e) {
            \Log::error("Failed to send completion notification: " . $e->getMessage());
        }
    }
}
