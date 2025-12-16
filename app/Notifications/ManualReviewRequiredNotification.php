<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ManualReviewRequiredNotification - STAFF-REG-004
 *
 * Sent when identity verification requires manual review.
 */
class ManualReviewRequiredNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Identity Verification Under Review')
            ->greeting("Hello {$notifiable->first_name},")
            ->line('Your identity verification documents have been received and are currently under manual review.')
            ->line('')
            ->line('**What this means:**')
            ->line('Our verification team needs to take a closer look at your submitted documents. This is a routine process that helps ensure the security of all users on our platform.')
            ->line('')
            ->line('**What to expect:**')
            ->line('- Review typically takes 1-2 business days')
            ->line('- You\'ll receive an email once the review is complete')
            ->line('- No further action is required from you at this time')
            ->line('')
            ->line('**In the meantime:**')
            ->line('While you wait, you can continue browsing available shifts. However, you may have limited access to certain features until verification is complete.')
            ->action('View Verification Status', url('/worker/verification'))
            ->line('')
            ->line('Thank you for your patience. We\'ll notify you as soon as the review is complete.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'manual_review_required',
            'verification_id' => $this->verification->id,
            'message' => 'Your identity verification is under manual review. This typically takes 1-2 business days.',
            'url' => '/worker/verification',
        ];
    }
}
