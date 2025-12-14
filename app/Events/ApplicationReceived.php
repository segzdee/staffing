<?php

namespace App\Events;

use App\Models\ShiftApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationReceived
{
    use Dispatchable, SerializesModels;

    public $application;

    /**
     * Create a new event instance.
     */
    public function __construct(ShiftApplication $application)
    {
        $this->application = $application;
    }
}
