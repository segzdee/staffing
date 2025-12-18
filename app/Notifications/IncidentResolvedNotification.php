<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-002: Incident Resolved Notification
 *
 * Sent to the reporter when their incident is resolved.
 */
class IncidentResolvedNotification extends Notification implements ShouldQueue
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
            ->subject("Incident Resolved - {$this->incident->incident_number}")
            ->greeting('Your Incident Has Been Resolved')
            ->line('We wanted to let you know that your incident report has been resolved.')
            ->line('')
            ->line('**Incident Details:**')
            ->line("- **Number:** {$this->incident->incident_number}")
            ->line("- **Type:** {$typeLabel}")
            ->line("- **Reported On:** {$this->incident->created_at->format('M d, Y')}")
            ->line("- **Resolved On:** {$this->incident->resolved_at->format('M d, Y')}")
            ->line('')
            ->line('**Resolution:**')
            ->line($this->incident->resolution_notes ?? 'No additional notes provided.')
            ->line('')
            ->action('View Full Details', url('/worker/incidents/'.$this->incident->id))
            ->line('Thank you for bringing this matter to our attention. Your safety and well-being are our top priority.')
            ->line('')
            ->line('If you have any questions about the resolution or need further assistance, please do not hesitate to contact us.');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'incident_resolved',
            'incident_id' => $this->incident->id,
            'incident_number' => $this->incident->incident_number,
            'incident_type' => $this->incident->type,
            'resolution_notes' => substr($this->incident->resolution_notes ?? '', 0, 200),
            'resolved_at' => $this->incident->resolved_at?->toIso8601String(),
            'url' => '/worker/incidents/'.$this->incident->id,
        ];
    }
}
