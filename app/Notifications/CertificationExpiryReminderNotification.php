<?php

namespace App\Notifications;

use App\Models\WorkerCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-007: Certification Expiry Reminder Notification
 *
 * Sent to worker when their certification is expiring soon.
 * Reminders are sent at 60, 30, 14, and 7 days before expiry.
 */
class CertificationExpiryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected WorkerCertification $certification;
    protected int $daysUntilExpiry;

    /**
     * Create a new notification instance.
     */
    public function __construct(WorkerCertification $certification, int $daysUntilExpiry)
    {
        $this->certification = $certification;
        $this->daysUntilExpiry = $daysUntilExpiry;
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

        $urgency = match (true) {
            $this->daysUntilExpiry <= 7 => 'Urgent: ',
            $this->daysUntilExpiry <= 14 => 'Important: ',
            default => '',
        };

        $subject = "{$urgency}Your {$certName} expires in {$this->daysUntilExpiry} days";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->first_name . ',');

        if ($this->daysUntilExpiry <= 7) {
            $message->error()
                ->line("Your {$certName} certification expires on {$expiryDate} - that's only {$this->daysUntilExpiry} days away!")
                ->line('Please renew your certification as soon as possible to continue working shifts that require this certification.');
        } elseif ($this->daysUntilExpiry <= 14) {
            $message->line("Your {$certName} certification will expire on {$expiryDate} ({$this->daysUntilExpiry} days).")
                ->line('Please start the renewal process soon to avoid any gaps in your eligibility.');
        } else {
            $message->line("This is a friendly reminder that your {$certName} certification will expire on {$expiryDate}.")
                ->line('You have {$this->daysUntilExpiry} days to renew.');
        }

        $certType = $this->certification->certificationType;
        if ($certType && $certType->renewal_instructions) {
            $message->line('Renewal Instructions:')
                ->line($certType->renewal_instructions);
        }

        return $message
            ->action('Renew Now', url('/worker/certifications/' . $this->certification->id . '/renew'))
            ->line('If you have already renewed, please upload your new certificate.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $urgency = match (true) {
            $this->daysUntilExpiry <= 7 => 'critical',
            $this->daysUntilExpiry <= 14 => 'urgent',
            $this->daysUntilExpiry <= 30 => 'warning',
            default => 'notice',
        };

        return [
            'type' => 'certification_expiry_reminder',
            'certification_id' => $this->certification->id,
            'certification_name' => $this->certification->certification_name,
            'expiry_date' => $this->certification->expiry_date->toIso8601String(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'urgency' => $urgency,
            'message' => "Your {$this->certification->certification_name} expires in {$this->daysUntilExpiry} days.",
            'action_url' => '/worker/certifications/' . $this->certification->id . '/renew',
        ];
    }
}
