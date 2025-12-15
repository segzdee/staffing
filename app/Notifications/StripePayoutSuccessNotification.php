<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StripePayoutSuccessNotification
 *
 * Sent when a commission payout has been successfully processed
 * and sent to the agency's bank account.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 */
class StripePayoutSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyProfile $agency;
    protected float $amount;
    protected string $currency;
    protected string $payoutId;

    /**
     * Create a new notification instance.
     */
    public function __construct(AgencyProfile $agency, float $amount, string $currency, string $payoutId)
    {
        $this->agency = $agency;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->payoutId = $payoutId;
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
        $formattedAmount = $this->formatCurrency($this->amount, $this->currency);

        return (new MailMessage)
            ->subject('Payout Sent: ' . $formattedAmount)
            ->greeting('Good news, ' . $notifiable->name . '!')
            ->line('Your commission payout of **' . $formattedAmount . '** has been sent to your bank account.')
            ->line('')
            ->line('**Payout Details:**')
            ->line('- Amount: ' . $formattedAmount)
            ->line('- Currency: ' . strtoupper($this->currency))
            ->line('- Agency: ' . $this->agency->agency_name)
            ->line('- Date: ' . now()->format('F j, Y'))
            ->line('')
            ->line('The funds should appear in your bank account within 1-2 business days, depending on your bank.')
            ->action('View Payout History', route('agency.stripe.status'))
            ->line('Thank you for partnering with OvertimeStaff!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stripe_payout_success',
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->agency_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payout_id' => $this->payoutId,
            'message' => 'Your commission payout of ' . $this->formatCurrency($this->amount, $this->currency) . ' has been sent.',
            'action_url' => route('agency.stripe.status'),
        ];
    }

    /**
     * Format amount with currency symbol.
     */
    protected function formatCurrency(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'AUD' => 'A$',
            'INR' => '₹',
            'NGN' => '₦',
            'ZAR' => 'R',
            'BRL' => 'R$',
            'MXN' => 'MX$',
        ];

        $symbol = $symbols[strtoupper($currency)] ?? strtoupper($currency) . ' ';

        return $symbol . number_format($amount, 2);
    }
}
