<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use App\Models\ShiftAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftAssignment>
 */
class ShiftAssignmentFactory extends Factory
{
    protected $model = ShiftAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = User::factory()->create(['user_type' => 'business']);

        return [
            'shift_id' => Shift::factory()->create(['business_id' => $business->id])->id,
            'worker_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'assigned_by' => $business->id, // Required field
            'status' => 'assigned',
            'payment_status' => 'pending',
        ];
    }

    /**
     * State for pending worker acceptance
     */
    public function pendingAcceptance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_worker_acceptance',
            'assigned_at' => null,
        ]);
    }

    /**
     * State for completed assignment
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'checked_in_at' => now()->subHours(8),
            'checked_out_at' => now(),
            'payment_status' => 'pending',
        ]);
    }

    /**
     * State for cancelled assignment
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * State for agency-assigned
     */
    public function agencyAssigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'agency_id' => User::factory()->create(['user_type' => 'agency'])->id,
            'assigned_by_agency' => true,
        ]);
    }
}
