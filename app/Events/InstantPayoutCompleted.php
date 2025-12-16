<?php

namespace App\Events;

use App\Models\ShiftPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an instant payout is completed for a worker.
 */
class InstantPayoutCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The shift payment that was paid out.
     *
     * @var ShiftPayment
     */
    public $shiftPayment;

    /**
     * Create a new event instance.
     *
     * @param ShiftPayment $shiftPayment
     */
    public function __construct(ShiftPayment $shiftPayment)
    {
        $this->shiftPayment = $shiftPayment;
    }
}
