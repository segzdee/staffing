<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SAF-003: Certification Rejected Notification
 *
 * Sent to worker when their certification is rejected with reason.
 */
class CertificationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkerCertification $certification;

    protected string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkerCertification $certification, string $reason)
    {
        $this->certification = $certification;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $certName = $this->certification->certification_name;

        return (new MailMessage)
            ->subject('Certification Review Update')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line("Unfortunately, we were unable to verify your {$certName} certification.")
            ->line('**Reason for rejection:**')
            ->line($this->reason)
            ->line('**What you can do:**')
            ->line('1. Ensure your certification document is clear and readable')
            ->line('2. Verify that all information matches your submitted details')
            ->line('3. Check that the certification is still valid and not expired')
            ->action('Re-submit Certification', url('/worker/certifications'))
            ->line('If you believe this was an error, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'certification_rejected',
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification_name,
            'reason' => $this->reason,
            'message' => "Your {$this->certification->certification_name} certification was not verified.",
            'action_url' => '/worker/certifications',
        ];
    }
}
