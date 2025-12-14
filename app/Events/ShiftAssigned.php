<?php

namespace App\Events;

use App\Models\ShiftAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftAssigned
{
    use Dispatchable, SerializesModels;

    public $assignment;

    public function __construct(ShiftAssignment $assignment)
    {
        $this->assignment = $assignment;
    }
}
