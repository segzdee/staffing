<?php

namespace App\Notifications;

use App\Models\RosterInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * BIZ-005: Notification sent to businesses when a worker accepts their roster invitation.
 */
class InvitationAcceptedNotification extends Notification implements ShouldQueue
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
        $worker = $this->invitation->worker;
        $roster = $this->invitation->roster;
        $appName = config('app.name', 'OvertimeStaff');

        $mail = (new MailMessage)
            ->subject("{$worker->name} Accepted Your Roster Invitation")
            ->greeting("Great news, {$notifiable->name}!")
            ->line("**{$worker->name}** has accepted your invitation to join your **{$roster->name}** roster.")
            ->line('');

        // Include worker stats if available
        if ($worker->total_shifts_completed > 0) {
            $mail->line('**Worker Stats:**')
                ->line("- Shifts Completed: {$worker->total_shifts_completed}")
                ->line('- Rating: '.number_format($worker->rating_as_worker, 1).'/5')
                ->line('');
        }

        $mail->line("You can now invite {$worker->name} to shifts directly from your roster or shift management pages.")
            ->action('View Roster', route('business.rosters.show', $roster))
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
        $worker = $this->invitation->worker;
        $roster = $this->invitation->roster;

        return [
            'type' => 'invitation_accepted',
            'title' => "{$worker->name} Joined Your Roster",
            'message' => "{$worker->name} has accepted your invitation to join the {$roster->name} roster.",
            'invitation_id' => $this->invitation->id,
            'roster_id' => $roster->id,
            'roster_name' => $roster->name,
            'worker_id' => $worker->id,
            'worker_name' => $worker->name,
            'worker_rating' => $worker->rating_as_worker,
            'action_url' => route('business.rosters.show', $roster),
            'action_text' => 'View Roster',
            'priority' => 'normal',
            'icon' => 'user-check',
            'color' => 'green',
        ];
    }
}
