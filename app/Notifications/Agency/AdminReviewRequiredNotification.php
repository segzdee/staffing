<?php

namespace App\Notifications\Agency;

use App\Models\AgencyPerformanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to admins when an agency requires manual review.
 * AGY-005: Agency Performance Notification System
 */
class AdminReviewRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyPerformanceNotification $performanceNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(AgencyPerformanceNotification $performanceNotification)
    {
        $this->performanceNotification = $performanceNotification;
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
        $agency = $this->performanceNotification->agency;
        $agencyName = $agency?->agencyProfile?->agency_name ?? $agency?->name ?? 'Unknown Agency';

        return (new MailMessage)
            ->error()
            ->subject("Admin Review Required: {$agencyName}")
            ->greeting("Attention Required")
            ->line("An agency requires your manual review due to unresolved performance issues.")
            ->line("**Agency:** {$agencyName}")
            ->line("**Notification Type:** {$this->performanceNotification->type_display}")
            ->line("**Severity:** " . ucfirst($this->performanceNotification->severity))
            ->line("**Days Since Original Notification:** " . $this->performanceNotification->created_at->diffInDays(now()))
            ->line("**Escalation Level:** {$this->performanceNotification->escalation_level}")
            ->when($this->performanceNotification->escalation_reason, function ($message) {
                return $message->line("**Escalation Reason:** {$this->performanceNotification->escalation_reason}");
            })
            ->line("**Current Status:**")
            ->line("- Acknowledged: " . ($this->performanceNotification->acknowledged ? 'Yes' : 'No'))
            ->line("- Appealed: " . ($this->performanceNotification->appealed ? 'Yes' : 'No'))
            ->action('Review Agency', url("/admin/agencies/{$this->performanceNotification->agency_id}/performance"))
            ->line('Please review this case and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $agency = $this->performanceNotification->agency;

        return [
            'type' => 'admin_review_required',
            'title' => 'Agency Review Required',
            'message' => "Agency {$agency?->name} requires manual review due to unresolved performance issues.",
            'notification_id' => $this->performanceNotification->id,
            'agency_id' => $this->performanceNotification->agency_id,
            'agency_name' => $agency?->agencyProfile?->agency_name ?? $agency?->name,
            'original_notification_type' => $this->performanceNotification->notification_type,
            'severity' => $this->performanceNotification->severity,
            'escalation_level' => $this->performanceNotification->escalation_level,
            'days_unresolved' => $this->performanceNotification->created_at->diffInDays(now()),
            'action_url' => url("/admin/agencies/{$this->performanceNotification->agency_id}/performance"),
            'priority' => 'critical',
        ];
    }
}
