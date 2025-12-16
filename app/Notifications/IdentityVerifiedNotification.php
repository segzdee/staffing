<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * IdentityVerifiedNotification - STAFF-REG-004
 *
 * Sent when identity verification is successful.
 */
class IdentityVerifiedNotification extends Notification implements ShouldQueue
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
        $expiryDate = $this->verification->expires_at?->format('F j, Y');

        $mail = (new MailMessage)
            ->subject('Identity Verified Successfully!')
            ->greeting("Congratulations, {$notifiable->first_name}!")
            ->line('Your identity has been successfully verified on OvertimeStaff.')
            ->line("Verification Level: **{$levelLabel}**");

        if ($expiryDate) {
            $mail->line("Valid Until: **{$expiryDate}**");
        }

        $mail->line('')
            ->line('**You now have access to:**');

        // Benefits based on verification level
        switch ($this->verification->verification_level) {
            case 'enhanced':
                $mail->line('- All premium shift opportunities')
                    ->line('- Highest trust badge on your profile')
                    ->line('- Priority matching with top-tier businesses')
                    ->line('- Express payout options');
                break;

            case 'standard':
                $mail->line('- Most shift opportunities')
                    ->line('- Verified badge on your profile')
                    ->line('- Priority matching with businesses')
                    ->line('- Standard payout options');
                break;

            case 'basic':
            default:
                $mail->line('- Basic shift opportunities')
                    ->line('- Verified badge on your profile')
                    ->line('- Access to business contacts');
                break;
        }

        $mail->action('View Your Profile', url('/worker/profile'))
            ->line('')
            ->line('Start applying for shifts and grow your career with OvertimeStaff!')
            ->line('Thank you for completing the verification process.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'identity_verified',
            'verification_id' => $this->verification->id,
            'verification_level' => $this->verification->verification_level,
            'expires_at' => $this->verification->expires_at?->toIso8601String(),
            'message' => 'Your identity has been verified! You now have access to more shift opportunities.',
            'url' => '/worker/profile',
        ];
    }
}
