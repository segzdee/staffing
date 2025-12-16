<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * IdentityVerificationInitiatedNotification - STAFF-REG-004
 *
 * Sent when a user initiates identity verification.
 */
class IdentityVerificationInitiatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected IdentityVerification $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(IdentityVerification $verification)
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
        $levelLabel = ucfirst($this->verification->verification_level);

        return (new MailMessage)
            ->subject('Identity Verification Started')
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("You've started the identity verification process on OvertimeStaff.")
            ->line("Verification Level: {$levelLabel}")
            ->line('')
            ->line('**What to expect:**')
            ->line('1. You\'ll be asked to upload a valid government-issued ID')
            ->line('2. You may need to take a selfie for face matching')
            ->line('3. Verification typically takes a few minutes')
            ->line('')
            ->line('**Tips for successful verification:**')
            ->line('- Use a well-lit environment')
            ->line('- Ensure your ID is not expired')
            ->line('- Make sure all text on your ID is clearly visible')
            ->line('- Remove glasses or hats for the selfie')
            ->action('Continue Verification', url('/worker/verification'))
            ->line('If you didn\'t initiate this verification, please contact our support team immediately.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'identity_verification_initiated',
            'verification_id' => $this->verification->id,
            'verification_level' => $this->verification->verification_level,
            'message' => 'You\'ve started identity verification. Complete the process to unlock all features.',
            'url' => '/worker/verification',
        ];
    }
}
