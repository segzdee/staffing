<?php

namespace Database\Factories;

use App\Models\AgencyPerformanceScorecard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for AgencyPerformanceScorecard model.
 * AGY-005: Agency Performance Notification System
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyPerformanceScorecard>
 */
class AgencyPerformanceScorecardFactory extends Factory
{
    protected $model = AgencyPerformanceScorecard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalShifts = $this->faker->numberBetween(10, 100);
        $shiftsFilled = (int) ($totalShifts * ($this->faker->numberBetween(70, 100) / 100));
        $noShows = $this->faker->numberBetween(0, (int) ($totalShifts * 0.1));
        $complaints = $this->faker->numberBetween(0, (int) ($totalShifts * 0.05));
        $totalRatings = $this->faker->numberBetween((int) ($shiftsFilled * 0.5), $shiftsFilled);

        $fillRate = $totalShifts > 0 ? ($shiftsFilled / $totalShifts) * 100 : 0;
        $noShowRate = $totalShifts > 0 ? ($noShows / $totalShifts) * 100 : 0;
        $complaintRate = $totalShifts > 0 ? ($complaints / $totalShifts) * 100 : 0;
        $avgRating = $this->faker->randomFloat(2, 3.5, 5.0);

        return [
            'agency_id' => User::factory()->create(['user_type' => 'agency'])->id,
            'period_start' => now()->subWeek()->startOfWeek(),
            'period_end' => now()->subWeek()->endOfWeek(),
            'period_type' => 'weekly',
            'fill_rate' => round($fillRate, 2),
            'no_show_rate' => round($noShowRate, 2),
            'average_worker_rating' => $avgRating,
            'complaint_rate' => round($complaintRate, 2),
            'total_shifts_assigned' => $totalShifts,
            'shifts_filled' => $shiftsFilled,
            'shifts_unfilled' => $totalShifts - $shiftsFilled,
            'no_shows' => $noShows,
            'complaints_received' => $complaints,
            'total_ratings' => $totalRatings,
            'total_rating_sum' => round($avgRating * $totalRatings, 2),
            'urgent_fill_requests' => $this->faker->numberBetween(0, 10),
            'urgent_fills_completed' => $this->faker->numberBetween(0, 8),
            'urgent_fill_rate' => $this->faker->randomFloat(2, 50, 100),
            'average_response_time_minutes' => $this->faker->randomFloat(2, 5, 60),
            'status' => 'green',
            'warnings' => [],
            'flags' => [],
            'target_fill_rate' => 90.00,
            'target_no_show_rate' => 3.00,
            'target_average_rating' => 4.30,
            'target_complaint_rate' => 2.00,
            'warning_sent' => false,
            'warning_sent_at' => null,
            'sanction_applied' => false,
            'sanction_type' => null,
            'sanction_applied_at' => null,
            'notes' => null,
            'generated_at' => now(),
            'generated_by' => null,
        ];
    }

    /**
     * Create a green (passing) scorecard.
     */
    public function green(): static
    {
        return $this->state(fn (array $attributes) => [
            'fill_rate' => $this->faker->randomFloat(2, 92, 100),
            'no_show_rate' => $this->faker->randomFloat(2, 0, 2.5),
            'average_worker_rating' => $this->faker->randomFloat(2, 4.4, 5.0),
            'complaint_rate' => $this->faker->randomFloat(2, 0, 1.5),
            'status' => 'green',
            'warnings' => [],
            'flags' => [],
        ]);
    }

    /**
     * Create a yellow (warning) scorecard.
     */
    public function yellow(): static
    {
        return $this->state(fn (array $attributes) => [
            'fill_rate' => $this->faker->randomFloat(2, 85, 89),
            'no_show_rate' => $this->faker->randomFloat(2, 3.5, 5),
            'average_worker_rating' => $this->faker->randomFloat(2, 4.1, 4.29),
            'complaint_rate' => $this->faker->randomFloat(2, 2.5, 4),
            'status' => 'yellow',
            'warnings' => ['Fill rate below target', 'No-show rate above target'],
            'flags' => [],
        ]);
    }

    /**
     * Create a red (critical) scorecard.
     */
    public function red(): static
    {
        return $this->state(fn (array $attributes) => [
            'fill_rate' => $this->faker->randomFloat(2, 60, 79),
            'no_show_rate' => $this->faker->randomFloat(2, 6, 15),
            'average_worker_rating' => $this->faker->randomFloat(2, 3.0, 3.8),
            'complaint_rate' => $this->faker->randomFloat(2, 5, 10),
            'status' => 'red',
            'warnings' => [
                'Fill rate critically below target',
                'No-show rate critically above target',
                'Worker rating below acceptable level',
            ],
            'flags' => ['critical_fill_rate', 'critical_no_show_rate', 'low_worker_quality'],
        ]);
    }

    /**
     * Create a scorecard with warning sent.
     */
    public function withWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'warning_sent' => true,
            'warning_sent_at' => now(),
        ]);
    }

    /**
     * Create a scorecard with fee increase sanction.
     */
    public function withFeeIncrease(): static
    {
        return $this->red()->state(fn (array $attributes) => [
            'sanction_applied' => true,
            'sanction_type' => 'fee_increase',
            'sanction_applied_at' => now(),
            'notes' => 'Commission rate increased due to poor performance',
        ]);
    }

    /**
     * Create a scorecard with suspension sanction.
     */
    public function withSuspension(): static
    {
        return $this->red()->state(fn (array $attributes) => [
            'sanction_applied' => true,
            'sanction_type' => 'suspension',
            'sanction_applied_at' => now(),
            'notes' => 'Agency suspended due to 3+ consecutive red scorecards',
        ]);
    }

    /**
     * Create scorecard for specific agency.
     */
    public function forAgency(User $agency): static
    {
        return $this->state(fn (array $attributes) => [
            'agency_id' => $agency->id,
        ]);
    }

    /**
     * Create scorecard for specific period.
     */
    public function forPeriod($start, $end): static
    {
        return $this->state(fn (array $attributes) => [
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }
}
