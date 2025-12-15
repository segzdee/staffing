<?php

namespace App\Notifications\Agency;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Critical alert notification sent to admins when agencies have red status.
 * AGY-005: Agency Performance Notification System
 */
class CriticalPerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Collection $criticalScorecards;
    protected array $summary;

    /**
     * Create a new notification instance.
     */
    public function __construct(Collection $criticalScorecards, array $summary)
    {
        $this->criticalScorecards = $criticalScorecards;
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
            ->error()
            ->subject("URGENT: {$this->criticalScorecards->count()} Agencies with Critical Performance")
            ->greeting("Critical Performance Alert")
            ->line("**{$this->criticalScorecards->count()} agencies** have critical (RED) performance status this week.")
            ->line("Immediate review may be required.");

        $message->line("---");
        $message->line("**Agencies Requiring Attention:**");

        foreach ($this->criticalScorecards->take(10) as $scorecard) {
            $agencyName = $scorecard->agency?->agencyProfile?->agency_name
                ?? $scorecard->agency?->name
                ?? "Agency #{$scorecard->agency_id}";

            $message->line("- **{$agencyName}**: Fill Rate {$scorecard->fill_rate}%, No-Show {$scorecard->no_show_rate}%");
        }

        if ($this->criticalScorecards->count() > 10) {
            $remaining = $this->criticalScorecards->count() - 10;
            $message->line("- ... and {$remaining} more agencies");
        }

        $suspensions = $this->summary['notifications']['suspensions'] ?? 0;
        if ($suspensions > 0) {
            $message->line("---")
                ->line("**{$suspensions} agencies have been suspended** due to 3+ consecutive weeks of poor performance.");
        }

        $message->action('Review All Critical Agencies', url('/admin/agencies/performance?status=red'))
            ->line('Please review and take appropriate action.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'critical_performance_alert',
            'title' => 'Critical Agency Performance Alert',
            'critical_count' => $this->criticalScorecards->count(),
            'suspensions' => $this->summary['notifications']['suspensions'] ?? 0,
            'agency_ids' => $this->criticalScorecards->pluck('agency_id')->toArray(),
            'action_url' => url('/admin/agencies/performance?status=red'),
            'priority' => 'critical',
        ];
    }
}
