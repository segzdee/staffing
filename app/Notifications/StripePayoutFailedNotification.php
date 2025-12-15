<?php

namespace App\Notifications;

use App\Models\AgencyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * StripePayoutFailedNotification
 *
 * Sent when a commission payout fails to process.
 * Includes information about the failure and next steps.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 */
class StripePayoutFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected AgencyProfile $agency;
    protected float $amount;
    protected string $failureCode;
    protected string $failureMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(AgencyProfile $agency, float $amount, string $failureCode, string $failureMessage)
    {
        $this->agency = $agency;
        $this->amount = $amount;
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
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
        $formattedAmount = '$' . number_format($this->amount, 2);

        $mail = (new MailMessage)
            ->subject('Payout Failed: Action Required')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your commission payout of **' . $formattedAmount . '** could not be processed.')
            ->line('')
            ->line('**Reason:** ' . $this->getHumanReadableError())
            ->line('');

        // Add resolution steps based on failure code
        $steps = $this->getResolutionSteps();
        if (!empty($steps)) {
            $mail->line('**To resolve this issue:**');
            foreach ($steps as $step) {
                $mail->line('- ' . $step);
            }
            $mail->line('');
        }

        $mail->line('Your commission balance of **' . $formattedAmount . '** is safe and will be included in your next payout once the issue is resolved.')
            ->action('Update Payout Details', route('agency.stripe.status'))
            ->line('If you need assistance, please contact our support team.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stripe_payout_failed',
            'agency_id' => $this->agency->id,
            'agency_name' => $this->agency->agency_name,
            'amount' => $this->amount,
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
            'human_readable_error' => $this->getHumanReadableError(),
            'message' => 'Your payout of $' . number_format($this->amount, 2) . ' failed: ' . $this->getHumanReadableError(),
            'action_url' => route('agency.stripe.status'),
        ];
    }

    /**
     * Convert Stripe failure code to human-readable message.
     */
    protected function getHumanReadableError(): string
    {
        $errorMessages = [
            'account_closed' => 'Your bank account has been closed.',
            'account_frozen' => 'Your bank account is frozen.',
            'bank_account_restricted' => 'Your bank account has restrictions that prevent this transfer.',
            'bank_ownership_changed' => 'Bank account ownership has changed.',
            'could_not_process' => 'The bank could not process this transfer.',
            'debit_not_authorized' => 'Debit transactions are not authorized for this account.',
            'declined' => 'The transfer was declined by the bank.',
            'insufficient_funds' => 'There are insufficient funds in the source account.',
            'invalid_account_number' => 'The bank account number is invalid.',
            'incorrect_account_holder_name' => 'The account holder name doesn\'t match bank records.',
            'invalid_currency' => 'The currency is not supported by this bank account.',
            'no_account' => 'No bank account was found with the provided details.',
            'unsupported_card' => 'This type of card is not supported for payouts.',
        ];

        if (isset($errorMessages[$this->failureCode])) {
            return $errorMessages[$this->failureCode];
        }

        // Return the original message if no translation available
        return $this->failureMessage ?: 'An unexpected error occurred.';
    }

    /**
     * Get resolution steps based on failure code.
     */
    protected function getResolutionSteps(): array
    {
        $steps = match ($this->failureCode) {
            'account_closed', 'bank_account_restricted', 'no_account' => [
                'Log in to your Stripe dashboard',
                'Update your bank account information',
                'Wait for the new account to be verified',
            ],
            'invalid_account_number', 'incorrect_account_holder_name' => [
                'Verify your bank account details are correct',
                'Update any incorrect information in Stripe',
                'Ensure the account name matches your ID exactly',
            ],
            'insufficient_funds' => [
                'This is usually a temporary issue',
                'The payout will be retried automatically',
            ],
            'declined', 'could_not_process' => [
                'Contact your bank to understand why the transfer was declined',
                'Update your bank account if necessary',
            ],
            default => [
                'Review your Stripe account settings',
                'Verify your bank account is still active and accepting deposits',
                'Contact support if the issue persists',
            ],
        };

        return $steps;
    }
}
