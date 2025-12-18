<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * WKR-001: KYC Submitted Notification
 *
 * Sent to worker when they submit KYC documents for verification.
 */
class KycSubmittedNotification extends Notification implements ShouldQueue
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
        $documentType = $this->verification->document_type_name;

        return (new MailMessage)
            ->subject('KYC Verification Submitted')
            ->greeting('Hello '.$notifiable->first_name.'!')
            ->line("Your {$documentType} has been submitted for KYC verification.")
            ->line('Our team will review your documents and verify your identity within 1-3 business days.')
            ->line('You will receive an email notification once the review is complete.')
            ->action('View Status', url('/worker/kyc'))
            ->line('Thank you for completing your identity verification!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'kyc_submitted',
            'verification_id' => $this->verification->id,
            'document_type' => $this->verification->document_type,
            'message' => "Your {$this->verification->document_type_name} has been submitted for KYC verification.",
            'action_url' => '/worker/kyc',
        ];
    }
}
