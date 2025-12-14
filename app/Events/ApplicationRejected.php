<?php

namespace App\Events;

use App\Models\ShiftApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationRejected
{
    use Dispatchable, SerializesModels;

    public $application;

    public function __construct(ShiftApplication $application)
    {
        $this->application = $application;
    }
}
