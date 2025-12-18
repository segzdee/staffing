<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-002: Incident Assigned Notification
 *
 * Sent to an admin when they are assigned to investigate an incident.
 */
class IncidentAssignedNotification extends Notification implements ShouldQueue
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
        $severityBadge = strtoupper($this->incident->severity);
        $typeLabel = $this->incident->getTypeLabel();

        $mail = (new MailMessage)
            ->subject("[{$severityBadge}] Incident Assigned to You - {$this->incident->incident_number}");

        if ($this->incident->isCritical()) {
            $mail->error();
        }

        $mail->greeting('Incident Assignment')
            ->line('You have been assigned to investigate the following incident:')
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

        $mail->line('')
            ->line('**Description:**')
            ->line(substr($this->incident->description, 0, 300).'...')
            ->action('Review & Investigate', url('/admin/incidents/'.$this->incident->id));

        if ($this->incident->isCritical()) {
            $mail->line('')
                ->line('This is a critical incident requiring immediate attention.');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_assigned',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'incident_type' => $this->incident->type,
            'severity' => $this->incident->severity,
            'reporter_name' => $this->incident->reporter->name ?? 'Unknown',
            'url' => '/admin/incidents/'.$this->incident->id,
        ];
    }
}
