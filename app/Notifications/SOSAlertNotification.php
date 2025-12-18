<?php

namespace App\Notifications;

use App\Models\EmergencyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-001: SOS Alert Notification
 *
 * Sent to admins, safety team, business, and the user who triggered the alert.
 * Different content based on notification type (new, acknowledged, resolved, false_alarm, business).
 */
class SOSAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected EmergencyAlert $alert;

    protected string $notificationType;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Create a new notification instance.
     *
     * @param  string  $type  Type: 'new', 'acknowledged', 'resolved', 'false_alarm', 'business'
     */
    public function __construct(EmergencyAlert $alert, string $type = 'new')
    {
        $this->alert = $alert;
        $this->notificationType = $type;

        // Use high-priority queue for emergency notifications
        $this->onQueue('high');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // New alerts to admins could also use SMS/push for urgency
        // if ($this->notificationType === 'new' && $this->alert->isHighPriority()) {
        //     $channels[] = 'vonage';
        // }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->notificationType) {
            'new' => $this->buildNewAlertEmail($notifiable),
            'acknowledged' => $this->buildAcknowledgedEmail($notifiable),
            'resolved' => $this->buildResolvedEmail($notifiable),
            'false_alarm' => $this->buildFalseAlarmEmail($notifiable),
            'business' => $this->buildBusinessEmail($notifiable),
            default => $this->buildNewAlertEmail($notifiable),
        };
    }

    /**
     * Build email for new emergency alert (admin/safety team).
     */
    protected function buildNewAlertEmail(object $notifiable): MailMessage
    {
        $priorityLabel = $this->alert->isHighPriority() ? 'URGENT: ' : '';
        $typeLabel = $this->alert->type_label;

        $mail = (new MailMessage)
            ->subject("{$priorityLabel}Emergency SOS Alert - {$this->alert->alert_number}")
            ->greeting('Emergency Alert Received');

        if ($this->alert->isHighPriority()) {
            $mail->error();
        }

        $mail->line("A user has triggered an emergency {$typeLabel} alert and requires immediate assistance.")
            ->line('')
            ->line('**Alert Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Type:** {$typeLabel}")
            ->line("- **User:** {$this->alert->user->name}")
            ->line("- **Time:** {$this->alert->created_at->format('M d, Y g:i A')}");

        if ($this->alert->hasLocation()) {
            $googleMapsUrl = "https://www.google.com/maps?q={$this->alert->latitude},{$this->alert->longitude}";
            $mail->line("- **Location:** [{$this->alert->latitude}, {$this->alert->longitude}]({$googleMapsUrl})");
        }

        if ($this->alert->location_address) {
            $mail->line("- **Address:** {$this->alert->location_address}");
        }

        if ($this->alert->shift) {
            $mail->line("- **Shift:** {$this->alert->shift->title}");
        }

        if ($this->alert->venue) {
            $mail->line("- **Venue:** {$this->alert->venue->name}");
        }

        if ($this->alert->message) {
            $mail->line('')
                ->line('**Message from user:**')
                ->line($this->alert->message);
        }

        $mail->action('Respond to Alert', url('/admin/emergency-alerts/'.$this->alert->id))
            ->line('')
            ->line('Please respond to this alert immediately.');

        return $mail;
    }

    /**
     * Build email for acknowledged alert (user who triggered).
     */
    protected function buildAcknowledgedEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Help is on the way - {$this->alert->alert_number}")
            ->greeting('Your Emergency Alert Has Been Acknowledged')
            ->line('Someone from our safety team has acknowledged your emergency alert and is responding.')
            ->line('')
            ->line('**Alert Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Acknowledged at:** {$this->alert->acknowledged_at->format('M d, Y g:i A')}")
            ->line('')
            ->line('Stay where you are if safe to do so. Help is on the way.')
            ->line('')
            ->line('If this was triggered by mistake, you can cancel it through the app.')
            ->salutation('Stay safe.');
    }

    /**
     * Build email for resolved alert (user who triggered).
     */
    protected function buildResolvedEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Emergency Alert Resolved - {$this->alert->alert_number}")
            ->greeting('Your Emergency Alert Has Been Resolved')
            ->success()
            ->line('We are glad to inform you that your emergency alert has been resolved.')
            ->line('')
            ->line('**Resolution Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Resolved at:** {$this->alert->resolved_at->format('M d, Y g:i A')}")
            ->line("- **Duration:** {$this->alert->duration_minutes} minutes")
            ->line('')
            ->line('If you have any concerns or need further assistance, please contact our support team.')
            ->salutation('Stay safe.');
    }

    /**
     * Build email for false alarm (user who triggered).
     */
    protected function buildFalseAlarmEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Emergency Alert Closed - {$this->alert->alert_number}")
            ->greeting('Your Emergency Alert Has Been Closed')
            ->line('Your emergency alert has been marked as resolved.')
            ->line('')
            ->line('**Alert Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Closed at:** {$this->alert->resolved_at->format('M d, Y g:i A')}")
            ->line('')
            ->line('If you still need assistance, please trigger a new emergency alert or contact our support team.')
            ->salutation('Stay safe.');
    }

    /**
     * Build email for business (when alert is at their venue/shift).
     */
    protected function buildBusinessEmail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Emergency Alert at Your Location - {$this->alert->alert_number}")
            ->greeting('Emergency Alert at Your Business Location');

        if ($this->alert->isHighPriority()) {
            $mail->error();
        }

        $mail->line('An emergency alert has been triggered at one of your business locations.')
            ->line('')
            ->line('**Alert Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Type:** {$this->alert->type_label}")
            ->line("- **Time:** {$this->alert->created_at->format('M d, Y g:i A')}");

        if ($this->alert->venue) {
            $mail->line("- **Venue:** {$this->alert->venue->name}");
        }

        if ($this->alert->shift) {
            $mail->line("- **Shift:** {$this->alert->shift->title}");
        }

        $mail->line('')
            ->line('Our safety team has been notified and is responding to this alert.')
            ->line('')
            ->line('You may be contacted for additional information or to assist with the response.')
            ->action('View Alert Details', url('/business/emergency-alerts/'.$this->alert->id));

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sos_alert',
            'notification_type' => $this->notificationType,
            'alert_id' => $this->alert->id,
            'alert_number' => $this->alert->alert_number,
            'alert_type' => $this->alert->type,
            'alert_status' => $this->alert->status,
            'user_id' => $this->alert->user_id,
            'user_name' => $this->alert->user->name ?? 'Unknown',
            'shift_id' => $this->alert->shift_id,
            'venue_id' => $this->alert->venue_id,
            'is_high_priority' => $this->alert->isHighPriority(),
            'has_location' => $this->alert->hasLocation(),
            'latitude' => $this->alert->latitude,
            'longitude' => $this->alert->longitude,
            'message' => $this->alert->message,
            'url' => $this->getNotificationUrl(),
            'created_at' => $this->alert->created_at->toISOString(),
        ];
    }

    /**
     * Get the appropriate URL based on notification type.
     */
    protected function getNotificationUrl(): string
    {
        return match ($this->notificationType) {
            'business' => '/business/emergency-alerts/'.$this->alert->id,
            'acknowledged', 'resolved', 'false_alarm' => '/worker/emergency-alerts/'.$this->alert->id,
            default => '/admin/emergency-alerts/'.$this->alert->id,
        };
    }
}
