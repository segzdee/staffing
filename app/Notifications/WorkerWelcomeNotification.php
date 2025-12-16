<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Welcome notification sent to new workers after registration.
 */
class WorkerWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $user;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $firstName = $this->user->first_name ?? 'there';

        return (new MailMessage)
            ->subject('Welcome to OvertimeStaff!')
            ->greeting("Hi {$firstName}!")
            ->line('Welcome to OvertimeStaff, the global shift marketplace connecting workers with businesses.')
            ->line('You\'re one step closer to finding flexible work opportunities that match your skills and schedule.')
            ->line('**Here\'s what to do next:**')
            ->line('1. Verify your email address')
            ->line('2. Complete your profile')
            ->line('3. Add your skills and availability')
            ->line('4. Start applying to shifts!')
            ->action('Complete Your Profile', route('worker.onboarding'))
            ->line('If you have any questions, our support team is here to help.')
            ->salutation("Best regards,\nThe OvertimeStaff Team");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to OvertimeStaff!',
            'message' => 'Your account has been created successfully. Complete your profile to start finding shifts.',
            'action_url' => route('worker.onboarding'),
            'action_text' => 'Complete Profile',
            'icon' => 'user-plus',
        ];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
