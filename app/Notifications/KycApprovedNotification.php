<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-001: KYC Approved Notification
 *
 * Sent to worker when their KYC verification is approved.
 */
class KycApprovedNotification extends Notification implements ShouldQueue
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
        $kycLevel = config('kyc.levels.'.$notifiable->kyc_level.'.label', 'Verified');
        $expiresAt = $this->verification->expires_at?->format('F j, Y');

        $message = (new MailMessage)
            ->subject('KYC Verification Approved')
            ->greeting('Congratulations '.$notifiable->first_name.'!')
            ->line('Your identity verification has been approved.')
            ->line("Your verification level: **{$kycLevel}**");

        if ($expiresAt) {
            $message->line("This verification is valid until {$expiresAt}.");
        }

        return $message
            ->line('You can now apply for shifts and start earning.')
            ->action('Browse Available Shifts', url('/worker/shifts'))
            ->line('Thank you for choosing OvertimeStaff!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'kyc_approved',
            'verification_id' => $this->verification->id,
            'kyc_level' => $notifiable->kyc_level,
            'message' => 'Your KYC verification has been approved. You can now apply for shifts.',
            'action_url' => '/worker/shifts',
        ];
    }
}
