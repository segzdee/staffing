<?php

namespace App\Notifications;

use App\Models\OnboardingStep;
use App\Models\OnboardingProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a worker completes an onboarding step.
 * STAFF-REG-010: Onboarding Progress Tracking
 */
class OnboardingStepCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected OnboardingStep $step;
    protected OnboardingProgress $progress;

    /**
     * Create a new notification instance.
     */
    public function __construct(OnboardingStep $step, OnboardingProgress $progress)
    {
        $this->step = $step;
        $this->progress = $progress;
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
            ->subject("Step Completed: {$this->step->name}")
            ->greeting("Great progress, {$notifiable->first_name}!")
            ->line("You've completed the '{$this->step->name}' step of your onboarding.")
            ->line("Keep going! Each step you complete brings you closer to finding great shifts.")
            ->action('Continue Onboarding', url('/worker/onboarding/dashboard'))
            ->line('Thank you for joining OvertimeStaff!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'onboarding_step_completed',
            'step_id' => $this->step->step_id,
            'step_name' => $this->step->name,
            'completed_at' => $this->progress->completed_at?->toIso8601String(),
            'message' => "You've completed: {$this->step->name}",
        ];
    }
}
