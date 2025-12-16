<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * AgencyActivated Notification
 *
 * Sent to agency owner when their agency is activated and goes live.
 *
 * TASK: AGY-REG-005 - Go-Live Checklist and Compliance Monitoring
 */
class AgencyActivated extends Notification implements ShouldQueue
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
            ->subject('Congratulations! Your Agency is Now Live on OvertimeStaff')
            ->greeting('Congratulations, ' . $notifiable->name . '!')
            ->line('Great news! Your agency **' . $this->agency->agency_name . '** has been approved and is now live on OvertimeStaff!')
            ->line('**What You Can Do Now:**')
            ->line('- Browse available shifts and assign your workers')
            ->line('- Manage your worker pool and add new team members')
            ->line('- Track your commissions and view analytics')
            ->line('- Access the full agency dashboard')
            ->action('Go to Dashboard', route('dashboard'))
            ->line('')
            ->line('**Getting Started Tips:**')
            ->line('1. Make sure all your workers have completed their profiles')
            ->line('2. Set up your notification preferences to stay informed')
            ->line('3. Browse available shifts in your service area')
            ->line('4. Keep your response time under 2 hours for best results')
            ->line('')
            ->line('Thank you for joining OvertimeStaff. We are excited to have you as a partner!')
            ->salutation('Welcome to the team! - The OvertimeStaff Team');
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
            'type' => 'agency_activated',
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->agency_name,
            'message' => 'Congratulations! Your agency "' . $this->agency->agency_name . '" is now live on OvertimeStaff!',
            'action_url' => route('dashboard'),
            'action_label' => 'Go to Dashboard',
            'priority' => 'high',
            'activated_at' => $this->agency->activated_at,
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
