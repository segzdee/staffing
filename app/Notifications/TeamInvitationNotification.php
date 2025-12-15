<?php

namespace App\Notifications;

use App\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * BIZ-003: Team Invitation Notification
 *
 * Sent when a user is invited to join a business team.
 */
class TeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $teamMember;
    protected $invitationToken;

    /**
     * Create a new notification instance.
     *
     * @param TeamMember $teamMember
     * @param string $invitationToken Plain (unhashed) token for the invitation URL
     */
    public function __construct(TeamMember $teamMember, string $invitationToken)
    {
        $this->teamMember = $teamMember;
        $this->invitationToken = $invitationToken;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $businessName = $this->teamMember->business->businessProfile->business_name
            ?? $this->teamMember->business->name;

        $inviterName = $this->teamMember->invitedBy->name ?? 'A team administrator';
        $roleName = $this->teamMember->role_name;
        $expiryDate = $this->teamMember->invitation_expires_at->format('F j, Y');
        $invitationUrl = route('team.invitation.accept', ['token' => $this->invitationToken]);

        return (new MailMessage)
            ->subject("You've been invited to join {$businessName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$inviterName} has invited you to join {$businessName} as a {$roleName}.")
            ->line($this->getRoleDescription())
            ->action('Accept Invitation', $invitationUrl)
            ->line("This invitation will expire on {$expiryDate}.")
            ->line('If you did not expect this invitation, no action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $businessName = $this->teamMember->business->businessProfile->business_name
            ?? $this->teamMember->business->name;

        return [
            'type' => 'team_invitation',
            'team_member_id' => $this->teamMember->id,
            'business_id' => $this->teamMember->business_id,
            'business_name' => $businessName,
            'role' => $this->teamMember->role,
            'role_name' => $this->teamMember->role_name,
            'invited_by' => $this->teamMember->invited_by,
            'invitation_url' => route('team.invitation.accept', ['token' => $this->invitationToken]),
            'expires_at' => $this->teamMember->invitation_expires_at,
        ];
    }

    /**
     * Get description of the role.
     *
     * @return string
     */
    protected function getRoleDescription(): string
    {
        return match($this->teamMember->role) {
            'administrator' => 'As an Administrator, you will have full access to manage shifts, workers, venues, and team members.',
            'location_manager' => 'As a Location Manager, you will be able to manage shifts and workers for assigned venues.',
            'scheduler' => 'As a Scheduler, you will be able to create and manage shifts.',
            'viewer' => 'As a Viewer, you will have read-only access to shifts and workers.',
            default => 'You will be able to access the business dashboard.',
        };
    }
}
