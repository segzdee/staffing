<?php

namespace Database\Factories;

use App\Models\PaymentCorridor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentCorridor>
 */
class PaymentCorridorFactory extends Factory
{
    protected $model = PaymentCorridor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = ['US', 'GB', 'DE', 'FR', 'AU', 'CA', 'JP', 'IN'];
        $currencies = [
            'US' => 'USD',
            'GB' => 'GBP',
            'DE' => 'EUR',
            'FR' => 'EUR',
            'AU' => 'AUD',
            'CA' => 'CAD',
            'JP' => 'JPY',
            'IN' => 'INR',
        ];

        $sourceCountry = fake()->randomElement($countries);
        $destCountry = fake()->randomElement(array_diff($countries, [$sourceCountry]));

        return [
            'source_country' => $sourceCountry,
            'destination_country' => $destCountry,
            'source_currency' => $currencies[$sourceCountry],
            'destination_currency' => $currencies[$destCountry],
            'payment_method' => fake()->randomElement(PaymentCorridor::PAYMENT_METHODS),
            'estimated_days_min' => fake()->numberBetween(0, 2),
            'estimated_days_max' => fake()->numberBetween(2, 5),
            'fee_fixed' => fake()->randomFloat(2, 0, 25),
            'fee_percent' => fake()->randomFloat(2, 0, 2),
            'min_amount' => fake()->optional(0.7)->randomFloat(2, 1, 100),
            'max_amount' => fake()->optional(0.7)->randomFloat(2, 10000, 250000),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the corridor is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a SEPA corridor.
     */
    public function sepa(): static
    {
        $sepaCountries = ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT'];

        return $this->state(fn (array $attributes) => [
            'source_country' => 'US',
            'destination_country' => fake()->randomElement($sepaCountries),
            'source_currency' => 'USD',
            'destination_currency' => 'EUR',
            'payment_method' => PaymentCorridor::METHOD_SEPA,
            'estimated_days_min' => 1,
            'estimated_days_max' => 2,
            'fee_fixed' => 0.50,
            'fee_percent' => 0.35,
        ]);
    }

    /**
     * Create an ACH corridor.
     */
    public function ach(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_country' => 'US',
            'destination_country' => 'US',
            'source_currency' => 'USD',
            'destination_currency' => 'USD',
            'payment_method' => PaymentCorridor::METHOD_ACH,
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'fee_fixed' => 0.00,
            'fee_percent' => 0.25,
        ]);
    }

    /**
     * Create a Faster Payments corridor.
     */
    public function fasterPayments(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_country' => 'GB',
            'destination_country' => 'GB',
            'source_currency' => 'GBP',
            'destination_currency' => 'GBP',
            'payment_method' => PaymentCorridor::METHOD_FASTER_PAYMENTS,
            'estimated_days_min' => 0,
            'estimated_days_max' => 1,
            'fee_fixed' => 0.00,
            'fee_percent' => 0.20,
        ]);
    }

    /**
     * Create a SWIFT corridor.
     */
    public function swift(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentCorridor::METHOD_SWIFT,
            'estimated_days_min' => 2,
            'estimated_days_max' => 5,
            'fee_fixed' => 15.00,
            'fee_percent' => 0.50,
        ]);
    }
}
