<?php

namespace App\Notifications;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Auto-Cancelled Notification
 * Sent when a shift is auto-cancelled due to no acknowledgment
 */
class ShiftAutoCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftAssignment $assignment;

    protected string $recipientType;

    /**
     * Create a new notification instance.
     *
     * @param  string  $recipientType  'worker' or 'business'
     */
    public function __construct(ShiftAssignment $assignment, string $recipientType = 'worker')
    {
        $this->assignment = $assignment;
        $this->recipientType = $recipientType;
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
        $shift = $this->assignment->shift;
        $shiftTitle = $shift?->title ?? 'the shift';
        $shiftDate = $shift?->shift_date ?? 'scheduled date';

        if ($this->recipientType === 'worker') {
            return (new MailMessage)
                ->subject('Shift Assignment Cancelled - No Acknowledgment')
                ->greeting('Shift Assignment Cancelled')
                ->line("Your assignment to {$shiftTitle} on {$shiftDate} has been automatically cancelled.")
                ->line('**Reason:** No acknowledgment received within 6 hours.')
                ->line('This may affect your reliability score. Please ensure you acknowledge future shift assignments promptly.')
                ->line('If you believe this was an error, please contact support.')
                ->action('View Available Shifts', url('/worker/shifts/available'));
        }

        $workerName = $this->assignment->worker?->name ?? 'The assigned worker';

        return (new MailMessage)
            ->subject('Worker Assignment Cancelled - Action May Be Required')
            ->greeting('Assignment Auto-Cancelled')
            ->line("{$workerName}'s assignment to {$shiftTitle} on {$shiftDate} has been cancelled.")
            ->line('**Reason:** Worker did not acknowledge within 6 hours.')
            ->line('The escrowed payment has been refunded to your account.')
            ->line('You may want to review other applicants or invite more workers to fill this position.')
            ->action('Manage Shift', url("/business/shifts/{$shift?->id}"));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->assignment->shift;

        $message = $this->recipientType === 'worker'
            ? "Your assignment to {$shift?->title} was cancelled due to no acknowledgment."
            : "Worker assignment for {$shift?->title} was auto-cancelled. Payment refunded.";

        return [
            'type' => 'shift_auto_cancelled',
            'title' => 'Shift Assignment Cancelled',
            'message' => $message,
            'assignment_id' => $this->assignment->id,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'shift_date' => $shift?->shift_date,
            'reason' => 'no_acknowledgment',
            'recipient_type' => $this->recipientType,
            'action_url' => $this->recipientType === 'worker'
                ? url('/worker/shifts/available')
                : url("/business/shifts/{$shift?->id}"),
        ];
    }
}
