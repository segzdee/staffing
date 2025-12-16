<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-008: Payment Setup Complete Notification
 *
 * Sent to worker when their Stripe Connect setup is complete.
 */
class PaymentSetupCompleteNotification extends Notification implements ShouldQueue
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
            ->subject('Payment Setup Complete!')
            ->greeting('Great news, ' . $notifiable->first_name . '!')
            ->line('Your payment account has been successfully set up.')
            ->line('You can now receive payouts for completed shifts directly to your bank account.')
            ->line('Payouts are processed on a daily basis by default. You can change your payout schedule in your payment settings.')
            ->action('View Payment Settings', url('/worker/payment'))
            ->line('Thank you for completing your setup!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_setup_complete',
            'message' => 'Your payment account has been successfully set up!',
            'action_url' => '/worker/payment',
        ];
    }
}
