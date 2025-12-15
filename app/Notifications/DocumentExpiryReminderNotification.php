<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to workers when their documents are expiring or have expired.
 * WKR-006: Document Expiry Management
 */
class DocumentExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $certification;
    protected $daysBeforeExpiry;
    protected $isExpired;

    /**
     * Create a new notification instance.
     *
     * @param WorkerCertification $certification
     * @param int $daysBeforeExpiry
     * @param bool $isExpired
     */
    public function __construct(WorkerCertification $certification, int $daysBeforeExpiry, bool $isExpired = false)
    {
        $this->certification = $certification;
        $this->daysBeforeExpiry = $daysBeforeExpiry;
        $this->isExpired = $isExpired;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $certificationName = $this->certification->certification->name ?? 'Your certification';

        if ($this->isExpired) {
            return $this->buildExpiredEmail($notifiable, $certificationName);
        }

        return $this->buildReminderEmail($notifiable, $certificationName);
    }

    /**
     * Build reminder email for upcoming expiry.
     *
     * @param mixed $notifiable
     * @param string $certificationName
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildReminderEmail($notifiable, string $certificationName)
    {
        $urgency = $this->getUrgencyLevel();
        $expiryDate = $this->certification->expiry_date->format('F j, Y');

        $message = (new MailMessage)
            ->subject("Document Expiring Soon: {$certificationName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a {$urgency} reminder that your {$certificationName} certification will expire in {$this->daysBeforeExpiry} days.")
            ->line("Expiry Date: {$expiryDate}")
            ->line("To maintain your eligibility for shifts requiring this certification, please renew it before the expiry date.");

        if ($this->daysBeforeExpiry <= 14) {
            $message->line("URGENT: This certification is expiring soon! Some shifts may become unavailable if not renewed.");
        }

        $message->action('Renew Certification', url('/worker/certifications/' . $this->certification->id . '/renew'))
            ->line('If you have already renewed this certification, please upload your new document.')
            ->line('Thank you for keeping your profile up to date!');

        return $message;
    }

    /**
     * Build email for expired certification.
     *
     * @param mixed $notifiable
     * @param string $certificationName
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildExpiredEmail($notifiable, string $certificationName)
    {
        $expiryDate = $this->certification->expiry_date->format('F j, Y');

        return (new MailMessage)
            ->error()
            ->subject("EXPIRED: {$certificationName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$certificationName} certification has expired as of {$expiryDate}.")
            ->line("IMPORTANT: This certification has been deactivated, and any associated skills have been temporarily disabled.")
            ->line("This affects your eligibility for shifts requiring this certification.")
            ->line("To restore your eligibility:")
            ->line("1. Renew your certification with the issuing authority")
            ->line("2. Upload the new certification document to your profile")
            ->line("3. Wait for admin verification (typically 24-48 hours)")
            ->action('Upload Renewed Certification', url('/worker/certifications/' . $this->certification->id . '/renew'))
            ->line('If you believe this is an error, please contact our support team immediately.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $certificationName = $this->certification->certification->name ?? 'Your certification';

        if ($this->isExpired) {
            return [
                'type' => 'document_expired',
                'title' => 'Certification Expired',
                'message' => "{$certificationName} has expired and has been deactivated.",
                'certification_id' => $this->certification->id,
                'certification_name' => $certificationName,
                'expiry_date' => $this->certification->expiry_date->toDateString(),
                'action_url' => url('/worker/certifications/' . $this->certification->id . '/renew'),
                'priority' => 'high',
            ];
        }

        return [
            'type' => 'document_expiring',
            'title' => 'Certification Expiring Soon',
            'message' => "{$certificationName} will expire in {$this->daysBeforeExpiry} days.",
            'certification_id' => $this->certification->id,
            'certification_name' => $certificationName,
            'days_until_expiry' => $this->daysBeforeExpiry,
            'expiry_date' => $this->certification->expiry_date->toDateString(),
            'action_url' => url('/worker/certifications/' . $this->certification->id . '/renew'),
            'priority' => $this->daysBeforeExpiry <= 7 ? 'high' : 'medium',
        ];
    }

    /**
     * Get urgency level text based on days before expiry.
     *
     * @return string
     */
    protected function getUrgencyLevel(): string
    {
        return match (true) {
            $this->daysBeforeExpiry <= 7 => 'URGENT',
            $this->daysBeforeExpiry <= 14 => 'important',
            $this->daysBeforeExpiry <= 30 => 'friendly',
            default => 'gentle',
        };
    }
}
