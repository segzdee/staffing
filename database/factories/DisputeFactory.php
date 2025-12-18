<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * DisputeFactory
 *
 * FIN-010: Factory for creating test Dispute instances.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispute>
 */
class DisputeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Dispute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'worker_id' => User::factory()->state(['user_type' => 'worker']),
            'business_id' => User::factory()->state(['user_type' => 'business']),
            'type' => $this->faker->randomElement([
                Dispute::TYPE_PAYMENT,
                Dispute::TYPE_HOURS,
                Dispute::TYPE_DEDUCTION,
                Dispute::TYPE_BONUS,
                Dispute::TYPE_EXPENSES,
                Dispute::TYPE_OTHER,
            ]),
            'status' => Dispute::STATUS_OPEN,
            'disputed_amount' => $this->faker->randomFloat(2, 10, 500),
            'worker_description' => $this->faker->paragraph(3),
            'business_response' => null,
            'assigned_to' => null,
            'evidence_worker' => null,
            'evidence_business' => null,
            'resolution' => null,
            'resolution_amount' => null,
            'resolution_notes' => null,
            'evidence_deadline' => now()->addDays(config('disputes.evidence_deadline_days', 5)),
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate the dispute is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_OPEN,
            'business_response' => null,
        ]);
    }

    /**
     * Indicate the dispute is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_UNDER_REVIEW,
            'business_response' => $this->faker->paragraph(2),
        ]);
    }

    /**
     * Indicate the dispute is awaiting evidence.
     */
    public function awaitingEvidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_AWAITING_EVIDENCE,
            'business_response' => $this->faker->paragraph(2),
        ]);
    }

    /**
     * Indicate the dispute is in mediation.
     */
    public function inMediation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_MEDIATION,
            'business_response' => $this->faker->paragraph(2),
            'assigned_to' => User::factory()->state(['role' => 'admin']),
        ]);
    }

    /**
     * Indicate the dispute is resolved in worker's favor.
     */
    public function resolvedWorkerFavor(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_RESOLVED,
            'business_response' => $this->faker->paragraph(2),
            'resolution' => Dispute::RESOLUTION_WORKER_FAVOR,
            'resolution_amount' => $attributes['disputed_amount'],
            'resolution_notes' => $this->faker->sentence(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate the dispute is resolved in business's favor.
     */
    public function resolvedBusinessFavor(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_RESOLVED,
            'business_response' => $this->faker->paragraph(2),
            'resolution' => Dispute::RESOLUTION_BUSINESS_FAVOR,
            'resolution_amount' => 0,
            'resolution_notes' => $this->faker->sentence(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate the dispute is resolved with split.
     */
    public function resolvedSplit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_RESOLVED,
            'business_response' => $this->faker->paragraph(2),
            'resolution' => Dispute::RESOLUTION_SPLIT,
            'resolution_amount' => $attributes['disputed_amount'] / 2,
            'resolution_notes' => $this->faker->sentence(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate the dispute is escalated.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_ESCALATED,
            'business_response' => $this->faker->paragraph(2),
        ]);
    }

    /**
     * Indicate the dispute is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_CLOSED,
            'resolution_notes' => 'Dispute closed',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Add worker evidence.
     */
    public function withWorkerEvidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'evidence_worker' => [
                [
                    'name' => 'screenshot.jpg',
                    'path' => 'disputes/1/evidence/screenshot.jpg',
                    'url' => '/storage/disputes/1/evidence/screenshot.jpg',
                    'mime' => 'image/jpeg',
                    'size' => 102400,
                    'uploaded_at' => now()->toDateTimeString(),
                ],
            ],
        ]);
    }

    /**
     * Add business evidence.
     */
    public function withBusinessEvidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'evidence_business' => [
                [
                    'name' => 'timesheet.pdf',
                    'path' => 'disputes/1/evidence/timesheet.pdf',
                    'url' => '/storage/disputes/1/evidence/timesheet.pdf',
                    'mime' => 'application/pdf',
                    'size' => 51200,
                    'uploaded_at' => now()->toDateTimeString(),
                ],
            ],
        ]);
    }

    /**
     * Set dispute type to payment.
     */
    public function paymentDispute(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Dispute::TYPE_PAYMENT,
        ]);
    }

    /**
     * Set dispute type to hours.
     */
    public function hoursDispute(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Dispute::TYPE_HOURS,
        ]);
    }

    /**
     * Mark as stale (no activity for 35 days).
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Dispute::STATUS_OPEN,
            'created_at' => now()->subDays(35),
            'updated_at' => now()->subDays(35),
        ]);
    }

    /**
     * Mark evidence deadline as passed.
     */
    public function pastDeadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'evidence_deadline' => now()->subDay(),
        ]);
    }
}
