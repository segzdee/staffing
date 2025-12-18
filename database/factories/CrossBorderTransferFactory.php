<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\CrossBorderTransfer;
use App\Models\PaymentCorridor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrossBorderTransfer>
 */
class CrossBorderTransferFactory extends Factory
{
    protected $model = CrossBorderTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sourceAmount = fake()->randomFloat(2, 100, 10000);
        $exchangeRate = fake()->randomFloat(4, 0.5, 2.0);
        $destinationAmount = round($sourceAmount * $exchangeRate, 2);
        $feeAmount = round($sourceAmount * 0.01 + fake()->randomFloat(2, 0, 5), 2);

        return [
            'transfer_reference' => 'CBT-'.strtoupper(fake()->unique()->lexify('????????????')),
            'user_id' => User::factory(),
            'bank_account_id' => BankAccount::factory(),
            'source_currency' => 'USD',
            'destination_currency' => 'EUR',
            'source_amount' => $sourceAmount,
            'destination_amount' => $destinationAmount,
            'exchange_rate' => $exchangeRate,
            'fee_amount' => $feeAmount,
            'payment_method' => fake()->randomElement(PaymentCorridor::PAYMENT_METHODS),
            'status' => CrossBorderTransfer::STATUS_PENDING,
            'estimated_arrival_at' => fake()->dateTimeBetween('+1 day', '+5 days'),
        ];
    }

    /**
     * Indicate that the transfer is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_PENDING,
            'sent_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_PROCESSING,
            'sent_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_SENT,
            'sent_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'provider_reference' => strtoupper(fake()->lexify('????-????????????')),
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer is completed.
     */
    public function completed(): static
    {
        $sentAt = fake()->dateTimeBetween('-5 days', '-1 day');

        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_COMPLETED,
            'sent_at' => $sentAt,
            'completed_at' => fake()->dateTimeBetween($sentAt, 'now'),
            'provider_reference' => strtoupper(fake()->lexify('????-????????????')),
        ]);
    }

    /**
     * Indicate that the transfer has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_FAILED,
            'failure_reason' => fake()->randomElement([
                'Invalid bank account details',
                'Beneficiary bank rejected transfer',
                'Compliance check failed',
                'Insufficient funds',
                'Account closed',
            ]),
            'sent_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer was returned.
     */
    public function returned(): static
    {
        $sentAt = fake()->dateTimeBetween('-10 days', '-3 days');

        return $this->state(fn (array $attributes) => [
            'status' => CrossBorderTransfer::STATUS_RETURNED,
            'sent_at' => $sentAt,
            'failure_reason' => fake()->randomElement([
                'Account not found',
                'Beneficiary details mismatch',
                'Bank account closed',
                'Rejected by beneficiary bank',
            ]),
            'provider_reference' => strtoupper(fake()->lexify('????-????????????')),
        ]);
    }

    /**
     * Create a SEPA transfer.
     */
    public function sepa(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_currency' => 'USD',
            'destination_currency' => 'EUR',
            'payment_method' => PaymentCorridor::METHOD_SEPA,
            'estimated_arrival_at' => fake()->dateTimeBetween('+1 day', '+2 days'),
        ]);
    }

    /**
     * Create an ACH transfer.
     */
    public function ach(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_currency' => 'USD',
            'destination_currency' => 'USD',
            'payment_method' => PaymentCorridor::METHOD_ACH,
            'estimated_arrival_at' => fake()->dateTimeBetween('+1 day', '+3 days'),
        ]);
    }

    /**
     * Create a Faster Payments transfer.
     */
    public function fasterPayments(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_currency' => 'GBP',
            'destination_currency' => 'GBP',
            'payment_method' => PaymentCorridor::METHOD_FASTER_PAYMENTS,
            'estimated_arrival_at' => fake()->dateTimeBetween('now', '+1 day'),
        ]);
    }

    /**
     * Create a SWIFT transfer.
     */
    public function swift(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentCorridor::METHOD_SWIFT,
            'estimated_arrival_at' => fake()->dateTimeBetween('+2 days', '+5 days'),
        ]);
    }
}
