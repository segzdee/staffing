<?php

namespace App\Notifications;

use App\Models\WorkerSuspension;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-009: Notification sent when a worker's suspension is lifted.
 */
class SuspensionLiftedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public WorkerSuspension $suspension,
        public ?string $notes = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('suspensions.notifications.channels', ['mail', 'database']);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Account Suspension Has Been Lifted')
            ->greeting('Good News!')
            ->line('Your account suspension on OvertimeStaff has been lifted.');

        // Add reason the suspension was lifted
        $wasOverturned = $this->suspension->status === WorkerSuspension::STATUS_OVERTURNED;

        if ($wasOverturned) {
            $message->line('Your appeal was approved and the suspension has been overturned.');
        } else {
            $message->line('The suspension period has ended.');
        }

        if ($this->notes) {
            $message->line('Notes: '.$this->notes);
        }

        $message
            ->line('You can now access all platform features and apply for shifts again.')
            ->action('Browse Available Shifts', route('dashboard.staff.marketplace'))
            ->line('We appreciate your patience and look forward to having you back on the platform.');

        // Add a reminder about strikes if applicable
        $worker = $notifiable;
        if ($worker->strike_count > 0) {
            $maxStrikes = config('suspensions.max_strikes_before_permanent', 5);
            $remaining = $maxStrikes - $worker->strike_count;
            $message->line("Please note: You have {$worker->strike_count} strike(s) on your account. After {$remaining} more strike(s), your account may be permanently suspended.");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suspension_lifted',
            'suspension_id' => $this->suspension->id,
            'was_overturned' => $this->suspension->status === WorkerSuspension::STATUS_OVERTURNED,
            'notes' => $this->notes,
            'message' => 'Your account suspension has been lifted. You can now apply for shifts again.',
        ];
    }
}
