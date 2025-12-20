<?php

namespace App\Observers;

use App\Events\PaymentReleased;
use App\Models\ShiftPayment;

class ShiftPaymentObserver
{
    /**
     * Handle the ShiftPayment "created" event.
     */
    public function created(ShiftPayment $shiftPayment): void
    {
        //
    }

    /**
     * Handle the ShiftPayment "updated" event.
     */
    public function updated(ShiftPayment $payment): void
    {
        if ($payment->wasChanged('status') && $payment->status === 'paid_out') {
            event(new PaymentReleased($payment));
        }
    }

    /**
     * Handle the ShiftPayment "deleted" event.
     */
    public function deleted(ShiftPayment $shiftPayment): void
    {
        //
    }

    /**
     * Handle the ShiftPayment "restored" event.
     */
    public function restored(ShiftPayment $shiftPayment): void
    {
        //
    }

    /**
     * Handle the ShiftPayment "force deleted" event.
     */
    public function forceDeleted(ShiftPayment $shiftPayment): void
    {
        //
    }
}
