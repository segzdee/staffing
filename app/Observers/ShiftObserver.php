<?php

namespace App\Observers;

use App\Events\ShiftCancelled;
use App\Events\ShiftCreated;
use App\Events\ShiftUpdated;
use App\Models\Shift;

class ShiftObserver
{
    /**
     * Handle the Shift "created" event.
     */
    public function created(Shift $shift): void
    {
        event(new ShiftCreated($shift));
    }

    /**
     * Handle the Shift "updated" event.
     */
    public function updated(Shift $shift): void
    {
        event(new ShiftUpdated($shift));

        if ($shift->wasChanged('status') && $shift->status === 'cancelled') {
            event(new ShiftCancelled($shift));
        }
    }

    /**
     * Handle the Shift "deleted" event.
     */
    public function deleted(Shift $shift): void
    {
        //
    }

    /**
     * Handle the Shift "restored" event.
     */
    public function restored(Shift $shift): void
    {
        //
    }

    /**
     * Handle the Shift "force deleted" event.
     */
    public function forceDeleted(Shift $shift): void
    {
        //
    }
}
