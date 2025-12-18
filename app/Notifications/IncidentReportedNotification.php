<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-002: Incident Reported Notification
 *
 * Sent to admins and relevant parties when a new incident is reported.
 */
class IncidentReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    protected bool $isBusinessNotification;

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
    public function __construct(Incident $incident, bool $isBusinessNotification = false)
    {
        $this->incident = $incident;
        $this->isBusinessNotification = $isBusinessNotification;

        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // Critical incidents also get SMS (if configured)
        // if ($this->incident->severity === Incident::SEVERITY_CRITICAL) {
        //     $channels[] = 'vonage'; // or other SMS provider
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $severityEmoji = $this->getSeverityEmoji();
        $typeLabel = $this->incident->getTypeLabel();

        $subject = $this->isBusinessNotification
            ? "{$severityEmoji} Incident Reported at Your Venue - {$this->incident->incident_number}"
            : "{$severityEmoji} New Incident Report - {$this->incident->incident_number}";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($this->getGreeting());

        if ($this->incident->isCritical()) {
            $mail->error();
        }

        $mail->line($this->getIntroLine())
            ->line('')
            ->line('**Incident Details:**')
            ->line("- **Number:** {$this->incident->incident_number}")
            ->line("- **Type:** {$typeLabel}")
            ->line('- **Severity:** '.ucfirst($this->incident->severity))
            ->line("- **Reported By:** {$this->incident->reporter->name}")
            ->line("- **Time:** {$this->incident->incident_time->format('M d, Y g:i A')}");

        if ($this->incident->location_description) {
            $mail->line("- **Location:** {$this->incident->location_description}");
        }

        if ($this->incident->shift) {
            $mail->line("- **Related Shift:** {$this->incident->shift->title}");
        }

        $mail->line('')
            ->line('**Description:**')
            ->line(substr($this->incident->description, 0, 500).(strlen($this->incident->description) > 500 ? '...' : ''));

        if ($this->incident->getEvidenceCount() > 0) {
            $mail->line('')
                ->line("Evidence attached: {$this->incident->getEvidenceCount()} file(s)");
        }

        if ($this->incident->getWitnessCount() > 0) {
            $mail->line("Witnesses recorded: {$this->incident->getWitnessCount()}");
        }

        // Action button
        if ($this->isBusinessNotification) {
            $mail->action('View Incident Details', url('/business/incidents/'.$this->incident->id));
        } else {
            $mail->action('Review Incident', url('/admin/incidents/'.$this->incident->id));
        }

        $mail->line('')
            ->line($this->getActionRequiredText());

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_reported',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'incident_type' => $this->incident->type,
            'severity' => $this->incident->severity,
            'reporter_id' => $this->incident->reported_by,
            'reporter_name' => $this->incident->reporter->name ?? 'Unknown',
            'shift_id' => $this->incident->shift_id,
            'venue_id' => $this->incident->venue_id,
            'description_preview' => substr($this->incident->description, 0, 100),
            'is_business_notification' => $this->isBusinessNotification,
            'url' => $this->isBusinessNotification
                ? '/business/incidents/'.$this->incident->id
                : '/admin/incidents/'.$this->incident->id,
        ];
    }

    /**
     * Get severity emoji.
     */
    protected function getSeverityEmoji(): string
    {
        return match ($this->incident->severity) {
            Incident::SEVERITY_LOW => 'INFO',
            Incident::SEVERITY_MEDIUM => 'WARNING',
            Incident::SEVERITY_HIGH => 'ALERT',
            Incident::SEVERITY_CRITICAL => 'CRITICAL',
            default => 'NOTICE',
        };
    }

    /**
     * Get greeting text.
     */
    protected function getGreeting(): string
    {
        if ($this->incident->isCritical()) {
            return 'URGENT: Critical Incident Reported';
        }

        return 'New Incident Report';
    }

    /**
     * Get intro line.
     */
    protected function getIntroLine(): string
    {
        if ($this->isBusinessNotification) {
            return 'An incident has been reported related to your business operations.';
        }

        $urgency = $this->incident->isCritical() ? 'A critical incident requiring immediate attention' : 'A new incident';

        return "{$urgency} has been reported and requires your review.";
    }

    /**
     * Get action required text.
     */
    protected function getActionRequiredText(): string
    {
        if ($this->incident->isCritical()) {
            return 'This is a critical incident requiring immediate action. Please review and respond as soon as possible.';
        }

        if ($this->isBusinessNotification) {
            return 'Our team is reviewing this incident. You may be contacted for additional information.';
        }

        return 'Please review this incident and assign an investigator if appropriate.';
    }
}
