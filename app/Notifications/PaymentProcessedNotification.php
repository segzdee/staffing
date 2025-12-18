<?php

namespace App\Notifications;

use App\Models\PayrollItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * PaymentProcessedNotification - FIN-005: Payroll Processing System
 *
 * Sent to workers when an individual payment has been processed.
 */
class PaymentProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected PayrollItem $payrollItem
    ) {}

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
        $payrollRun = $this->payrollItem->payrollRun;
        $shift = $this->payrollItem->shift;

        $mail = (new MailMessage)
            ->subject('Payment Processed: $'.number_format($this->payrollItem->net_amount, 2))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your payment has been processed successfully!')
            ->line('**Amount:** $'.number_format($this->payrollItem->net_amount, 2))
            ->line('**Type:** '.$this->payrollItem->type_label)
            ->line('**Description:** '.$this->payrollItem->description);

        if ($shift) {
            $mail->line('**Shift:** '.$shift->title.' on '.$shift->shift_date->format('M d, Y'));
        }

        if ($this->payrollItem->payment_reference) {
            $mail->line('**Reference:** '.$this->payrollItem->payment_reference);
        }

        return $mail
            ->line('The funds should appear in your account within 1-3 business days.')
            ->action('View Paystub', route('worker.paystubs.show', $payrollRun))
            ->line('Thank you for being a valued member of our team!')
            ->salutation('Best regards,'."\n".config('app.name'));
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $shift = $this->payrollItem->shift;

        return [
            'type' => 'payment_processed',
            'payroll_item_id' => $this->payrollItem->id,
            'payroll_run_id' => $this->payrollItem->payroll_run_id,
            'payment_reference' => $this->payrollItem->payment_reference,
            'net_amount' => $this->payrollItem->net_amount,
            'item_type' => $this->payrollItem->type,
            'description' => $this->payrollItem->description,
            'shift_id' => $shift?->id,
            'shift_title' => $shift?->title,
            'message' => 'Payment of $'.number_format($this->payrollItem->net_amount, 2).' processed for '.$this->payrollItem->description,
            'action_url' => route('worker.paystubs.show', $this->payrollItem->payrollRun),
        ];
    }
}
