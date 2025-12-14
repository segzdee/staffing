<?php

namespace App\Events;

use App\Models\ShiftSwap;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SwapRequested
{
    use Dispatchable, SerializesModels;

    public $swap;

    public function __construct(ShiftSwap $swap)
    {
        $this->swap = $swap;
    }
}
