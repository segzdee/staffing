<?php

namespace App\Notifications\Agency;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Weekly summary notification sent to admins after scorecard generation.
 * AGY-005: Agency Performance Notification System
 */
class WeeklyScorecardSummaryNotification extends Notification implements ShouldQueue
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
            ->subject("Weekly Agency Performance Summary - {$this->summary['period_start']} to {$this->summary['period_end']}")
            ->greeting("Weekly Agency Scorecard Summary")
            ->line("The weekly agency performance scorecards have been generated.")
            ->line("**Period:** {$this->summary['period_start']} to {$this->summary['period_end']}")
            ->line("---")
            ->line("**Agency Status Distribution:**")
            ->line("- GREEN (Good Standing): {$this->summary['green']} agencies")
            ->line("- YELLOW (Warning): {$this->summary['yellow']} agencies")
            ->line("- RED (Critical): {$this->summary['red']} agencies")
            ->line("---")
            ->line("**Actions Taken:**")
            ->line("- Warnings Sent: {$this->summary['warnings_sent']}")
            ->line("- Sanctions Applied: {$this->summary['sanctions_applied']}");

        if (isset($this->summary['notifications'])) {
            $notifications = $this->summary['notifications'];
            $message->line("---")
                ->line("**Notifications Sent:**")
                ->line("- Yellow Warnings: {$notifications['yellow_warnings']}")
                ->line("- Red Alerts: {$notifications['red_alerts']}")
                ->line("- Fee Increases: {$notifications['fee_increases']}")
                ->line("- Suspensions: {$notifications['suspensions']}")
                ->line("- Improvements: {$notifications['improvements']}");

            if ($notifications['errors'] > 0) {
                $message->line("- Errors: {$notifications['errors']} (check logs)");
            }
        }

        $message->action('View Performance Dashboard', url('/admin/agencies/performance'))
            ->line('Review the full details in the admin dashboard.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'weekly_scorecard_summary',
            'title' => 'Weekly Agency Scorecard Summary',
            'summary' => $this->summary,
            'action_url' => url('/admin/agencies/performance'),
            'priority' => $this->summary['red'] > 0 ? 'warning' : 'info',
        ];
    }
}
