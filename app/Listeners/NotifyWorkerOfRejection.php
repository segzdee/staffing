<?php

namespace App\Listeners;

use App\Events\ApplicationRejected;
use App\Mail\ApplicationRejectedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyWorkerOfRejection implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ApplicationRejected $event): void
    {
        $application = $event->application;
        $worker = $application->worker;

        try {
            Mail::to($worker->email)->send(new ApplicationRejectedMail($application));
        } catch (\Exception $e) {
            \Log::error("Failed to send rejection notification to worker {$worker->id}: " . $e->getMessage());
        }
    }
}
