<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_holder_name' => fake()->name(),
            'bank_name' => fake()->company().' Bank',
            'country_code' => 'US',
            'currency_code' => 'USD',
            'routing_number' => $this->generateValidRoutingNumber(),
            'account_number' => fake()->numerify('########'),
            'account_type' => fake()->randomElement(['checking', 'savings']),
            'is_verified' => false,
            'is_primary' => false,
        ];
    }

    /**
     * Generate a valid US routing number (passes checksum).
     */
    protected function generateValidRoutingNumber(): string
    {
        // Generate first 8 digits
        $base = str_pad((string) fake()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        // Calculate checksum digit
        // 3(d1 + d4 + d7) + 7(d2 + d5 + d8) + (d3 + d6) must sum to multiple of 10 with d9
        $sum = 3 * ($base[0] + $base[3] + $base[6])
             + 7 * ($base[1] + $base[4] + $base[7])
             + ($base[2] + $base[5]);

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $base.$checkDigit;
    }

    /**
     * Indicate that the bank account is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the bank account is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Create a US bank account (ACH).
     */
    public function us(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'US',
            'currency_code' => 'USD',
            'routing_number' => $this->generateValidRoutingNumber(),
            'account_number' => fake()->numerify('########'),
            'iban' => null,
            'sort_code' => null,
            'bsb_code' => null,
            'swift_bic' => null,
        ]);
    }

    /**
     * Create a UK bank account (Faster Payments).
     */
    public function uk(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'GB',
            'currency_code' => 'GBP',
            'sort_code' => fake()->numerify('######'),
            'account_number' => fake()->numerify('########'),
            'routing_number' => null,
            'iban' => null,
            'bsb_code' => null,
            'swift_bic' => null,
        ]);
    }

    /**
     * Create an Australian bank account.
     */
    public function australian(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'AU',
            'currency_code' => 'AUD',
            'bsb_code' => fake()->numerify('######'),
            'account_number' => fake()->numerify('#########'),
            'routing_number' => null,
            'iban' => null,
            'sort_code' => null,
            'swift_bic' => null,
        ]);
    }

    /**
     * Create a SEPA bank account (EU with IBAN).
     */
    public function sepa(): static
    {
        $countries = ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT'];
        $country = fake()->randomElement($countries);

        return $this->state(fn (array $attributes) => [
            'country_code' => $country,
            'currency_code' => 'EUR',
            'iban' => $this->generateFakeIban($country),
            'swift_bic' => strtoupper(fake()->lexify('????')).$country.strtoupper(fake()->lexify('??')),
            'routing_number' => null,
            'sort_code' => null,
            'bsb_code' => null,
            'account_number' => null,
        ]);
    }

    /**
     * Create an international bank account with SWIFT.
     */
    public function international(): static
    {
        $countries = ['JP', 'IN', 'SG', 'HK', 'MX', 'BR'];
        $currencies = [
            'JP' => 'JPY',
            'IN' => 'INR',
            'SG' => 'SGD',
            'HK' => 'HKD',
            'MX' => 'MXN',
            'BR' => 'BRL',
        ];

        $country = fake()->randomElement($countries);

        return $this->state(fn (array $attributes) => [
            'country_code' => $country,
            'currency_code' => $currencies[$country],
            'account_number' => fake()->numerify('##########'),
            'swift_bic' => strtoupper(fake()->lexify('????')).$country.strtoupper(fake()->lexify('??')),
            'routing_number' => null,
            'sort_code' => null,
            'bsb_code' => null,
            'iban' => null,
        ]);
    }

    /**
     * Generate a fake but structurally valid IBAN.
     * Note: This won't pass real IBAN validation but is suitable for testing.
     */
    protected function generateFakeIban(string $countryCode): string
    {
        $lengths = [
            'DE' => 22,
            'FR' => 27,
            'ES' => 24,
            'IT' => 27,
            'NL' => 18,
            'BE' => 16,
            'AT' => 20,
        ];

        $length = $lengths[$countryCode] ?? 22;
        $bbanLength = $length - 4;

        // Generate BBAN (Basic Bank Account Number)
        $bban = '';
        for ($i = 0; $i < $bbanLength; $i++) {
            $bban .= fake()->randomDigit();
        }

        // For testing purposes, use check digits 00 (not valid but identifies test data)
        return $countryCode.'00'.$bban;
    }
}
