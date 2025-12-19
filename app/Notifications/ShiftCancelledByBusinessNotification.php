<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Cancelled By Business Notification
 * Sent to assigned workers when a shift is cancelled
 */
class ShiftCancelledByBusinessNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Shift Cancelled: {$this->shift->title}")
            ->greeting('Shift Cancellation Notice')
            ->line("We regret to inform you that {$this->shift->title} scheduled for {$this->shift->shift_date} has been cancelled by the employer.")
            ->line('If payment was held in escrow, it will be refunded to the employer.')
            ->line('We apologize for any inconvenience.')
            ->action('Find Other Shifts', url('/worker/shifts/available'))
            ->line('Browse other available shifts that match your profile.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_cancelled_by_business',
            'title' => 'Shift Cancelled',
            'message' => "{$this->shift->title} on {$this->shift->shift_date} has been cancelled.",
            'shift_id' => $this->shift->id,
            'shift_title' => $this->shift->title,
            'shift_date' => $this->shift->shift_date,
            'action_url' => url('/worker/shifts/available'),
            'action_text' => 'Find Other Shifts',
        ];
    }
}
