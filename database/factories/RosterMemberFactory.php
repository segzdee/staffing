<?php

namespace Database\Factories;

use App\Models\BusinessRoster;
use App\Models\RosterMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RosterMember>
 */
class RosterMemberFactory extends Factory
{
    protected $model = RosterMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'roster_id' => BusinessRoster::factory(),
            'worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'active',
            'custom_rate' => fake()->optional(0.3)->randomFloat(2, 15, 50),
            'priority' => fake()->numberBetween(1, 100),
            'notes' => fake()->optional()->sentence(),
            'added_by' => User::factory()->create(['user_type' => 'business'])->id,
            'total_shifts' => fake()->numberBetween(0, 50),
            'last_worked_at' => fake()->optional(0.5)->dateTimeBetween('-6 months', 'now'),
            'preferred_positions' => null,
            'availability_preferences' => null,
        ];
    }

    /**
     * Indicate the member is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate the member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate the member is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    /**
     * Set a high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(80, 100),
        ]);
    }

    /**
     * Set a low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(1, 20),
        ]);
    }

    /**
     * Set a custom rate for the member.
     */
    public function withCustomRate(?float $rate = null): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_rate' => $rate ?? fake()->randomFloat(2, 20, 45),
        ]);
    }

    /**
     * Indicate the member has worked recently.
     */
    public function recentlyWorked(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_shifts' => fake()->numberBetween(5, 30),
            'last_worked_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
        ]);
    }

    /**
     * Indicate the member is new with no shifts.
     */
    public function newMember(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_shifts' => 0,
            'last_worked_at' => null,
        ]);
    }
}
