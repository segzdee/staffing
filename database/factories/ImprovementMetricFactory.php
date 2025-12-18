<?php

namespace Database\Factories;

use App\Models\ImprovementMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * QUA-005: Factory for ImprovementMetric model.
 */
class ImprovementMetricFactory extends Factory
{
    protected $model = ImprovementMetric::class;

    public function definition(): array
    {
        $current = $this->faker->randomFloat(2, 0, 100);
        $baseline = $this->faker->randomFloat(2, 0, $current);
        $target = $this->faker->randomFloat(2, $current, 100);

        return [
            'metric_key' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'current_value' => $current,
            'target_value' => $target,
            'baseline_value' => $baseline,
            'trend' => $this->faker->randomElement([
                ImprovementMetric::TREND_UP,
                ImprovementMetric::TREND_DOWN,
                ImprovementMetric::TREND_STABLE,
            ]),
            'unit' => $this->faker->randomElement(['%', 'seconds', 'count', 'score', null]),
            'history' => [],
            'measured_at' => now(),
        ];
    }

    /**
     * Set trend as improving.
     */
    public function improving(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend' => ImprovementMetric::TREND_UP,
        ]);
    }

    /**
     * Set trend as declining.
     */
    public function declining(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend' => ImprovementMetric::TREND_DOWN,
        ]);
    }

    /**
     * Set trend as stable.
     */
    public function stable(): static
    {
        return $this->state(fn (array $attributes) => [
            'trend' => ImprovementMetric::TREND_STABLE,
        ]);
    }

    /**
     * Set as percentage metric.
     */
    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => '%',
            'current_value' => $this->faker->randomFloat(2, 0, 100),
            'target_value' => $this->faker->randomFloat(2, 80, 100),
            'baseline_value' => $this->faker->randomFloat(2, 0, 50),
        ]);
    }

    /**
     * Set with historical data.
     */
    public function withHistory(int $days = 30): static
    {
        $history = [];
        $value = $this->faker->randomFloat(2, 50, 70);

        for ($i = $days; $i > 0; $i--) {
            $value = max(0, min(100, $value + $this->faker->randomFloat(2, -5, 5)));
            $history[] = [
                'value' => $value,
                'recorded_at' => now()->subDays($i)->toIso8601String(),
            ];
        }

        return $this->state(fn (array $attributes) => [
            'history' => $history,
        ]);
    }

    /**
     * Set as on target.
     */
    public function onTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_value' => 95.0,
            'target_value' => 90.0,
        ]);
    }

    /**
     * Set as below target.
     */
    public function belowTarget(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_value' => 70.0,
            'target_value' => 90.0,
        ]);
    }
}
