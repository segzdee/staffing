<?php

namespace Database\Factories;

use App\Models\EarningsSummary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * WKR-006: Earnings Summary Factory
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EarningsSummary>
 */
class EarningsSummaryFactory extends Factory
{
    protected $model = EarningsSummary::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grossEarnings = $this->faker->randomFloat(2, 500, 5000); // $500-$5000
        $totalFees = round($grossEarnings * 0.10, 2);
        $totalTaxes = round($grossEarnings * 0.05, 2);
        $netEarnings = $grossEarnings - $totalFees - $totalTaxes;
        $totalHours = $this->faker->randomFloat(2, 10, 80);
        $shiftsCompleted = $this->faker->numberBetween(5, 30);
        $avgHourlyRate = $totalHours > 0 ? round($grossEarnings / $totalHours, 2) : 0;

        return [
            'user_id' => User::factory()->create(['user_type' => 'worker'])->id,
            'period_type' => EarningsSummary::PERIOD_MONTHLY,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'shifts_completed' => $shiftsCompleted,
            'total_hours' => $totalHours,
            'gross_earnings' => $grossEarnings,
            'total_fees' => $totalFees,
            'total_taxes' => $totalTaxes,
            'net_earnings' => $netEarnings,
            'avg_hourly_rate' => $avgHourlyRate,
        ];
    }

    /**
     * Create a daily summary.
     */
    public function daily(?Carbon $date = null): static
    {
        $date = $date ?? now();

        return $this->state(fn (array $attributes) => [
            'period_type' => EarningsSummary::PERIOD_DAILY,
            'period_start' => $date->toDateString(),
            'period_end' => $date->toDateString(),
        ]);
    }

    /**
     * Create a weekly summary.
     */
    public function weekly(?Carbon $date = null): static
    {
        $date = $date ?? now();

        return $this->state(fn (array $attributes) => [
            'period_type' => EarningsSummary::PERIOD_WEEKLY,
            'period_start' => $date->copy()->startOfWeek()->toDateString(),
            'period_end' => $date->copy()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Create a monthly summary.
     */
    public function monthly(?Carbon $date = null): static
    {
        $date = $date ?? now();

        return $this->state(fn (array $attributes) => [
            'period_type' => EarningsSummary::PERIOD_MONTHLY,
            'period_start' => $date->copy()->startOfMonth()->toDateString(),
            'period_end' => $date->copy()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Create a yearly summary.
     */
    public function yearly(?int $year = null): static
    {
        $year = $year ?? now()->year;

        return $this->state(fn (array $attributes) => [
            'period_type' => EarningsSummary::PERIOD_YEARLY,
            'period_start' => Carbon::create($year, 1, 1)->toDateString(),
            'period_end' => Carbon::create($year, 12, 31)->toDateString(),
        ]);
    }

    /**
     * Create for a specific worker.
     */
    public function forWorker(User $worker): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $worker->id,
        ]);
    }

    /**
     * Create with specific amounts.
     */
    public function withAmounts(float $gross, float $fees, float $tax, float $net): static
    {
        return $this->state(fn (array $attributes) => [
            'gross_earnings' => $gross,
            'total_fees' => $fees,
            'total_taxes' => $tax,
            'net_earnings' => $net,
        ]);
    }
}
