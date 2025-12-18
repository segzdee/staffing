<?php

namespace App\Notifications;

use App\Models\EmergencyAlert;
use App\Models\EmergencyContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-001: Emergency Contact Alert Notification
 *
 * Sent to emergency contacts when a user triggers an SOS alert.
 * Also sent when the situation is resolved.
 */
class EmergencyContactAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected EmergencyAlert $alert;

    protected EmergencyContact $contact;

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
     * @param  string  $type  Type: 'alert' or 'resolved'
     */
    public function __construct(EmergencyAlert $alert, EmergencyContact $contact, string $type = 'alert')
    {
        $this->alert = $alert;
        $this->contact = $contact;
        $this->notificationType = $type;

        // Use high-priority queue for emergency notifications
        $this->onQueue('high');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // SMS could be added here for emergency contacts
        // $channels[] = 'vonage';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        if ($this->notificationType === 'resolved') {
            return $this->buildResolvedEmail();
        }

        return $this->buildAlertEmail();
    }

    /**
     * Build email for new emergency alert.
     */
    protected function buildAlertEmail(): MailMessage
    {
        $userName = $this->alert->user->name;
        $typeLabel = $this->alert->type_label;

        $mail = (new MailMessage)
            ->subject("URGENT: Emergency Alert from {$userName}")
            ->greeting('Emergency Alert')
            ->error();

        $mail->line("You are receiving this notification because you are listed as an emergency contact for **{$userName}**.")
            ->line('')
            ->line("**{$userName} has triggered a {$typeLabel} alert and may need assistance.**")
            ->line('')
            ->line('**Alert Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Type:** {$typeLabel}")
            ->line("- **Time:** {$this->alert->created_at->format('M d, Y g:i A')} (UTC)");

        if ($this->alert->hasLocation()) {
            $googleMapsUrl = "https://www.google.com/maps?q={$this->alert->latitude},{$this->alert->longitude}";
            $mail->line('')
                ->line('**Last Known Location:**')
                ->line("[View on Google Maps]({$googleMapsUrl})");
        }

        if ($this->alert->location_address) {
            $mail->line("- **Address:** {$this->alert->location_address}");
        }

        if ($this->alert->message) {
            $mail->line('')
                ->line('**Message from user:**')
                ->line($this->alert->message);
        }

        // Add context about the shift/venue if available
        if ($this->alert->shift) {
            $mail->line('')
                ->line('**Work Information:**')
                ->line("- **Shift:** {$this->alert->shift->title}");

            if ($this->alert->venue) {
                $mail->line("- **Location:** {$this->alert->venue->name}")
                    ->line("- **Address:** {$this->alert->venue->full_address}");
            }
        }

        $mail->line('')
            ->line('**What you should do:**')
            ->line('1. Try to contact them directly by phone')
            ->line('2. If you cannot reach them and believe this is a real emergency, contact local emergency services')
            ->line('3. Our platform safety team has also been notified and is responding')
            ->line('')
            ->line('**Important:** If you believe this is a life-threatening emergency, please call your local emergency services immediately.');

        // Add user's phone if available
        $userPhone = $this->alert->user->workerProfile?->phone ?? null;
        if ($userPhone) {
            $mail->line('')
                ->line("**Contact {$userName}:**")
                ->line("Phone: {$userPhone}");
        }

        $mail->salutation('We will notify you when this alert is resolved.');

        return $mail;
    }

    /**
     * Build email for resolved alert.
     */
    protected function buildResolvedEmail(): MailMessage
    {
        $userName = $this->alert->user->name;

        return (new MailMessage)
            ->subject("Emergency Alert Resolved - {$userName}")
            ->greeting('Emergency Alert Resolved')
            ->success()
            ->line("The emergency alert for **{$userName}** has been resolved.")
            ->line('')
            ->line('**Resolution Details:**')
            ->line("- **Alert Number:** {$this->alert->alert_number}")
            ->line("- **Triggered at:** {$this->alert->created_at->format('M d, Y g:i A')} (UTC)")
            ->line("- **Resolved at:** {$this->alert->resolved_at->format('M d, Y g:i A')} (UTC)")
            ->line("- **Duration:** {$this->alert->duration_minutes} minutes")
            ->line('')
            ->line('No further action is required on your part.')
            ->line('')
            ->line("If you have any concerns about {$userName}'s wellbeing, we encourage you to reach out to them directly.")
            ->salutation('Thank you for being there when it matters.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'emergency_contact_alert',
            'notification_type' => $this->notificationType,
            'alert_id' => $this->alert->id,
            'alert_number' => $this->alert->alert_number,
            'alert_type' => $this->alert->type,
            'user_id' => $this->alert->user_id,
            'user_name' => $this->alert->user->name,
            'contact_id' => $this->contact->id,
            'contact_name' => $this->contact->name,
            'has_location' => $this->alert->hasLocation(),
        ];
    }
}
