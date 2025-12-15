<?php

namespace App\Notifications;

use App\Models\VerificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VerificationRejectedNotification - ADM-001
 *
 * Sent to users when their verification request has been rejected.
 */
class VerificationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected VerificationQueue $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(VerificationQueue $verification)
    {
        $this->verification = $verification;
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
        $verificationType = $this->getVerificationTypeLabel();
        $reason = $this->verification->admin_notes ?? 'Documents did not meet our verification requirements.';

        return (new MailMessage)
            ->subject("Verification Update: {$verificationType}")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've reviewed your {$verificationType} verification request and unfortunately, it could not be approved at this time.")
            ->line("")
            ->line("**Reason:**")
            ->line($reason)
            ->line("")
            ->line("**What you can do:**")
            ->line("1. Review the feedback provided above")
            ->line("2. Ensure your documents are clear and legible")
            ->line("3. Submit a new verification request with updated documents")
            ->line("")
            ->action('Submit New Request', url('/settings'))
            ->line('If you have questions about this decision, please contact our support team.')
            ->salutation('Best regards,<br>The OvertimeStaff Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_rejected',
            'verification_id' => $this->verification->id,
            'verification_type' => $this->verification->verification_type,
            'reason' => $this->verification->admin_notes,
            'message' => 'Your ' . $this->getVerificationTypeLabel() . ' verification was not approved. Please review and resubmit.',
            'url' => '/settings',
        ];
    }

    /**
     * Get human-readable verification type label
     */
    protected function getVerificationTypeLabel(): string
    {
        $labels = [
            'identity' => 'Identity',
            'background_check' => 'Background Check',
            'certification' => 'Certification',
            'business_license' => 'Business License',
            'agency' => 'Agency',
        ];

        return $labels[$this->verification->verification_type] ?? ucfirst($this->verification->verification_type);
    }
}
