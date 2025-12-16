<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-007: Certification Submitted Notification
 *
 * Sent to worker when they submit a certification for review.
 */
class CertificationSubmittedNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject('Certification Submitted for Review')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line("Your {$certName} certification has been submitted for review.")
            ->line('Our team will verify your certification and update its status within 1-3 business days.')
            ->line('You will receive an email once the verification is complete.')
            ->action('View Certifications', url('/worker/certifications'))
            ->line('Thank you for keeping your profile up to date!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'certification_submitted',
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification_name,
            'message' => "Your {$this->certification->certification_name} certification has been submitted for review.",
            'action_url' => '/worker/certifications',
        ];
    }
}
