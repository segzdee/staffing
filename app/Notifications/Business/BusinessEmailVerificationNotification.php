<?php

namespace App\Notifications\Business;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Business Email Verification Notification
 * BIZ-REG-002: Email verification for business accounts
 */
class BusinessEmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessProfile $businessProfile;
    protected string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile, string $token)
    {
        $this->businessProfile = $businessProfile;
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = route('business.verify-email.link', [
            'token' => $this->token,
            'email' => $this->businessProfile->work_email,
        ]);

        return (new MailMessage)
            ->subject('Verify Your Business Email - OvertimeStaff')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Please verify your email address to complete your business registration for {$this->businessProfile->business_name}.")
            ->line("Click the button below to verify your email:")
            ->action('Verify Email Address', $verificationUrl)
            ->line("This verification link will expire in 24 hours.")
            ->line("If you did not create a business account, no further action is required.")
            ->line("If you're having trouble clicking the button, copy and paste this URL into your browser:")
            ->line($verificationUrl)
            ->salutation("Best regards,\nThe OvertimeStaff Team");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'business_email_verification',
            'title' => 'Email Verification Required',
            'message' => 'Please verify your email address to complete your registration.',
            'business_profile_id' => $this->businessProfile->id,
        ];
    }
}
