<?php

namespace Database\Factories;

use App\Models\ShiftAssignment;
use App\Models\ShiftSwap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftSwap>
 */
class ShiftSwapFactory extends Factory
{
    protected $model = ShiftSwap::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_assignment_id' => ShiftAssignment::factory(),
            'offering_worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'pending',
            'reason' => $this->faker->sentence(),
        ];
    }

    /**
     * State for approved swap
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'receiving_worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'approved',
            'business_approved_at' => now(),
            'approved_by' => User::factory()->create(['user_type' => 'business'])->id,
        ]);
    }

    /**
     * State for rejected swap
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'receiving_worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'status' => 'rejected',
            'business_approved_at' => now(),
            'approved_by' => User::factory()->create(['user_type' => 'business'])->id,
        ]);
    }

    /**
     * State for cancelled swap
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
