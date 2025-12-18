<?php

namespace Database\Factories;

use App\Models\PriceAdjustment;
use App\Models\RegionalPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * GLO-009: Price Adjustment Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceAdjustment>
 */
class PriceAdjustmentFactory extends Factory
{
    protected $model = PriceAdjustment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'regional_pricing_id' => RegionalPricing::factory(),
            'adjustment_type' => $this->faker->randomElement([
                PriceAdjustment::TYPE_SUBSCRIPTION,
                PriceAdjustment::TYPE_SERVICE_FEE,
                PriceAdjustment::TYPE_SURGE,
                PriceAdjustment::TYPE_PROMOTIONAL,
                PriceAdjustment::TYPE_SEASONAL,
            ]),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'multiplier' => $this->faker->randomFloat(3, 0.80, 1.50),
            'fixed_adjustment' => 0,
            'valid_from' => now(),
            'valid_until' => null,
            'conditions' => null,
            'is_active' => true,
        ];
    }

    /**
     * Create surge pricing adjustment.
     */
    public function surge(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => PriceAdjustment::TYPE_SURGE,
            'name' => 'Surge Pricing',
            'multiplier' => $this->faker->randomFloat(3, 1.10, 1.50),
        ]);
    }

    /**
     * Create promotional discount.
     */
    public function promotional(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => PriceAdjustment::TYPE_PROMOTIONAL,
            'name' => 'Promotional Discount',
            'multiplier' => $this->faker->randomFloat(3, 0.70, 0.95),
            'valid_until' => now()->addDays(30),
        ]);
    }

    /**
     * Create weekend adjustment.
     */
    public function weekend(): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_type' => PriceAdjustment::TYPE_SURGE,
            'name' => 'Weekend Surge',
            'multiplier' => 1.15,
            'conditions' => ['days_of_week' => [0, 6]],
        ]);
    }

    /**
     * Create scheduled future adjustment.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->addDays(7),
            'valid_until' => now()->addDays(37),
        ]);
    }

    /**
     * Create expired adjustment.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(60),
            'valid_until' => now()->subDays(30),
        ]);
    }

    /**
     * Create inactive adjustment.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create with fixed adjustment.
     */
    public function withFixedAdjustment(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'fixed_adjustment' => $amount,
        ]);
    }
}
