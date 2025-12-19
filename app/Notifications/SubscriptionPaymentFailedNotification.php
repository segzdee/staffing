<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Subscription Payment Failed Notification
 * Sent when a subscription payment fails
 */
class SubscriptionPaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Subscription $subscription;

    /**
     * Create a new notification instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
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
        $planName = $this->subscription->plan?->name ?? 'your subscription';

        return (new MailMessage)
            ->subject('Payment Failed - Action Required')
            ->greeting('Payment Issue Detected')
            ->line("We were unable to process the payment for {$planName}.")
            ->line('**Please update your payment method to avoid service interruption.**')
            ->line('Your subscription features will remain active while we retry the payment.')
            ->line('If the payment continues to fail, your subscription may be suspended.')
            ->action('Update Payment Method', url('/settings/billing'))
            ->line('If you have any questions, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_payment_failed',
            'title' => 'Payment Failed',
            'message' => 'Your subscription payment failed. Please update your payment method.',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan?->name,
            'action_url' => url('/settings/billing'),
            'action_text' => 'Update Payment',
            'priority' => 'high',
        ];
    }
}
