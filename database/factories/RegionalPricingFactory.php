<?php

namespace Database\Factories;

use App\Models\RegionalPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * GLO-009: Regional Pricing Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RegionalPricing>
 */
class RegionalPricingFactory extends Factory
{
    protected $model = RegionalPricing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countryCurrencyMap = [
            'US' => ['USD', 'United States', 1.000],
            'GB' => ['GBP', 'United Kingdom', 0.95],
            'CA' => ['CAD', 'Canada', 1.15],
            'AU' => ['AUD', 'Australia', 1.20],
            'DE' => ['EUR', 'Germany', 0.88],
            'FR' => ['EUR', 'France', 0.90],
            'JP' => ['JPY', 'Japan', 0.70],
            'IN' => ['INR', 'India', 0.25],
        ];

        $country = $this->faker->randomElement(array_keys($countryCurrencyMap));
        $data = $countryCurrencyMap[$country];

        return [
            'country_code' => $country,
            'region_code' => null,
            'currency_code' => $data[0],
            'country_name' => $data[1],
            'region_name' => null,
            'ppp_factor' => $data[2],
            'min_hourly_rate' => $this->faker->randomFloat(2, 10, 30),
            'max_hourly_rate' => $this->faker->randomFloat(2, 50, 150),
            'platform_fee_rate' => $this->faker->randomFloat(2, 10, 20),
            'worker_fee_rate' => $this->faker->randomFloat(2, 3, 10),
            'tier_adjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
            'is_active' => true,
        ];
    }

    /**
     * Create for United States.
     */
    public function unitedStates(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'US',
            'currency_code' => 'USD',
            'country_name' => 'United States',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
        ]);
    }

    /**
     * Create for United Kingdom.
     */
    public function unitedKingdom(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'GB',
            'currency_code' => 'GBP',
            'country_name' => 'United Kingdom',
            'ppp_factor' => 0.95,
            'min_hourly_rate' => 12.00,
            'max_hourly_rate' => 75.00,
        ]);
    }

    /**
     * Create with specific region.
     */
    public function withRegion(string $regionCode, string $regionName): static
    {
        return $this->state(fn (array $attributes) => [
            'region_code' => $regionCode,
            'region_name' => $regionName,
        ]);
    }

    /**
     * Create inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create with low PPP (developing market).
     */
    public function developingMarket(): static
    {
        return $this->state(fn (array $attributes) => [
            'ppp_factor' => $this->faker->randomFloat(3, 0.15, 0.40),
            'platform_fee_rate' => 10.00,
            'worker_fee_rate' => 3.00,
        ]);
    }
}
