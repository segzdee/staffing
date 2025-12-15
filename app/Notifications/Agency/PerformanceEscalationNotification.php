<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a performance notification is escalated due to no acknowledgment.
 * AGY-005: Agency Performance Notification System
 */
class PerformanceEscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyPerformanceNotification $originalNotification;
    protected bool $isFollowUp;

    /**
     * Create a new notification instance.
     */
    public function __construct(AgencyPerformanceNotification $notification, bool $isFollowUp = false)
    {
        $this->originalNotification = $notification;
        $this->isFollowUp = $isFollowUp;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->isFollowUp
            ? "REMINDER: Unacknowledged Performance Notification"
            : "ESCALATION: Urgent - Your Attention Required";

        $message = (new MailMessage)
            ->error()
            ->subject($subject)
            ->greeting("Urgent Attention Required")
            ->line("You have an unacknowledged performance notification that requires your immediate attention.")
            ->line("**Original Notification:** {$this->originalNotification->title}")
            ->line("**Sent:** {$this->originalNotification->sent_at?->format('M j, Y g:i A')}")
            ->line("**Days Unacknowledged:** {$this->originalNotification->created_at->diffInDays(now())}");

        if ($this->originalNotification->improvement_deadline) {
            $message->line("**Improvement Deadline:** {$this->originalNotification->improvement_deadline->format('M j, Y')}");
        }

        return $message
            ->line("---")
            ->line("**Failure to acknowledge may result in:**")
            ->line("- Further escalation to platform administrators")
            ->line("- Delayed access to premium features")
            ->line("- Account restrictions")
            ->action('Acknowledge Now', url("/agency/performance/notifications/{$this->originalNotification->id}/acknowledge"))
            ->line("Please acknowledge this notification immediately to avoid further escalation.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => $this->isFollowUp ? 'performance_follow_up' : 'performance_escalation',
            'title' => $this->isFollowUp ? 'Reminder: Unacknowledged Notification' : 'Escalation: Urgent Action Required',
            'original_notification_id' => $this->originalNotification->id,
            'original_type' => $this->originalNotification->notification_type,
            'days_unacknowledged' => $this->originalNotification->created_at->diffInDays(now()),
            'follow_up_count' => $this->originalNotification->follow_up_count,
            'action_url' => url("/agency/performance/notifications/{$this->originalNotification->id}/acknowledge"),
            'priority' => 'critical',
        ];
    }
}
