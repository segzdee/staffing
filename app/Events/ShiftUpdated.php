<?php

namespace App\Events;

use App\Models\Shift;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shift;
    public $updateType; // 'new', 'spots_changed', 'surge_activated', 'filled', 'cancelled'

    public function __construct(Shift $shift, $updateType = 'updated')
    {
        $this->shift = $shift;
        $this->updateType = $updateType;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('shifts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'shift.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'shift_id' => $this->shift->id,
            'title' => $this->shift->title,
            'status' => $this->shift->status,
            'spots_remaining' => $this->shift->required_workers - $this->shift->filled_workers,
            'update_type' => $this->updateType,
        ];
    }
}
