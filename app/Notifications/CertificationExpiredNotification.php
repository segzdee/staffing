<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-007: Certification Expired Notification
 *
 * Sent to worker when their certification has expired.
 */
class CertificationExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkerCertification $certification;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkerCertification $certification)
    {
        $this->certification = $certification;
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
        $certName = $this->certification->certification_name;
        $expiryDate = $this->certification->expiry_date->format('F j, Y');

        return (new MailMessage)
            ->subject("Your {$certName} Certification Has Expired")
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->error()
            ->line("Your {$certName} certification expired on {$expiryDate}.")
            ->line('You will no longer be able to apply to or work shifts that require this certification until you upload a renewed certificate.')
            ->line('If your certification depends on related skills, those skills may also be temporarily deactivated.')
            ->action('Upload Renewed Certificate', url('/worker/certifications/' . $this->certification->id . '/renew'))
            ->line('If you have already renewed, please upload your new certificate as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'certification_expired',
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification_name,
            'expiry_date' => $this->certification->expiry_date->toIso8601String(),
            'message' => "Your {$this->certification->certification_name} certification has expired.",
            'action_url' => '/worker/certifications/' . $this->certification->id . '/renew',
        ];
    }
}
