<?php

namespace App\Events;

use App\Models\ShiftPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReleased
{
    use Dispatchable, SerializesModels;

    public $payment;

    public function __construct(ShiftPayment $payment)
    {
        $this->payment = $payment;
    }
}
