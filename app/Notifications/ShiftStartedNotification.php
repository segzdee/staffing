<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Started Notification
 * Sent to assigned workers when a shift is marked as started
 */
class ShiftStartedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject("Shift Started: {$this->shift->title}")
            ->greeting('Your Shift Has Started')
            ->line("The shift {$this->shift->title} is now in progress.")
            ->line("Don't forget to check in if you haven't already!")
            ->action('View Shift', url("/worker/shifts/{$this->shift->id}"));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_started',
            'title' => 'Shift Started',
            'message' => "{$this->shift->title} is now in progress. Please check in!",
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'shift_date' => $this->shift->shift_date,
            'action_url' => url("/worker/shifts/{$this->shift->id}"),
        ];
    }
}
