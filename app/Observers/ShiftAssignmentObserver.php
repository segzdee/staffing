<?php

namespace App\Observers;

use App\Models\ShiftAssignment;
use App\Services\BadgeService;
use App\Events\ShiftCompleted;
use Illuminate\Support\Facades\Log;

class ShiftAssignmentObserver
{
    protected $badgeService;
    protected $reliabilityService;

    public function __construct(BadgeService $badgeService, \App\Services\ReliabilityScoreService $reliabilityService)
    {
        $this->badgeService = $badgeService;
        $this->reliabilityService = $reliabilityService;
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

        // Trigger reliability score recalculation on any status change that affects score
        if ($shiftAssignment->wasChanged('status')) {
            $relevantStatuses = ['completed', 'no_show', 'cancelled_by_worker', 'checked_in'];
            if (in_array($shiftAssignment->status, $relevantStatuses)) {
                try {
                    $this->reliabilityService->recalculateAndSave($worker);
                } catch (\Exception $e) {
                    Log::error("Failed to update reliability score via observer: " . $e->getMessage());
                }
            }
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
                    'badges' => collect($awarded)->pluck('badge_type')->toArray()
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
