<?php

namespace App\Notifications\Business;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Business Welcome Notification
 * BIZ-REG-002: Sent after business account creation
 */
class BusinessWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessProfile $businessProfile;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile)
    {
        $this->businessProfile = $businessProfile;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $this->businessProfile->business_name;

        return (new MailMessage)
            ->subject("Welcome to OvertimeStaff, {$businessName}!")
            ->greeting("Welcome aboard!")
            ->line("Thank you for registering {$businessName} with OvertimeStaff.")
            ->line("We're excited to help you find qualified workers for your shifts.")
            ->line("**Here's what you can do next:**")
            ->line("1. Verify your email address")
            ->line("2. Complete your business profile")
            ->line("3. Set up your payment method")
            ->line("4. Start posting shifts!")
            ->action('Complete Your Profile', route('business.profile.complete'))
            ->line("If you have any questions, our support team is here to help.")
            ->salutation("Welcome to the team,\nThe OvertimeStaff Team");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'business_welcome',
            'title' => 'Welcome to OvertimeStaff!',
            'message' => "Thank you for registering {$this->businessProfile->business_name}. Complete your profile to start posting shifts.",
            'business_profile_id' => $this->businessProfile->id,
            'action_url' => route('business.profile.complete'),
            'action_text' => 'Complete Profile',
        ];
    }
}
