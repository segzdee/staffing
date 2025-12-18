<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-002: Incident Status Changed Notification
 *
 * Sent to the reporter when their incident's status changes.
 */
class IncidentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Incident $incident;

    protected string $oldStatus;

    protected string $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(Incident $incident, string $oldStatus, string $newStatus)
    {
        $this->incident = $incident;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;

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
        $newStatusLabel = Incident::STATUS_LABELS[$this->newStatus] ?? ucfirst($this->newStatus);
        $oldStatusLabel = Incident::STATUS_LABELS[$this->oldStatus] ?? ucfirst($this->oldStatus);

        return (new MailMessage)
            ->subject("Incident Update - {$this->incident->incident_number}")
            ->greeting('Incident Status Update')
            ->line('The status of your incident report has been updated.')
            ->line('')
            ->line("**Incident:** {$this->incident->incident_number}")
            ->line("**Previous Status:** {$oldStatusLabel}")
            ->line("**New Status:** {$newStatusLabel}")
            ->line('')
            ->line($this->getStatusMessage())
            ->action('View Incident Details', url('/worker/incidents/'.$this->incident->id))
            ->line('Thank you for your patience as we work to address this matter.');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_status_changed',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'url' => '/worker/incidents/'.$this->incident->id,
        ];
    }

    /**
     * Get contextual message based on new status.
     */
    protected function getStatusMessage(): string
    {
        return match ($this->newStatus) {
            Incident::STATUS_INVESTIGATING => 'An investigator has been assigned and your report is being actively reviewed.',
            Incident::STATUS_ESCALATED => 'Your incident has been escalated to senior management for further review.',
            Incident::STATUS_RESOLVED => 'Your incident has been resolved. Please review the resolution notes.',
            Incident::STATUS_CLOSED => 'Your incident report has been closed. Thank you for bringing this to our attention.',
            default => 'Your incident report is being processed.',
        };
    }
}
