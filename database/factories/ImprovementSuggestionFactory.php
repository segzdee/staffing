<?php

namespace Database\Factories;

use App\Models\ImprovementSuggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * QUA-005: Factory for ImprovementSuggestion model.
 */
class ImprovementSuggestionFactory extends Factory
{
    protected $model = ImprovementSuggestion::class;

    public function definition(): array
    {
        return [
            'submitted_by' => User::factory(),
            'category' => $this->faker->randomElement([
                ImprovementSuggestion::CATEGORY_FEATURE,
                ImprovementSuggestion::CATEGORY_BUG,
                ImprovementSuggestion::CATEGORY_UX,
                ImprovementSuggestion::CATEGORY_PROCESS,
                ImprovementSuggestion::CATEGORY_PERFORMANCE,
                ImprovementSuggestion::CATEGORY_OTHER,
            ]),
            'priority' => $this->faker->randomElement([
                ImprovementSuggestion::PRIORITY_LOW,
                ImprovementSuggestion::PRIORITY_MEDIUM,
                ImprovementSuggestion::PRIORITY_HIGH,
                ImprovementSuggestion::PRIORITY_CRITICAL,
            ]),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(2, true),
            'expected_impact' => $this->faker->optional(0.7)->paragraph(),
            'status' => ImprovementSuggestion::STATUS_SUBMITTED,
            'votes' => 0,
            'assigned_to' => null,
            'admin_notes' => null,
            'rejection_reason' => null,
            'reviewed_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Mark the suggestion as under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_UNDER_REVIEW,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Mark the suggestion as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Mark the suggestion as in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_IN_PROGRESS,
            'reviewed_at' => now(),
            'assigned_to' => User::factory(),
        ]);
    }

    /**
     * Mark the suggestion as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_COMPLETED,
            'reviewed_at' => now()->subDays(7),
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the suggestion as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_REJECTED,
            'reviewed_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Mark the suggestion as deferred.
     */
    public function deferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImprovementSuggestion::STATUS_DEFERRED,
            'reviewed_at' => now(),
            'admin_notes' => 'Deferred to next quarter.',
        ]);
    }

    /**
     * Set specific vote count.
     */
    public function withVotes(int $votes): static
    {
        return $this->state(fn (array $attributes) => [
            'votes' => $votes,
        ]);
    }

    /**
     * Set as feature request.
     */
    public function feature(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ImprovementSuggestion::CATEGORY_FEATURE,
        ]);
    }

    /**
     * Set as bug report.
     */
    public function bug(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => ImprovementSuggestion::CATEGORY_BUG,
        ]);
    }

    /**
     * Set as critical priority.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => ImprovementSuggestion::PRIORITY_CRITICAL,
        ]);
    }
}
