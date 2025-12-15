<?php

namespace Database\Factories;

use App\Models\AgencyWorker;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyWorker>
 */
class AgencyWorkerFactory extends Factory
{
    protected $model = AgencyWorker::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => User::factory()->create(['user_type' => 'agency'])->id,
            'worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'active', // enum: 'active', 'suspended', 'removed'
            'commission_rate' => $this->faker->randomElement([10, 15, 20, 25]), // 10-25%
            'added_at' => now(),
        ];
    }

    /**
     * State for removed relationship
     */
    public function removed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'removed',
            'removed_at' => now(),
            'notes' => 'Worker left agency',
        ]);
    }

    /**
     * State for suspended relationship
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'notes' => $this->faker->sentence(),
        ]);
    }
}
