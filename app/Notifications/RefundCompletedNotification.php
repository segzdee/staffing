<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * RefundCompletedNotification
 *
 * Sent to businesses when a refund has been completed.
 */
class RefundCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Refund $refund
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
        $mail = (new MailMessage)
            ->subject('Refund Processed: $'.number_format($this->refund->refund_amount, 2))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your refund has been processed successfully!')
            ->line('**Refund Number:** '.$this->refund->refund_number)
            ->line('**Amount:** $'.number_format($this->refund->refund_amount, 2))
            ->line('**Reason:** '.$this->formatReason($this->refund->refund_reason));

        if ($this->refund->shift) {
            $mail->line('**Related Shift:** '.$this->refund->shift->title);
        }

        $mail->line('**Refund Method:** '.$this->formatMethod($this->refund->refund_method));

        if ($this->refund->refund_method === 'original_payment_method') {
            $mail->line('Please allow 5-10 business days for the refund to appear on your statement.');
        } elseif ($this->refund->refund_method === 'credit_balance') {
            $mail->line('The credit has been applied to your account balance and is available immediately.');
        }

        if ($this->refund->hasCreditNote()) {
            $mail->action('Download Credit Note', route('business.refunds.credit-note', $this->refund));
        }

        return $mail
            ->line('If you have any questions about this refund, please contact our support team.')
            ->salutation('Best regards,'."\n".config('app.name'));
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'refund_completed',
            'refund_id' => $this->refund->id,
            'refund_number' => $this->refund->refund_number,
            'refund_amount' => $this->refund->refund_amount,
            'refund_reason' => $this->refund->refund_reason,
            'refund_method' => $this->refund->refund_method,
            'shift_id' => $this->refund->shift_id,
            'shift_title' => $this->refund->shift?->title,
            'message' => 'Refund of $'.number_format($this->refund->refund_amount, 2).' has been processed ('.$this->refund->refund_number.')',
            'action_url' => route('business.finance.overview'),
        ];
    }

    /**
     * Format refund reason for display.
     */
    protected function formatReason(string $reason): string
    {
        return match ($reason) {
            'cancellation_72hr' => 'Shift Cancellation (72+ hours notice)',
            'dispute_resolved' => 'Dispute Resolution',
            'billing_error' => 'Billing Error Correction',
            'overcharge' => 'Overcharge Correction',
            'duplicate_charge' => 'Duplicate Charge',
            'goodwill' => 'Goodwill Adjustment',
            default => ucwords(str_replace('_', ' ', $reason)),
        };
    }

    /**
     * Format refund method for display.
     */
    protected function formatMethod(string $method): string
    {
        return match ($method) {
            'original_payment_method' => 'Original Payment Method',
            'credit_balance' => 'Account Credit',
            'manual' => 'Manual Processing',
            default => ucwords(str_replace('_', ' ', $method)),
        };
    }
}
