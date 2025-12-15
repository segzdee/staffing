<?php

namespace Database\Factories;

use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShiftPayment>
 */
class ShiftPaymentFactory extends Factory
{
    protected $model = ShiftPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountGross = $this->faker->numberBetween(100, 300); // $100-$300
        $platformFee = round($amountGross * 0.10, 2); // 10% platform fee
        $amountNet = $amountGross - $platformFee;

        $business = User::factory()->create(['user_type' => 'business']);
        $worker = User::factory()->create(['user_type' => 'worker']);

        return [
            'shift_assignment_id' => ShiftAssignment::factory()->create([
                'worker_id' => $worker->id,
                'assigned_by' => $business->id,
            ])->id,
            'worker_id' => $worker->id,
            'business_id' => $business->id,
            'amount_gross' => $amountGross,
            'platform_fee' => $platformFee,
            'amount_net' => $amountNet,
            'status' => 'pending_escrow',
        ];
    }

    /**
     * State for payment in escrow
     */
    public function inEscrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_escrow',
            'escrow_held_at' => now(),
            'stripe_payment_intent_id' => 'pi_' . uniqid(),
        ]);
    }

    /**
     * State for released payment
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'released',
            'escrow_held_at' => now()->subHours(10),
            'released_at' => now(),
        ]);
    }

    /**
     * State for completed payout
     */
    public function paidOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid_out',
            'escrow_held_at' => now()->subHours(12),
            'released_at' => now()->subHours(2),
            'payout_initiated_at' => now()->subMinutes(30),
            'payout_completed_at' => now(),
            'stripe_transfer_id' => 'tr_' . uniqid(),
        ]);
    }

    /**
     * State for disputed payment
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disputed',
            'disputed' => true,
            'escrow_held_at' => now()->subDays(2),
            'disputed_at' => now(),
            'dispute_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * State for refunded payment
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'dispute_reason' => $this->faker->sentence(),
            'resolved_at' => now(),
        ]);
    }
}
