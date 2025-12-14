<?php

namespace App\Observers;

use App\Models\ShiftAssignment;
use App\Services\BadgeService;
use App\Events\ShiftCompleted;
use Illuminate\Support\Facades\Log;

class ShiftAssignmentObserver
{
    protected $badgeService;

    public function __construct(BadgeService $badgeService)
    {
        $this->badgeService = $badgeService;
    }

    /**
     * Handle the ShiftAssignment "created" event.
     */
    public function created(ShiftAssignment $shiftAssignment): void
    {
        // Assignment created - no badge trigger needed
    }

    /**
     * Handle the ShiftAssignment "updated" event.
     */
    public function updated(ShiftAssignment $shiftAssignment): void
    {
        $worker = $shiftAssignment->worker;
        
        if (!$worker || !$worker->isWorker()) {
            return;
        }

        // Check if status changed to checked_in
        if ($shiftAssignment->wasChanged('status') && $shiftAssignment->status === 'checked_in') {
            // Check for early_bird badge
            $this->badgeService->checkAndAward($worker, 'checked_in');
        }

        // Check if status changed to completed
        if ($shiftAssignment->wasChanged('status') && $shiftAssignment->status === 'completed') {
            // Fire shift completed event
            event(new ShiftCompleted($shiftAssignment));
            
            // Check for shift completion badges
            $awarded = $this->badgeService->checkAndAward($worker, 'shift_completed');
            
            if (!empty($awarded)) {
                Log::info("Badges awarded to worker {$worker->id}", [
                    'badges' => $awarded->pluck('badge_type')->toArray()
                ]);
            }
        }
    }

    /**
     * Handle the ShiftAssignment "deleted" event.
     */
    public function deleted(ShiftAssignment $shiftAssignment): void
    {
        //
    }

    /**
     * Handle the ShiftAssignment "restored" event.
     */
    public function restored(ShiftAssignment $shiftAssignment): void
    {
        //
    }

    /**
     * Handle the ShiftAssignment "force deleted" event.
     */
    public function forceDeleted(ShiftAssignment $shiftAssignment): void
    {
        //
    }
}
