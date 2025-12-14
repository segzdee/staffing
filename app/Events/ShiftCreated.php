<?php

namespace App\Events;

use App\Models\Shift;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCreated
{
    use Dispatchable, SerializesModels;

    public $shift;

    /**
     * Create a new event instance.
     */
    public function __construct(Shift $shift)
    {
        $this->shift = $shift;
    }
}
