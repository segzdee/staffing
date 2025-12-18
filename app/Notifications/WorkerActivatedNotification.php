<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a worker's account is activated.
 * STAFF-REG-010: Worker Activation Flow
 */
class WorkerActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
        $profile = $notifiable->workerProfile;
        $tier = ucfirst($profile?->subscription_tier ?? 'bronze');
        $reliabilityScore = $profile?->reliability_score ?? 80;

        return (new MailMessage)
            ->subject('Your Account is Now Active!')
            ->greeting("Congratulations, {$notifiable->first_name}!")
            ->line('Your OvertimeStaff account has been activated. You can now start applying for shifts!')
            ->line("**Your Starting Tier:** {$tier}")
            ->line("**Reliability Score:** {$reliabilityScore}%")
            ->line('Here are some tips to get started:')
            ->line('- Browse available shifts in your area')
            ->line('- Set up your availability schedule')
            ->line('- Keep your profile updated for better matches')
            ->action('Find Shifts Now', url('/shifts'))
            ->line('Welcome to the OvertimeStaff community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'worker_activated',
            'message' => 'Your account is now active! Start finding shifts.',
            'action_url' => url('/shifts'),
        ];
    }
}
