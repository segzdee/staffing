<?php

namespace App\Notifications;

use App\Models\SystemIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * ADM-004: System Alert Notification
 *
 * Multi-channel notification for system health alerts.
 * Supports: Email, Slack (via notification), Database
 */
class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected SystemIncident $incident;
    protected bool $isResolution;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new notification instance.
     */
    public function __construct(SystemIncident $incident, bool $isResolution = false)
    {
        $this->incident = $incident;
        $this->isResolution = $isResolution;

        // Set queue for reliability
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // Add database channel for logged-in admin users
        if (method_exists($notifiable, 'receivesDatabaseNotifications')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isResolution) {
            return $this->buildResolutionEmail($notifiable);
        }

        return $this->buildAlertEmail($notifiable);
    }

    /**
     * Build alert email.
     */
    protected function buildAlertEmail(object $notifiable): MailMessage
    {
        $severityEmoji = $this->getSeverityEmoji();
        $severityColor = $this->getSeverityColor();
        $metric = $this->incident->triggeredByMetric;

        $message = (new MailMessage)
            ->subject("{$severityEmoji} {$this->incident->title}")
            ->greeting("System Alert: {$this->incident->severity}")
            ->line("A system health alert has been triggered.")
            ->line("**Incident:** {$this->incident->title}")
            ->line("**Severity:** " . ucfirst($this->incident->severity))
            ->line("**Service:** {$this->incident->affected_service}")
            ->line("**Detected:** " . $this->incident->detected_at->format('M j, Y g:i A T'));

        if ($metric) {
            $message->line("**Current Value:** {$metric->value} {$metric->unit}");

            if ($metric->threshold_warning) {
                $message->line("**Warning Threshold:** {$metric->threshold_warning} {$metric->unit}");
            }
            if ($metric->threshold_critical) {
                $message->line("**Critical Threshold:** {$metric->threshold_critical} {$metric->unit}");
            }
        }

        if ($this->incident->description) {
            $message->line("")
                ->line("**Details:**")
                ->line($this->incident->description);
        }

        $dashboardUrl = config('app.url') . '/panel/admin/system-health/incidents/' . $this->incident->id;

        $message->action('View Incident', $dashboardUrl)
            ->line("")
            ->line("**Incident ID:** #{$this->incident->id}");

        // Add urgency for critical alerts
        if ($this->incident->severity === 'critical') {
            $message->line("")
                ->line("This is a **CRITICAL** alert requiring immediate attention.");
        }

        return $message;
    }

    /**
     * Build resolution email.
     */
    protected function buildResolutionEmail(object $notifiable): MailMessage
    {
        $resolutionTime = $this->formatResolutionTime();

        $message = (new MailMessage)
            ->subject("Resolved: {$this->incident->title}")
            ->greeting("Incident Resolved")
            ->line("The following system incident has been resolved.")
            ->line("**Incident:** {$this->incident->title}")
            ->line("**Service:** {$this->incident->affected_service}");

        if ($resolutionTime) {
            $message->line("**Resolution Time:** {$resolutionTime}");
        }

        if ($this->incident->resolution_notes) {
            $message->line("")
                ->line("**Resolution Notes:**")
                ->line($this->incident->resolution_notes);
        }

        if ($this->incident->prevention_steps) {
            $message->line("")
                ->line("**Prevention Steps:**")
                ->line($this->incident->prevention_steps);
        }

        $dashboardUrl = config('app.url') . '/panel/admin/system-health/incidents/' . $this->incident->id;

        $message->action('View Incident Details', $dashboardUrl)
            ->line("**Incident ID:** #{$this->incident->id}");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $metric = $this->incident->triggeredByMetric;

        return [
            'type' => $this->isResolution ? 'system_alert_resolved' : 'system_alert',
            'incident_id' => $this->incident->id,
            'title' => $this->incident->title,
            'severity' => $this->incident->severity,
            'affected_service' => $this->incident->affected_service,
            'detected_at' => $this->incident->detected_at->toIso8601String(),
            'resolved_at' => $this->incident->resolved_at?->toIso8601String(),
            'current_value' => $metric?->value,
            'unit' => $metric?->unit,
            'threshold_warning' => $metric?->threshold_warning,
            'threshold_critical' => $metric?->threshold_critical,
            'dashboard_url' => config('app.url') . '/panel/admin/system-health/incidents/' . $this->incident->id,
            'is_resolution' => $this->isResolution,
        ];
    }

    /**
     * Get Slack representation (if using Laravel's native Slack notifications).
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $severityEmoji = $this->getSeverityEmoji();
        $severityColor = $this->getSeverityColor();
        $metric = $this->incident->triggeredByMetric;

        if ($this->isResolution) {
            return (new SlackMessage)
                ->success()
                ->content('Incident Resolved')
                ->attachment(function ($attachment) {
                    $attachment->title($this->incident->title)
                        ->fields([
                            'Service' => $this->incident->affected_service,
                            'Resolution Time' => $this->formatResolutionTime() ?? 'N/A',
                        ])
                        ->footer('OvertimeStaff System Health')
                        ->timestamp($this->incident->resolved_at ?? now());
                });
        }

        $slackMessage = (new SlackMessage)
            ->from('OvertimeStaff Alerts')
            ->content("{$severityEmoji} System Alert: {$this->incident->severity}");

        // Set color based on severity
        if ($this->incident->severity === 'critical') {
            $slackMessage->error();
        } elseif ($this->incident->severity === 'warning' || $this->incident->severity === 'high') {
            $slackMessage->warning();
        }

        $slackMessage->attachment(function ($attachment) use ($metric, $severityColor) {
            $fields = [
                'Severity' => ucfirst($this->incident->severity),
                'Service' => $this->incident->affected_service,
                'Detected' => $this->incident->detected_at->format('M j, g:i A'),
                'Incident ID' => '#' . $this->incident->id,
            ];

            if ($metric) {
                $fields['Current Value'] = "{$metric->value} {$metric->unit}";
                if ($metric->threshold_critical) {
                    $fields['Threshold'] = "{$metric->threshold_critical} {$metric->unit}";
                }
            }

            $attachment->title($this->incident->title)
                ->color($severityColor)
                ->fields($fields)
                ->action('View Incident', config('app.url') . '/panel/admin/system-health/incidents/' . $this->incident->id)
                ->footer('OvertimeStaff System Health')
                ->timestamp($this->incident->detected_at);
        });

        return $slackMessage;
    }

    /**
     * Get severity emoji.
     */
    protected function getSeverityEmoji(): string
    {
        return match ($this->incident->severity) {
            'critical' => '[CRITICAL]',
            'high' => '[HIGH]',
            'warning' => '[WARNING]',
            'low' => '[INFO]',
            default => '[ALERT]',
        };
    }

    /**
     * Get severity color.
     */
    protected function getSeverityColor(): string
    {
        return match ($this->incident->severity) {
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'warning' => '#ffc107',
            'low' => '#17a2b8',
            default => '#6c757d',
        };
    }

    /**
     * Format resolution time.
     */
    protected function formatResolutionTime(): ?string
    {
        $minutes = $this->incident->duration_minutes;

        if ($minutes === null) {
            if ($this->incident->detected_at && $this->incident->resolved_at) {
                $minutes = $this->incident->detected_at->diffInMinutes($this->incident->resolved_at);
            } else {
                return null;
            }
        }

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return "{$hours}h {$remainingMinutes}m";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return "{$days}d {$remainingHours}h";
    }

    /**
     * Handle failed notification.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('System alert notification failed', [
            'incident_id' => $this->incident->id,
            'is_resolution' => $this->isResolution,
            'error' => $exception->getMessage(),
        ]);
    }
}
