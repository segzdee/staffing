<?php

namespace App\Notifications;

use App\Models\ShiftInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shift Invitation Notification
 * Sent to workers when they are invited to a shift
 */
class ShiftInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ShiftInvitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(ShiftInvitation $invitation)
    {
        $this->invitation = $invitation;
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
        $shift = $this->invitation->shift;
        $shiftTitle = $shift?->title ?? 'a shift';
        $shiftDate = $shift?->shift_date ?? 'upcoming';
        $businessName = $shift?->business?->name ?? 'A business';
        $message = $this->invitation->message;

        $mail = (new MailMessage)
            ->subject("You've Been Invited to Work a Shift")
            ->greeting('Shift Invitation!')
            ->line("{$businessName} has invited you to work {$shiftTitle}.")
            ->line("**Date:** {$shiftDate}")
            ->line("**Time:** {$shift?->start_time} - {$shift?->end_time}");

        if ($message) {
            $mail->line("**Message from employer:** {$message}");
        }

        return $mail
            ->action('View Invitation', url("/worker/invitations/{$this->invitation->id}"))
            ->line('Respond soon to secure this shift!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->invitation->shift;

        return [
            'type' => 'shift_invitation',
            'title' => 'Shift Invitation',
            'message' => "You've been invited to work {$shift?->title} on {$shift?->shift_date}",
            'invitation_id' => $this->invitation->id,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'shift_date' => $shift?->shift_date,
            'business_name' => $shift?->business?->name,
            'invitation_message' => $this->invitation->message,
            'action_url' => url("/worker/invitations/{$this->invitation->id}"),
            'action_text' => 'View Invitation',
        ];
    }
}
