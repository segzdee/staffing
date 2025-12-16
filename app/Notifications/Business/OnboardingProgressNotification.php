<?php

namespace App\Notifications\Business;

use App\Models\BusinessProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Onboarding Progress Notification
 * BIZ-REG-003: Celebrates profile completion milestones
 */
class OnboardingProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessProfile $businessProfile;
    protected float $completionPercentage;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessProfile $businessProfile, float $completionPercentage)
    {
        $this->businessProfile = $businessProfile;
        $this->completionPercentage = $completionPercentage;
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

        if ($this->completionPercentage >= 100) {
            return $this->getCompletionEmail($notifiable, $businessName);
        }

        return $this->getMilestoneEmail($notifiable, $businessName);
    }

    /**
     * Get email for 100% completion
     */
    protected function getCompletionEmail(object $notifiable, string $businessName): MailMessage
    {
        return (new MailMessage)
            ->subject("Congratulations! {$businessName} Profile Complete!")
            ->greeting("Great work, {$notifiable->name}!")
            ->line("Your {$businessName} profile is now 100% complete!")
            ->line("You're all set to start posting shifts and finding qualified workers.")
            ->line("**What's next?**")
            ->line("- Post your first shift")
            ->line("- Browse available workers")
            ->line("- Set up shift templates for recurring needs")
            ->action('Post Your First Shift', route('shifts.create'))
            ->line("Need help? Our support team is here for you.")
            ->salutation("Congratulations!\nThe OvertimeStaff Team");
    }

    /**
     * Get email for milestone (25%, 50%, 75%)
     */
    protected function getMilestoneEmail(object $notifiable, string $businessName): MailMessage
    {
        $remaining = 100 - $this->completionPercentage;
        $milestone = $this->getMilestoneMessage();

        return (new MailMessage)
            ->subject("{$this->completionPercentage}% Complete - {$milestone['title']}")
            ->greeting("Nice progress, {$notifiable->name}!")
            ->line("Your {$businessName} profile is now {$this->completionPercentage}% complete!")
            ->line($milestone['message'])
            ->line("Just {$remaining}% more to go before you can start posting shifts.")
            ->action('Continue Setup', route('business.profile.complete'))
            ->salutation("Keep it up!\nThe OvertimeStaff Team");
    }

    /**
     * Get milestone-specific message
     */
    protected function getMilestoneMessage(): array
    {
        if ($this->completionPercentage >= 75) {
            return [
                'title' => 'Almost There!',
                'message' => "You're so close to completing your profile! Just a few more details and you'll be ready to post shifts.",
            ];
        }

        if ($this->completionPercentage >= 50) {
            return [
                'title' => 'Halfway There!',
                'message' => "Great progress! You're halfway through setting up your business profile.",
            ];
        }

        return [
            'title' => 'Good Start!',
            'message' => "You've made a great start on your business profile. Keep going!",
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $isComplete = $this->completionPercentage >= 100;

        return [
            'type' => 'onboarding_progress',
            'title' => $isComplete
                ? 'Profile Complete!'
                : "Profile {$this->completionPercentage}% Complete",
            'message' => $isComplete
                ? 'Congratulations! Your profile is complete and you can now post shifts.'
                : "You're making progress! Keep going to complete your profile.",
            'business_profile_id' => $this->businessProfile->id,
            'completion_percentage' => $this->completionPercentage,
            'is_complete' => $isComplete,
            'action_url' => $isComplete
                ? route('shifts.create')
                : route('business.profile.complete'),
            'action_text' => $isComplete
                ? 'Post a Shift'
                : 'Continue Setup',
        ];
    }
}
