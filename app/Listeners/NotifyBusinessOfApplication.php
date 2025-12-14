<?php

namespace App\Listeners;

use App\Events\ApplicationReceived;
use App\Mail\ApplicationReceivedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyBusinessOfApplication implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ApplicationReceived $event): void
    {
        $application = $event->application;
        $business = $application->shift->business;

        try {
            Mail::to($business->email)->send(new ApplicationReceivedMail($application));
        } catch (\Exception $e) {
            \Log::error("Failed to send application notification to business {$business->id}: " . $e->getMessage());
        }
    }
}
