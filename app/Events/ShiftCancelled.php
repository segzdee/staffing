<?php

namespace App\Events;

use App\Models\Shift;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCancelled
{
    use Dispatchable, SerializesModels;

    public $shift;
    public $reason;

    public function __construct(Shift $shift, $reason = null)
    {
        $this->shift = $shift;
        $this->reason = $reason;
    }
}
