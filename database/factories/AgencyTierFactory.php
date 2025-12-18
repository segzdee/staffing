<?php

namespace Database\Factories;

use App\Models\AgencyTier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyTier>
 */
class AgencyTierFactory extends Factory
{
    protected $model = AgencyTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'level' => match ($name) {
                'Bronze' => 1,
                'Silver' => 2,
                'Gold' => 3,
                'Platinum' => 4,
                'Diamond' => 5,
            },
            'min_monthly_revenue' => $this->faker->randomElement([0, 5000, 20000, 50000, 100000]),
            'min_active_workers' => $this->faker->randomElement([5, 20, 50, 100, 200]),
            'min_fill_rate' => $this->faker->randomFloat(2, 0, 95),
            'min_rating' => $this->faker->randomFloat(2, 0, 4.5),
            'commission_rate' => $this->faker->randomFloat(2, 5, 15),
            'priority_booking_hours' => $this->faker->randomElement([0, 2, 6, 12, 24]),
            'dedicated_support' => $this->faker->boolean(),
            'custom_branding' => $this->faker->boolean(),
            'api_access' => $this->faker->boolean(),
            'additional_benefits' => [],
            'is_active' => true,
        ];
    }

    /**
     * Bronze tier state.
     */
    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bronze',
            'slug' => 'bronze',
            'level' => 1,
            'min_monthly_revenue' => 0,
            'min_active_workers' => 5,
            'min_fill_rate' => 0,
            'min_rating' => 0,
            'commission_rate' => 15.00,
            'priority_booking_hours' => 0,
            'dedicated_support' => false,
            'custom_branding' => false,
            'api_access' => false,
        ]);
    }

    /**
     * Silver tier state.
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Silver',
            'slug' => 'silver',
            'level' => 2,
            'min_monthly_revenue' => 5000,
            'min_active_workers' => 20,
            'min_fill_rate' => 80,
            'min_rating' => 4.0,
            'commission_rate' => 12.00,
            'priority_booking_hours' => 2,
            'dedicated_support' => false,
            'custom_branding' => false,
            'api_access' => false,
        ]);
    }

    /**
     * Gold tier state.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Gold',
            'slug' => 'gold',
            'level' => 3,
            'min_monthly_revenue' => 20000,
            'min_active_workers' => 50,
            'min_fill_rate' => 85,
            'min_rating' => 4.2,
            'commission_rate' => 10.00,
            'priority_booking_hours' => 6,
            'dedicated_support' => true,
            'custom_branding' => false,
            'api_access' => true,
        ]);
    }

    /**
     * Inactive tier state.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
