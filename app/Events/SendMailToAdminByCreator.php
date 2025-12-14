<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMailToAdminByCreator
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user_id;
    public $report_title;
    public $attach_files;
   
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($report_title,$user_id,$attach_files)
    {
        $this->user_id=$user_id;
        $this->report_title=$report_title;
        $this->attach_files=$attach_files;
       
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
