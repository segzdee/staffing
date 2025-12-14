<?php

namespace App\Listeners;

use App\Events\ApplicationAccepted;
use App\Mail\ApplicationAcceptedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyWorkerOfAcceptance implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ApplicationAccepted $event): void
    {
        $application = $event->application;
        $worker = $application->worker;

        try {
            Mail::to($worker->email)->send(new ApplicationAcceptedMail($application));
        } catch (\Exception $e) {
            \Log::error("Failed to send acceptance notification to worker {$worker->id}: " . $e->getMessage());
        }
    }
}
