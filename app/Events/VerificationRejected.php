<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerificationRejected
{
    use Dispatchable, SerializesModels;

    public $user;
    public $reason;

    public function __construct(User $user, $reason = null)
    {
        $this->user = $user;
        $this->reason = $reason;
    }
}
