<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-001: KYC Expiring Notification
 *
 * Sent to worker when their KYC verification is about to expire.
 */
class KycExpiringNotification extends Notification implements ShouldQueue
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
        $expiryDate = $this->getExpiryDate();
        $daysRemaining = $this->getDaysRemaining();
        $urgency = $daysRemaining <= 7 ? 'urgent' : 'important';

        $message = (new MailMessage)
            ->subject($urgency === 'urgent'
                ? 'Urgent: KYC Verification Expiring Soon'
                : 'Action Required: KYC Verification Expiring')
            ->greeting('Hello '.$notifiable->first_name.',');

        if ($daysRemaining <= 0) {
            $message->line('**Your KYC verification has expired.**')
                ->line('You will need to re-verify your identity to continue working on the platform.');
        } else {
            $message->line("Your KYC verification will expire in **{$daysRemaining} days** on {$expiryDate}.")
                ->line('To avoid any interruption to your ability to work, please renew your verification before it expires.');
        }

        return $message
            ->action('Renew Verification', url('/worker/kyc'))
            ->line('If your documents have changed or expired, you will need to submit new documents.')
            ->line('Thank you for keeping your account up to date.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $daysRemaining = $this->getDaysRemaining();

        return [
            'type' => 'kyc_expiring',
            'verification_id' => $this->verification->id,
            'expires_at' => $this->getExpiryDate(),
            'days_remaining' => $daysRemaining,
            'message' => $daysRemaining <= 0
                ? 'Your KYC verification has expired. Please renew to continue working.'
                : "Your KYC verification will expire in {$daysRemaining} days. Please renew soon.",
            'action_url' => '/worker/kyc',
        ];
    }

    /**
     * Get the expiry date.
     */
    protected function getExpiryDate(): string
    {
        $expiryDate = $this->verification->document_expiry ?? $this->verification->expires_at;

        return $expiryDate?->format('F j, Y') ?? 'Unknown';
    }

    /**
     * Get days remaining until expiry.
     */
    protected function getDaysRemaining(): int
    {
        $expiryDate = $this->verification->document_expiry ?? $this->verification->expires_at;

        if (! $expiryDate) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($expiryDate, false));
    }
}
