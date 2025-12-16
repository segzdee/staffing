<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification with guidance for completing first shift.
 * STAFF-REG-010: Worker Activation Flow
 */
class FirstShiftGuidanceNotification extends Notification implements ShouldQueue
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
            ->subject('Your First Shift Guide')
            ->greeting("Hi {$notifiable->first_name}!")
            ->line('Ready for your first shift? Here\'s everything you need to know:')
            ->line('')
            ->line('**Before the Shift:**')
            ->line('- Confirm you can make it at least 24 hours in advance')
            ->line('- Review the shift details and dress code')
            ->line('- Plan your route and arrive 10 minutes early')
            ->line('')
            ->line('**During the Shift:**')
            ->line('- Check in using the app when you arrive (GPS required)')
            ->line('- Complete all assigned tasks professionally')
            ->line('- Communicate with the supervisor if you have questions')
            ->line('- Check out when your shift ends')
            ->line('')
            ->line('**After the Shift:**')
            ->line('- Rate your experience with the business')
            ->line('- Payment will be processed within 24-48 hours')
            ->line('- Check for more shift opportunities!')
            ->line('')
            ->line('**Important Tips:**')
            ->line('- Cancellations within 24 hours affect your reliability score')
            ->line('- Arriving on time and completing shifts boosts your rating')
            ->line('- Higher ratings = more shift invitations!')
            ->action('Find Your First Shift', url('/worker/market'))
            ->line('Good luck with your first shift!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'first_shift_guidance',
            'message' => 'Check out our guide for completing your first shift successfully!',
            'action_url' => url('/worker/activation/welcome'),
        ];
    }
}
