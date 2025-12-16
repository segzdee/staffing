<?php

namespace App\Notifications;

use App\Models\OnboardingStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to remind workers about incomplete onboarding steps.
 * STAFF-REG-010: Onboarding Progress Tracking
 */
class OnboardingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OnboardingStep $step;

    /**
     * Create a new notification instance.
     */
    public function __construct(OnboardingStep $step)
    {
        $this->step = $step;
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
        return (new MailMessage)
            ->subject('Complete Your Profile to Start Finding Shifts')
            ->greeting("Hi {$notifiable->first_name}!")
            ->line("You're almost there! Complete your profile to start receiving shift opportunities.")
            ->line("Your next step: **{$this->step->name}**")
            ->line($this->step->description ?? 'Complete this step to unlock more features.')
            ->action('Complete Now', $this->step->getRouteUrl() ?? url('/worker/onboarding/dashboard'))
            ->line('Workers with complete profiles get 3x more shift invitations!')
            ->salutation('Best regards, The OvertimeStaff Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'onboarding_reminder',
            'step_id' => $this->step->step_id,
            'step_name' => $this->step->name,
            'message' => "Don't forget to complete: {$this->step->name}",
            'action_url' => $this->step->getRouteUrl(),
        ];
    }
}
