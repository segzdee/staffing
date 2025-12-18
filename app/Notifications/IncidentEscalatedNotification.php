<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-002: Incident Escalated Notification
 *
 * Sent to senior admins when an incident is escalated.
 */
class IncidentEscalatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    /**
     * Create a new notification instance.
     */
    public function __construct(Incident $incident)
    {
        $this->incident = $incident;

        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = $this->incident->getTypeLabel();

        return (new MailMessage)
            ->subject("ESCALATED: Incident {$this->incident->incident_number} Requires Senior Review")
            ->error()
            ->greeting('Incident Escalation')
            ->line('An incident has been escalated and requires senior management review.')
            ->line('')
            ->line('**Incident Details:**')
            ->line("- **Number:** {$this->incident->incident_number}")
            ->line("- **Type:** {$typeLabel}")
            ->line('- **Severity:** '.ucfirst($this->incident->severity))
            ->line("- **Reported By:** {$this->incident->reporter->name}")
            ->line("- **Time:** {$this->incident->incident_time->format('M d, Y g:i A')}")
            ->line('')
            ->line('**Description:**')
            ->line(substr($this->incident->description, 0, 400).'...')
            ->line('')
            ->line($this->getEscalationReason())
            ->action('Review Escalated Incident', url('/admin/incidents/'.$this->incident->id))
            ->line('Please review this incident as soon as possible.');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_escalated',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'incident_type' => $this->incident->type,
            'severity' => $this->incident->severity,
            'reporter_name' => $this->incident->reporter->name ?? 'Unknown',
            'url' => '/admin/incidents/'.$this->incident->id,
        ];
    }

    /**
     * Get escalation reason text.
     */
    protected function getEscalationReason(): string
    {
        if ($this->incident->severity === Incident::SEVERITY_CRITICAL) {
            return 'This incident was automatically escalated due to its critical severity.';
        }

        return 'This incident was manually escalated by the assigned investigator.';
    }
}
