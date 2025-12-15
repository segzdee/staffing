<?php

namespace App\Notifications\Agency;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Summary notification for admins about daily escalation processing.
 * AGY-005: Agency Performance Notification System
 */
class EscalationSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $summary;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $summary)
    {
        $this->summary = $summary;
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
        $message = (new MailMessage)
            ->subject("Daily Escalation Summary - {$this->summary['total_processed']} Notifications Processed")
            ->greeting("Daily Escalation Summary")
            ->line("The following escalations were processed today:")
            ->line("- First-level escalations: {$this->summary['first_escalations']}")
            ->line("- Follow-up reminders sent: {$this->summary['follow_ups_sent']}")
            ->line("- Admin reviews required: {$this->summary['admin_reviews_required']}");

        if ($this->summary['errors'] > 0) {
            $message->line("- Errors encountered: {$this->summary['errors']}");
        }

        return $message
            ->action('View Unacknowledged Notifications', url('/admin/agencies/performance/notifications?unacknowledged=1'))
            ->line('Please review agencies requiring attention.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'escalation_summary',
            'title' => 'Daily Escalation Summary',
            'summary' => $this->summary,
            'action_url' => url('/admin/agencies/performance/notifications'),
            'priority' => $this->summary['admin_reviews_required'] > 0 ? 'warning' : 'info',
        ];
    }
}
