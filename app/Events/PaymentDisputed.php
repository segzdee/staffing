<?php

namespace App\Events;

use App\Models\ShiftPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a payment dispute is created.
 */
class PaymentDisputed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The shift payment that is being disputed.
     *
     * @var ShiftPayment
     */
    public $shiftPayment;

    /**
     * The reason for the dispute.
     *
     * @var string
     */
    public $reason;

    /**
     * Create a new event instance.
     *
     * @param ShiftPayment $shiftPayment
     * @param string $reason
     */
    public function __construct(ShiftPayment $shiftPayment, string $reason)
    {
        $this->shiftPayment = $shiftPayment;
        $this->reason = $reason;
    }
}
