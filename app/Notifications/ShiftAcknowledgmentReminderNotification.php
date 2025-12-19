<?php

namespace App\Notifications;

use App\Models\ShiftAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Acknowledgment Reminder Notification
 * Sent to workers who haven't acknowledged their shift assignment
 */
class ShiftAcknowledgmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftAssignment $assignment;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShiftAssignment $assignment)
    {
        $this->assignment = $assignment;
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
        $shiftTitle = $shift?->title ?? 'your assigned shift';
        $shiftDate = $shift?->shift_date ?? 'the scheduled date';

        return (new MailMessage)
            ->subject('Action Required: Please Acknowledge Your Shift')
            ->greeting('Reminder: Shift Acknowledgment Needed')
            ->line("You have been assigned to {$shiftTitle} on {$shiftDate}.")
            ->line('**Please acknowledge your assignment as soon as possible.**')
            ->line('If you do not acknowledge within 6 hours of assignment, the shift will be automatically cancelled and reassigned.')
            ->action('Acknowledge Shift', url("/worker/shifts/{$shift?->id}/acknowledge"))
            ->line('If you cannot work this shift, please decline it so we can find a replacement.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->assignment->shift;

        return [
            'type' => 'shift_acknowledgment_reminder',
            'title' => 'Please Acknowledge Your Shift',
            'message' => "Reminder: You need to acknowledge your assignment to {$shift?->title}. Auto-cancellation in 4 hours.",
            'assignment_id' => $this->assignment->id,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'shift_date' => $shift?->shift_date,
            'action_url' => url("/worker/shifts/{$shift?->id}/acknowledge"),
            'action_text' => 'Acknowledge Now',
            'priority' => 'high',
        ];
    }
}
