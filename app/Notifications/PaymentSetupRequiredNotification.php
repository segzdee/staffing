<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * STAFF-REG-008: Payment Setup Required Notification
 *
 * Sent to worker when they need to set up or reconnect their payment account.
 */
class PaymentSetupRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ?string $reason;

    /**
     * Create a new notification instance.
     *
     * @param string|null $reason Optional reason for requiring setup
     */
    public function __construct(?string $reason = null)
    {
        $this->reason = $reason;
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
        $message = (new MailMessage)
            ->subject('Payment Setup Required')
            ->greeting('Hello ' . $notifiable->first_name . ',');

        if ($this->reason === 'disconnected') {
            $message->line('Your payment account has been disconnected.')
                ->line('To continue receiving payouts for your shifts, please reconnect your payment account.');
        } elseif ($this->reason === 'incomplete') {
            $message->line('Your payment account setup is incomplete.')
                ->line('Please complete the remaining steps to enable payouts for your shifts.');
        } else {
            $message->line('To receive payments for completed shifts, you need to set up your payment account.')
                ->line('This is a quick process that connects your bank account to receive direct deposits.');
        }

        return $message
            ->action('Set Up Payment', url('/worker/payment/setup'))
            ->line('If you have any questions, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        $message = match ($this->reason) {
            'disconnected' => 'Your payment account has been disconnected. Please reconnect to receive payouts.',
            'incomplete' => 'Please complete your payment setup to receive payouts.',
            default => 'Set up your payment account to receive payouts for completed shifts.',
        };

        return [
            'type' => 'payment_setup_required',
            'reason' => $this->reason,
            'message' => $message,
            'action_url' => '/worker/payment/setup',
        ];
    }
}
