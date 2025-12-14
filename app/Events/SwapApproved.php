<?php

namespace App\Events;

use App\Models\ShiftSwap;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SwapApproved
{
    use Dispatchable, SerializesModels;

    public $swap;

    public function __construct(ShiftSwap $swap)
    {
        $this->swap = $swap;
    }
}
