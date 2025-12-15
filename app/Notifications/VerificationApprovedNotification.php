<?php

namespace App\Notifications;

use App\Models\VerificationQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * VerificationApprovedNotification - ADM-001
 *
 * Sent to users when their verification request has been approved.
 */
class VerificationApprovedNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage)
            ->subject("Verification Approved: {$verificationType}")
            ->greeting("Great News, {$notifiable->name}!")
            ->line("Your {$verificationType} verification has been approved.")
            ->line("")
            ->line("You can now enjoy the full benefits of being a verified member on OvertimeStaff.");

        // Add specific benefits based on verification type
        switch ($this->verification->verification_type) {
            case 'identity':
                $mail->line("As a verified worker, you can:")
                     ->line("- Access premium shift opportunities")
                     ->line("- Receive priority matching")
                     ->line("- Build trust with businesses");
                break;

            case 'business_license':
                $mail->line("As a verified business, you can:")
                     ->line("- Post unlimited shifts")
                     ->line("- Access our verified worker pool")
                     ->line("- Display your verified badge");
                break;

            case 'agency':
                $mail->line("As a verified agency, you can:")
                     ->line("- Manage workers on the platform")
                     ->line("- Access enterprise features")
                     ->line("- Earn commission on placements");
                break;
        }

        $mail->action('View Dashboard', url('/dashboard'))
             ->line('Thank you for being part of the OvertimeStaff community!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'verification_approved',
            'verification_id' => $this->verification->id,
            'verification_type' => $this->verification->verification_type,
            'message' => 'Your ' . $this->getVerificationTypeLabel() . ' verification has been approved!',
            'url' => '/dashboard',
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
