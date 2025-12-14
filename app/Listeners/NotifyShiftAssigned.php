<?php

namespace App\Listeners;

use App\Events\ShiftAssigned;
use App\Mail\ShiftAssignedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyShiftAssigned implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ShiftAssigned $event): void
    {
        $assignment = $event->assignment;
        $worker = $assignment->worker;
        $business = $assignment->shift->business;

        try {
            // Notify worker
            Mail::to($worker->email)->send(new ShiftAssignedMail($assignment));
            
            // Notify business
            Mail::to($business->email)->send(new ShiftAssignedMail($assignment));
        } catch (\Exception $e) {
            \Log::error("Failed to send assignment notification: " . $e->getMessage());
        }
    }
}
