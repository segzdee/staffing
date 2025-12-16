<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome notification sent when a worker joins the marketplace.
 * STAFF-REG-010: Worker Activation Flow
 */
class WelcomeToMarketplaceNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Welcome to OvertimeStaff Marketplace!')
            ->greeting("Welcome, {$notifiable->first_name}!")
            ->line('You now have full access to the OvertimeStaff marketplace. Here\'s what you can do:')
            ->line('')
            ->line('**Find Shifts**')
            ->line('Browse hundreds of shifts from top businesses in your area.')
            ->line('')
            ->line('**Apply Instantly**')
            ->line('Apply to shifts with one click and get notified when you\'re selected.')
            ->line('')
            ->line('**Get Paid Fast**')
            ->line('Receive your earnings quickly through our secure payment system.')
            ->line('')
            ->line('**Build Your Reputation**')
            ->line('Complete shifts successfully to improve your rating and unlock better opportunities.')
            ->action('Explore the Marketplace', url('/worker/market'))
            ->line('We\'re excited to have you on board!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome_to_marketplace',
            'message' => 'Welcome to OvertimeStaff! Start exploring available shifts.',
            'action_url' => url('/worker/market'),
        ];
    }
}
