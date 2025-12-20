<?php

namespace App\Events;

use App\Models\ShiftApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $application;

    public $status; // 'accepted', 'rejected'

    public function __construct(ShiftApplication $application, $status)
    {
        $this->application = $application;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->application->worker_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'application.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'application_id' => $this->application->id,
            'shift_id' => $this->application->shift_id,
            'shift_title' => $this->application->shift->title,
            'status' => $this->status,
        ];
    }
}
