<?php

namespace App\Notifications;

use App\Models\BusinessPaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PaymentMethodAddedNotification
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Sent when a business adds a new payment method.
 */
class PaymentMethodAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BusinessPaymentMethod $paymentMethod;

    /**
     * Create a new notification instance.
     */
    public function __construct(BusinessPaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
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
        $displayName = $this->paymentMethod->display_name;
        $needsVerification = $this->paymentMethod->requiresAction();

        $mail = (new MailMessage)
            ->subject('Payment Method Added - OvertimeStaff')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("A new payment method has been added to your account: **{$displayName}**");

        if ($needsVerification) {
            $mail->line('This payment method requires verification before it can be used.')
                 ->action('Verify Payment Method', route('business.payment.setup'))
                 ->line('Once verified, you\'ll be able to post shifts and pay workers.');
        } else {
            $mail->line('Your payment method has been verified and is ready to use.')
                 ->action('View Payment Methods', route('business.payment.setup'))
                 ->line('You can now post shifts and pay workers through OvertimeStaff.');
        }

        return $mail->line('If you did not add this payment method, please contact support immediately.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_method_added',
            'payment_method_id' => $this->paymentMethod->id,
            'payment_method_type' => $this->paymentMethod->type,
            'display_name' => $this->paymentMethod->display_name,
            'needs_verification' => $this->paymentMethod->requiresAction(),
            'message' => "Payment method added: {$this->paymentMethod->display_name}",
            'action_url' => route('business.payment.setup'),
        ];
    }
}
