<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-007: Certification Verified Notification
 *
 * Sent to worker when their certification is verified.
 */
class CertificationVerifiedNotification extends Notification implements ShouldQueue
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
        $expiryInfo = '';

        if ($this->certification->expiry_date) {
            $expiryInfo = "This certification is valid until {$this->certification->expiry_date->format('F j, Y')}.";
        }

        return (new MailMessage)
            ->subject('Certification Verified!')
            ->greeting('Great news, ' . $notifiable->first_name . '!')
            ->line("Your {$certName} certification has been verified.")
            ->line($expiryInfo)
            ->line('Your profile has been updated with this verification.')
            ->action('View Your Profile', url('/worker/profile'))
            ->line('You can now apply to shifts that require this certification.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'certification_verified',
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification_name,
            'expiry_date' => $this->certification->expiry_date?->toIso8601String(),
            'message' => "Your {$this->certification->certification_name} has been verified!",
            'action_url' => '/worker/profile',
        ];
    }
}
