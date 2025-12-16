<?php

namespace App\Notifications;

use App\Models\AgencyInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * AGY-REG-004: Agency Worker Invitation Notification
 *
 * Sent when an agency invites a worker to join their pool.
 */
class AgencyWorkerInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyInvitation $invitation;

    /**
     * Create a new notification instance.
     *
     * @param AgencyInvitation $invitation
     */
    public function __construct(AgencyInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $agency = $this->invitation->agency;
        $agencyProfile = $agency->agencyProfile;
        $agencyName = $agencyProfile->agency_name ?? $agency->name;
        $recipientName = $this->invitation->name ?? 'there';
        $expiryDate = $this->invitation->expires_at->format('F j, Y');
        $invitationUrl = $this->invitation->getInvitationUrl();

        $mail = (new MailMessage)
            ->subject("You're invited to join {$agencyName} on OvertimeStaff")
            ->greeting("Hello {$recipientName}!")
            ->line("{$agencyName} has invited you to join their worker pool on OvertimeStaff, the shift marketplace platform.");

        // Add personal message if present
        if (!empty($this->invitation->personal_message)) {
            $mail->line("")
                ->line("Personal message from {$agencyName}:")
                ->line("\"{$this->invitation->personal_message}\"");
        }

        $mail->line("")
            ->line("As a member of {$agencyName}'s team, you'll have access to:")
            ->line("- Curated shift opportunities matched to your skills")
            ->line("- Reliable payment processing")
            ->line("- Support from your agency coordinator");

        // Add commission rate if preset
        if (!empty($this->invitation->preset_commission_rate)) {
            $mail->line("")
                ->line("Commission Rate: {$this->invitation->preset_commission_rate}%");
        }

        $mail->action('View Invitation', $invitationUrl)
            ->line("")
            ->line("This invitation will expire on {$expiryDate}.")
            ->line("")
            ->line("If you didn't expect this invitation, you can safely ignore this email.");

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agency_worker_invitation',
            'invitation_id' => $this->invitation->id,
            'agency_id' => $this->invitation->agency_id,
            'agency_name' => $this->invitation->agency->agencyProfile->agency_name ?? $this->invitation->agency->name,
            'invitation_url' => $this->invitation->getInvitationUrl(),
            'expires_at' => $this->invitation->expires_at->toIso8601String(),
        ];
    }
}
