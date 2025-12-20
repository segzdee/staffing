<?php

namespace App\Observers;

use App\Events\ApplicationAccepted;
use App\Events\ApplicationReceived;
use App\Events\ApplicationRejected;
use App\Models\ShiftApplication;

class ShiftApplicationObserver
{
    /**
     * Handle the ShiftApplication "created" event.
     */
    public function created(ShiftApplication $application): void
    {
        event(new ApplicationReceived($application));
    }

    /**
     * Handle the ShiftApplication "updated" event.
     */
    public function updated(ShiftApplication $application): void
    {
        if ($application->wasChanged('status')) {
            match ($application->status) {
                'approved', 'accepted' => event(new ApplicationAccepted($application)),
                'rejected', 'declined' => event(new ApplicationRejected($application)),
                default => null,
            };
        }
    }

    /**
     * Handle the ShiftApplication "deleted" event.
     */
    public function deleted(ShiftApplication $shiftApplication): void
    {
        //
    }

    /**
     * Handle the ShiftApplication "restored" event.
     */
    public function restored(ShiftApplication $shiftApplication): void
    {
        //
    }

    /**
     * Handle the ShiftApplication "force deleted" event.
     */
    public function forceDeleted(ShiftApplication $shiftApplication): void
    {
        //
    }
}
