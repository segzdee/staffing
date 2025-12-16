<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * AgencyGoLiveReady Notification
 *
 * Sent to admins when an agency submits a go-live request.
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 */
class AgencyGoLiveReady extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyProfile $agency;

    /**
     * Create a new notification instance.
     *
     * @param AgencyProfile $agency
     */
    public function __construct(AgencyProfile $agency)
    {
        $this->agency = $agency;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Agency Go-Live Request: ' . $this->agency->agency_name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new agency has submitted a go-live request and is ready for final review.')
            ->line('**Agency Details:**')
            ->line('- **Name:** ' . $this->agency->agency_name)
            ->line('- **User ID:** ' . $this->agency->user_id)
            ->line('- **Requested:** ' . now()->format('F j, Y \a\t g:i A'))
            ->line('The agency has completed all checklist requirements and passed compliance checks.')
            ->action('Review Agency', url('/panel/admin/agencies/' . $this->agency->user_id))
            ->line('Please review and approve within 24-48 hours.')
            ->salutation('OvertimeStaff Platform');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'agency_go_live_request',
            'agency_id' => $this->agency->id,
            'agency_user_id' => $this->agency->user_id,
            'agency_name' => $this->agency->agency_name,
            'message' => 'Agency "' . $this->agency->agency_name . '" has requested go-live activation.',
            'action_url' => '/panel/admin/agencies/' . $this->agency->user_id,
            'action_label' => 'Review Agency',
            'priority' => 'high',
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'notifications',
            'database' => 'default',
        ];
    }
}
