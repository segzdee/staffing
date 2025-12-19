<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * New Shift Posted Notification
 * Sent to matching workers when a new shift is posted
 */
class NewShiftPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Shift $shift;

    /**
     * Create a new notification instance.
     */
    public function __construct(Shift $shift)
    {
        $this->shift = $shift;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $rate = $this->shift->final_rate ?? $this->shift->base_rate ?? 'Competitive';
        $location = $this->shift->location_city ?? 'nearby';

        return (new MailMessage)
            ->subject("New Shift Available: {$this->shift->title}")
            ->greeting('New Shift Opportunity!')
            ->line('A new shift matching your profile has been posted.')
            ->line("**{$this->shift->title}**")
            ->line("**Date:** {$this->shift->shift_date}")
            ->line("**Time:** {$this->shift->start_time} - {$this->shift->end_time}")
            ->line("**Location:** {$location}")
            ->line("**Rate:** \${$rate}/hour")
            ->action('Apply Now', url("/shifts/{$this->shift->id}"))
            ->line('Act fast - positions fill quickly!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_shift_posted',
            'title' => 'New Shift Available',
            'message' => "{$this->shift->title} on {$this->shift->shift_date} in {$this->shift->location_city}",
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'shift_date' => $this->shift->shift_date,
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
            'location_city' => $this->shift->location_city,
            'base_rate' => $this->shift->base_rate,
            'action_url' => url("/shifts/{$this->shift->id}"),
            'action_text' => 'View Shift',
        ];
    }
}
