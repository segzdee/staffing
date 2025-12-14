<?php

namespace App\Listeners;

use App\Events\ShiftCreated;
use App\Mail\ShiftCreatedMail;
use App\Services\ShiftMatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyMatchedWorkers implements ShouldQueue
{
    use InteractsWithQueue;

    protected $matchingService;

    /**
     * Create the event listener.
     */
    public function __construct(ShiftMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    /**
     * Handle the event.
     */
    public function handle(ShiftCreated $event): void
    {
        $shift = $event->shift;

        // Find top 20 matched workers
        $matchedWorkers = $this->matchingService->matchWorkersForShift($shift)
            ->take(20)
            ->filter(function($worker) {
                return $worker->match_score >= 50; // Only notify workers with 50%+ match
            });

        // Send email to each matched worker
        foreach ($matchedWorkers as $worker) {
            try {
                Mail::to($worker->email)->send(new ShiftCreatedMail($shift, $worker));
            } catch (\Exception $e) {
                \Log::error("Failed to send shift notification to worker {$worker->id}: " . $e->getMessage());
            }
        }
    }
}
