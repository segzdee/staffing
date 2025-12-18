<?php

namespace Database\Factories;

use App\Models\BusinessRoster;
use App\Models\RosterInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RosterInvitation>
 */
class RosterInvitationFactory extends Factory
{
    protected $model = RosterInvitation::class;

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
            'invited_by' => User::factory()->create(['user_type' => 'business'])->id,
            'status' => 'pending',
            'message' => fake()->optional()->sentence(),
            'expires_at' => now()->addDays(7),
            'responded_at' => null,
        ];
    }

    /**
     * Indicate the invitation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'responded_at' => null,
        ]);
    }

    /**
     * Indicate the invitation was accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'responded_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate the invitation was declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'responded_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    /**
     * Set a custom expiration date.
     */
    public function expiresIn(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Set a custom message.
     */
    public function withMessage(?string $message = null): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => $message ?? fake()->paragraph(),
        ]);
    }

    /**
     * Indicate the invitation is past its expiration date but still pending.
     */
    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'expires_at' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }
}
