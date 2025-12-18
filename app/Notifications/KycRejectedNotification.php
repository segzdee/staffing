<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-001: KYC Rejected Notification
 *
 * Sent to worker when their KYC verification is rejected.
 */
class KycRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected KycVerification $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(KycVerification $verification)
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
        $reason = $this->verification->rejection_reason ?? 'Document quality or authenticity issues.';
        $canRetry = $this->verification->canRetry();
        $attemptsRemaining = $this->verification->max_attempts - $this->verification->attempt_count;

        $message = (new MailMessage)
            ->subject('KYC Verification Requires Attention')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Unfortunately, your identity verification could not be approved.')
            ->line('**Reason:** '.$reason);

        if ($canRetry) {
            $message->line("You have {$attemptsRemaining} attempt(s) remaining to submit new documents.")
                ->action('Submit New Documents', url('/worker/kyc/resubmit/'.$this->verification->id))
                ->line('Please ensure your documents are:')
                ->line('- Clear and not blurry')
                ->line('- Not expired')
                ->line('- Show all four corners')
                ->line('- Match the name on your account');
        } else {
            $message->line('You have exceeded the maximum number of verification attempts.')
                ->line('Please contact our support team for assistance.')
                ->action('Contact Support', url('/support'));
        }

        return $message->line('If you believe this was an error, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'kyc_rejected',
            'verification_id' => $this->verification->id,
            'reason' => $this->verification->rejection_reason,
            'can_retry' => $this->verification->canRetry(),
            'message' => 'Your KYC verification was not approved: '.($this->verification->rejection_reason ?? 'Document quality issues'),
            'action_url' => $this->verification->canRetry()
                ? '/worker/kyc/resubmit/'.$this->verification->id
                : '/support',
        ];
    }
}
