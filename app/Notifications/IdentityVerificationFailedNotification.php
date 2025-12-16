<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * IdentityVerificationFailedNotification - STAFF-REG-004
 *
 * Sent when identity verification fails.
 */
class IdentityVerificationFailedNotification extends Notification implements ShouldQueue
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
        $reason = $this->verification->rejection_reason ?? 'The submitted documents could not be verified.';
        $canRetry = $this->verification->canRetry();
        $attemptsRemaining = $this->verification->max_attempts - $this->verification->attempt_count;

        $mail = (new MailMessage)
            ->subject('Identity Verification Unsuccessful')
            ->greeting("Hello {$notifiable->first_name},")
            ->line('Unfortunately, we were unable to verify your identity.')
            ->line('')
            ->line("**Reason:** {$reason}");

        if ($canRetry) {
            $mail->line('')
                ->line("You have **{$attemptsRemaining}** attempt(s) remaining.")
                ->line('')
                ->line('**Common reasons for verification failure:**')
                ->line('- Blurry or unclear document images')
                ->line('- Expired identification documents')
                ->line('- Information on the document doesn\'t match your profile')
                ->line('- Poor lighting during selfie capture')
                ->line('- Face not clearly visible or partially obscured')
                ->line('')
                ->line('**Tips for successful verification:**')
                ->line('- Use a valid, non-expired government ID')
                ->line('- Ensure all text on your ID is clearly readable')
                ->line('- Take photos in a well-lit area')
                ->line('- Remove glasses, hats, or anything covering your face')
                ->line('- Hold your ID flat and avoid glare')
                ->action('Try Again', url('/worker/verification'));
        } else {
            $mail->line('')
                ->line('You have reached the maximum number of verification attempts.')
                ->line('')
                ->line('Please contact our support team for assistance.')
                ->action('Contact Support', url('/support'));
        }

        $mail->line('')
            ->line('If you believe this is an error, please don\'t hesitate to reach out to our support team.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'identity_verification_failed',
            'verification_id' => $this->verification->id,
            'rejection_reason' => $this->verification->rejection_reason,
            'can_retry' => $this->verification->canRetry(),
            'attempts_remaining' => $this->verification->max_attempts - $this->verification->attempt_count,
            'message' => 'Your identity verification was unsuccessful. ' .
                ($this->verification->canRetry() ? 'You can try again.' : 'Please contact support.'),
            'url' => $this->verification->canRetry() ? '/worker/verification' : '/support',
        ];
    }
}
