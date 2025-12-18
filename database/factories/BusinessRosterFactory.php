<?php

namespace Database\Factories;

use App\Models\BusinessRoster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessRoster>
 */
class BusinessRosterFactory extends Factory
{
    protected $model = BusinessRoster::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => User::factory()->create(['user_type' => 'business'])->id,
            'name' => fake()->words(2, true).' Roster',
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['preferred', 'regular', 'backup', 'blacklist']),
            'is_default' => false,
        ];
    }

    /**
     * Indicate the roster is preferred type.
     */
    public function preferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'preferred',
        ]);
    }

    /**
     * Indicate the roster is regular type.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'regular',
        ]);
    }

    /**
     * Indicate the roster is backup type.
     */
    public function backup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'backup',
        ]);
    }

    /**
     * Indicate the roster is blacklist type.
     */
    public function blacklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'blacklist',
        ]);
    }

    /**
     * Indicate the roster is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
