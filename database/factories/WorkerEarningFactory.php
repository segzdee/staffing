<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerEarning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * WKR-006: Worker Earning Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkerEarning>
 */
class WorkerEarningFactory extends Factory
{
    protected $model = WorkerEarning::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grossAmount = $this->faker->randomFloat(2, 50, 300); // $50-$300
        $platformFeeRate = config('earnings.platform_fee_percentage', 10) / 100;
        $platformFee = round($grossAmount * $platformFeeRate, 2);
        $taxWithheld = 0;
        $netAmount = $grossAmount - $platformFee - $taxWithheld;

        return [
            'user_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'type' => $this->faker->randomElement(WorkerEarning::getTypes()),
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'tax_withheld' => $taxWithheld,
            'net_amount' => $netAmount,
            'currency' => config('earnings.default_currency', 'USD'),
            'description' => $this->faker->sentence(),
            'earned_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'hours_worked' => $this->faker->randomFloat(2, 1, 8),
            'hourly_rate' => $this->faker->randomFloat(2, 15, 50), // $15-$50/hour
            'status' => WorkerEarning::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the earning is for shift pay.
     */
    public function shiftPay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkerEarning::TYPE_SHIFT_PAY,
        ]);
    }

    /**
     * Indicate that the earning is a bonus.
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkerEarning::TYPE_BONUS,
            'platform_fee' => 0, // Bonuses typically have no platform fee
            'net_amount' => $attributes['gross_amount'],
        ]);
    }

    /**
     * Indicate that the earning is a tip.
     */
    public function tip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkerEarning::TYPE_TIP,
            'platform_fee' => 0, // Tips typically have no platform fee
            'net_amount' => $attributes['gross_amount'],
            'hours_worked' => null,
            'hourly_rate' => null,
        ]);
    }

    /**
     * Indicate that the earning is a referral bonus.
     */
    public function referral(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WorkerEarning::TYPE_REFERRAL,
            'platform_fee' => 0,
            'net_amount' => $attributes['gross_amount'],
            'hours_worked' => null,
            'hourly_rate' => null,
            'description' => 'Referral bonus',
        ]);
    }

    /**
     * Indicate that the earning is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerEarning::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the earning is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerEarning::STATUS_APPROVED,
        ]);
    }

    /**
     * Indicate that the earning has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerEarning::STATUS_PAID,
            'paid_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the earning is disputed.
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkerEarning::STATUS_DISPUTED,
            'dispute_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Set earning with tax withholding.
     */
    public function withTaxWithholding(int $percentage = 15): static
    {
        return $this->state(function (array $attributes) use ($percentage) {
            $taxWithheld = (int) round($attributes['gross_amount'] * ($percentage / 100));
            $netAmount = $attributes['gross_amount'] - $attributes['platform_fee'] - $taxWithheld;

            return [
                'tax_withheld' => $taxWithheld,
                'net_amount' => $netAmount,
            ];
        });
    }

    /**
     * Set earning for a specific date.
     */
    public function forDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'earned_date' => $date,
        ]);
    }

    /**
     * Set earning for a specific worker.
     */
    public function forWorker(User $worker): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $worker->id,
        ]);
    }
}
