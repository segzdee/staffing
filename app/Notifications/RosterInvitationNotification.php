<?php

namespace App\Notifications;

use App\Models\RosterInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * BIZ-005: Notification sent to workers when they receive a roster invitation.
 */
class RosterInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected RosterInvitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(RosterInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $roster = $this->invitation->roster;
        $business = $roster->business;
        $appName = config('app.name', 'OvertimeStaff');

        $mail = (new MailMessage)
            ->subject("Roster Invitation from {$business->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$business->name}** has invited you to join their **{$roster->name}** roster.")
            ->line("**Roster Type:** {$roster->type_display}");

        if ($this->invitation->message) {
            $mail->line('**Message from the business:**')
                ->line("\"{$this->invitation->message}\"");
        }

        $mail->line('')
            ->line('**What does this mean?**')
            ->line("Being on a business's roster means you'll be prioritized for their shifts and may receive early notifications about new opportunities.");

        if ($roster->type === 'preferred') {
            $mail->line('')
                ->line('As a **preferred worker**, you will be first in line for shifts at this business.');
        }

        $mail->line('')
            ->line("**This invitation expires:** {$this->invitation->expires_at->format('F j, Y \\a\\t g:i A')}")
            ->action('View Invitation', route('worker.roster-invitations.show', $this->invitation))
            ->line('')
            ->line('You can accept or decline this invitation from your dashboard.')
            ->salutation("Best regards,\nThe {$appName} Team");

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        $roster = $this->invitation->roster;
        $business = $roster->business;

        return [
            'type' => 'roster_invitation',
            'title' => "Roster Invitation from {$business->name}",
            'message' => "{$business->name} has invited you to join their {$roster->name} roster.",
            'invitation_id' => $this->invitation->id,
            'roster_id' => $roster->id,
            'roster_name' => $roster->name,
            'roster_type' => $roster->type,
            'business_id' => $business->id,
            'business_name' => $business->name,
            'expires_at' => $this->invitation->expires_at->toIso8601String(),
            'invitation_message' => $this->invitation->message,
            'action_url' => route('worker.roster-invitations.show', $this->invitation),
            'action_text' => 'View Invitation',
            'priority' => 'normal',
            'icon' => 'user-plus',
            'color' => 'blue',
        ];
    }
}
