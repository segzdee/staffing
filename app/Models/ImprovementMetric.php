<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QUA-005: Continuous Improvement System
 * Model for tracking platform performance metrics.
 *
 * @property int $id
 * @property string $metric_key
 * @property string $name
 * @property string|null $description
 * @property float $current_value
 * @property float|null $target_value
 * @property float|null $baseline_value
 * @property string $trend
 * @property string|null $unit
 * @property array|null $history
 * @property \Illuminate\Support\Carbon|null $measured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ImprovementMetric extends Model
{
    use HasFactory;

    public const TREND_UP = 'up';

    public const TREND_DOWN = 'down';

    public const TREND_STABLE = 'stable';

    // Common metric keys
    public const METRIC_SHIFT_FILL_RATE = 'shift_fill_rate';

    public const METRIC_AVG_RESPONSE_TIME = 'avg_response_time';

    public const METRIC_WORKER_SATISFACTION = 'worker_satisfaction';

    public const METRIC_BUSINESS_RETENTION = 'business_retention';

    public const METRIC_DISPUTE_RESOLUTION_TIME = 'dispute_resolution_time';

    public const METRIC_PLATFORM_HEALTH = 'platform_health_score';

    public const METRIC_WORKER_RETENTION = 'worker_retention';

    public const METRIC_AVG_RATING = 'avg_rating';

    public const METRIC_CANCELLATION_RATE = 'cancellation_rate';

    public const METRIC_PAYMENT_SUCCESS_RATE = 'payment_success_rate';

    protected $fillable = [
        'metric_key',
        'name',
        'description',
        'current_value',
        'target_value',
        'baseline_value',
        'trend',
        'unit',
        'history',
        'measured_at',
    ];

    protected $casts = [
        'current_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'baseline_value' => 'decimal:4',
        'history' => 'array',
        'measured_at' => 'datetime',
    ];

    /**
     * Get all available trends.
     */
    public static function getTrends(): array
    {
        return [
            self::TREND_UP => 'Improving',
            self::TREND_DOWN => 'Declining',
            self::TREND_STABLE => 'Stable',
        ];
    }

    /**
     * Record a new value for this metric.
     */
    public function recordValue(float $value): void
    {
        // Add current value to history before updating
        $history = $this->history ?? [];
        $history[] = [
            'value' => $this->current_value,
            'recorded_at' => $this->measured_at?->toIso8601String() ?? now()->toIso8601String(),
        ];

        // Keep only last 365 entries
        if (count($history) > 365) {
            $history = array_slice($history, -365);
        }

        // Calculate trend based on recent values
        $trend = $this->calculateTrend($value);

        $this->update([
            'current_value' => $value,
            'history' => $history,
            'trend' => $trend,
            'measured_at' => now(),
        ]);
    }

    /**
     * Calculate the trend based on the new value.
     */
    protected function calculateTrend(float $newValue): string
    {
        $history = $this->history ?? [];

        if (count($history) < 3) {
            return self::TREND_STABLE;
        }

        // Get the last 7 values for trend calculation
        $recentHistory = array_slice($history, -7);
        $recentValues = array_column($recentHistory, 'value');
        $avgRecent = count($recentValues) > 0 ? array_sum($recentValues) / count($recentValues) : $this->current_value;

        // Calculate percentage change
        $change = $avgRecent > 0 ? (($newValue - $avgRecent) / $avgRecent) * 100 : 0;

        // Threshold of 5% for trend change
        if ($change > 5) {
            return self::TREND_UP;
        } elseif ($change < -5) {
            return self::TREND_DOWN;
        }

        return self::TREND_STABLE;
    }

    /**
     * Get the trend for the last N days.
     */
    public function getTrendForDays(int $days = 30): array
    {
        $history = $this->history ?? [];
        $cutoff = now()->subDays($days);

        $filteredHistory = array_filter($history, function ($entry) use ($cutoff) {
            $recordedAt = isset($entry['recorded_at'])
                ? \Carbon\Carbon::parse($entry['recorded_at'])
                : null;

            return $recordedAt && $recordedAt->gte($cutoff);
        });

        return array_values($filteredHistory);
    }

    /**
     * Get the average value for the last N days.
     */
    public function getAverageForDays(int $days = 30): float
    {
        $trendData = $this->getTrendForDays($days);

        if (empty($trendData)) {
            return $this->current_value;
        }

        $values = array_column($trendData, 'value');

        return array_sum($values) / count($values);
    }

    /**
     * Get the progress towards target as a percentage.
     */
    public function getProgressPercentage(): ?float
    {
        if (! $this->target_value || ! $this->baseline_value) {
            return null;
        }

        $range = $this->target_value - $this->baseline_value;

        if ($range == 0) {
            return 100;
        }

        $progress = ($this->current_value - $this->baseline_value) / $range * 100;

        return min(100, max(0, $progress));
    }

    /**
     * Check if the metric is on target.
     */
    public function isOnTarget(): bool
    {
        if (! $this->target_value) {
            return true;
        }

        return $this->current_value >= $this->target_value;
    }

    /**
     * Get the formatted value with unit.
     */
    public function getFormattedValueAttribute(): string
    {
        $value = number_format($this->current_value, 2);

        if ($this->unit) {
            if ($this->unit === '%') {
                return $value.'%';
            }

            return $value.' '.$this->unit;
        }

        return $value;
    }

    /**
     * Get the trend icon class.
     */
    public function getTrendIconAttribute(): string
    {
        return match ($this->trend) {
            self::TREND_UP => 'fas fa-arrow-up text-green-500',
            self::TREND_DOWN => 'fas fa-arrow-down text-red-500',
            default => 'fas fa-minus text-gray-500',
        };
    }

    /**
     * Get the trend color class.
     */
    public function getTrendColorAttribute(): string
    {
        return match ($this->trend) {
            self::TREND_UP => 'text-green-600',
            self::TREND_DOWN => 'text-red-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Scope: Find by metric key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('metric_key', $key);
    }

    /**
     * Scope: Filter by trend.
     */
    public function scopeWithTrend($query, string $trend)
    {
        return $query->where('trend', $trend);
    }

    /**
     * Scope: Metrics that are below target.
     */
    public function scopeBelowTarget($query)
    {
        return $query->whereNotNull('target_value')
            ->whereColumn('current_value', '<', 'target_value');
    }

    /**
     * Scope: Metrics that are on or above target.
     */
    public function scopeOnTarget($query)
    {
        return $query->whereNotNull('target_value')
            ->whereColumn('current_value', '>=', 'target_value');
    }

    /**
     * Get or create a metric by key.
     */
    public static function findOrCreateByKey(string $key, array $attributes = []): self
    {
        return static::firstOrCreate(
            ['metric_key' => $key],
            array_merge([
                'name' => ucwords(str_replace('_', ' ', $key)),
                'current_value' => 0,
                'trend' => self::TREND_STABLE,
            ], $attributes)
        );
    }
}
