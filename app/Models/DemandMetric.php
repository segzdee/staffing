<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * SL-008: Surge Pricing - Demand-based metrics model
 *
 * @property int $id
 * @property string|null $region
 * @property string|null $skill_category
 * @property \Illuminate\Support\Carbon $metric_date
 * @property int $shifts_posted
 * @property int $shifts_filled
 * @property int $workers_available
 * @property float $fill_rate
 * @property float $supply_demand_ratio
 * @property float $calculated_surge
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DemandMetric extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'region',
        'skill_category',
        'metric_date',
        'shifts_posted',
        'shifts_filled',
        'workers_available',
        'fill_rate',
        'supply_demand_ratio',
        'calculated_surge',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metric_date' => 'date',
        'shifts_posted' => 'integer',
        'shifts_filled' => 'integer',
        'workers_available' => 'integer',
        'fill_rate' => 'decimal:2',
        'supply_demand_ratio' => 'decimal:2',
        'calculated_surge' => 'decimal:2',
    ];

    /**
     * Scope to get metrics for a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Carbon\Carbon|string  $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        $date = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('metric_date', $date);
    }

    /**
     * Scope to get metrics for a specific region.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRegion($query, ?string $region)
    {
        if (empty($region)) {
            return $query->whereNull('region');
        }

        return $query->where('region', $region);
    }

    /**
     * Scope to get metrics for a specific skill category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSkill($query, ?string $skill)
    {
        if (empty($skill)) {
            return $query->whereNull('skill_category');
        }

        return $query->where('skill_category', $skill);
    }

    /**
     * Scope to get metrics within a date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Carbon\Carbon|string  $startDate
     * @param  \Carbon\Carbon|string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;

        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    /**
     * Calculate the surge multiplier based on fill rate.
     * Uses configurable thresholds from config/overtimestaff.php
     */
    public function calculateSurgeMultiplier(): float
    {
        $thresholds = Config::get('overtimestaff.surge.demand_based.thresholds', [
            ['ratio' => 0.8, 'multiplier' => 1.0],
            ['ratio' => 0.6, 'multiplier' => 1.2],
            ['ratio' => 0.4, 'multiplier' => 1.5],
            ['ratio' => 0.0, 'multiplier' => 2.0],
        ]);

        $fillRate = $this->fill_rate / 100; // Convert percentage to decimal

        // Sort thresholds by ratio descending to check from highest to lowest
        usort($thresholds, fn ($a, $b) => $b['ratio'] <=> $a['ratio']);

        foreach ($thresholds as $threshold) {
            if ($fillRate >= $threshold['ratio']) {
                return (float) $threshold['multiplier'];
            }
        }

        // Return the lowest threshold multiplier as fallback
        return (float) end($thresholds)['multiplier'];
    }

    /**
     * Update the calculated_surge field based on current metrics.
     */
    public function updateCalculatedSurge(): self
    {
        $this->calculated_surge = $this->calculateSurgeMultiplier();
        $this->save();

        return $this;
    }

    /**
     * Get demand metric for a specific date, region, and skill.
     * Creates a new record if none exists.
     */
    public static function getOrCreateFor(Carbon $date, ?string $region, ?string $skill): self
    {
        return static::firstOrCreate(
            [
                'metric_date' => $date->toDateString(),
                'region' => $region,
                'skill_category' => $skill,
            ],
            [
                'shifts_posted' => 0,
                'shifts_filled' => 0,
                'workers_available' => 0,
                'fill_rate' => 0,
                'supply_demand_ratio' => 1,
                'calculated_surge' => 1,
            ]
        );
    }

    /**
     * Get the calculated surge for a specific date/region/skill combination.
     */
    public static function getSurgeFor(Carbon $date, ?string $region, ?string $skill): float
    {
        $metric = static::query()
            ->forDate($date)
            ->forRegion($region)
            ->forSkill($skill)
            ->first();

        if ($metric) {
            return (float) $metric->calculated_surge;
        }

        // Try without skill specificity
        if ($skill) {
            $metric = static::query()
                ->forDate($date)
                ->forRegion($region)
                ->whereNull('skill_category')
                ->first();

            if ($metric) {
                return (float) $metric->calculated_surge;
            }
        }

        // Try without region specificity
        if ($region) {
            $metric = static::query()
                ->forDate($date)
                ->whereNull('region')
                ->forSkill($skill)
                ->first();

            if ($metric) {
                return (float) $metric->calculated_surge;
            }
        }

        // Default to no surge
        return 1.0;
    }

    /**
     * Get demand trend for a region over the specified number of days.
     *
     * @return array<string, mixed>
     */
    public static function getDemandTrend(?string $region, int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $metrics = static::query()
            ->forRegion($region)
            ->whereNull('skill_category') // Get overall metrics
            ->dateRange($startDate, $endDate)
            ->orderBy('metric_date')
            ->get();

        $trend = [];
        foreach ($metrics as $metric) {
            $trend[$metric->metric_date->toDateString()] = [
                'shifts_posted' => $metric->shifts_posted,
                'shifts_filled' => $metric->shifts_filled,
                'fill_rate' => $metric->fill_rate,
                'calculated_surge' => $metric->calculated_surge,
            ];
        }

        return [
            'region' => $region ?? 'Global',
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'data' => $trend,
            'average_fill_rate' => $metrics->avg('fill_rate') ?? 0,
            'average_surge' => $metrics->avg('calculated_surge') ?? 1,
        ];
    }

    /**
     * Get a demand heatmap for multiple regions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getDemandHeatmap(int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $metrics = static::query()
            ->whereNull('skill_category')
            ->dateRange($startDate, $endDate)
            ->get()
            ->groupBy('region');

        $heatmap = [];
        foreach ($metrics as $region => $regionMetrics) {
            $regionName = $region ?: 'Global';
            $heatmap[$regionName] = [
                'total_shifts_posted' => $regionMetrics->sum('shifts_posted'),
                'total_shifts_filled' => $regionMetrics->sum('shifts_filled'),
                'average_fill_rate' => $regionMetrics->avg('fill_rate'),
                'average_surge' => $regionMetrics->avg('calculated_surge'),
                'max_surge' => $regionMetrics->max('calculated_surge'),
                'days_with_data' => $regionMetrics->count(),
            ];
        }

        return $heatmap;
    }

    /**
     * Get skill-specific demand analysis.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getSkillDemandAnalysis(?string $region, int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $query = static::query()
            ->whereNotNull('skill_category')
            ->dateRange($startDate, $endDate);

        if ($region) {
            $query->forRegion($region);
        }

        $metrics = $query->get()->groupBy('skill_category');

        $analysis = [];
        foreach ($metrics as $skill => $skillMetrics) {
            $analysis[$skill] = [
                'total_shifts_posted' => $skillMetrics->sum('shifts_posted'),
                'total_shifts_filled' => $skillMetrics->sum('shifts_filled'),
                'average_fill_rate' => $skillMetrics->avg('fill_rate'),
                'average_surge' => $skillMetrics->avg('calculated_surge'),
                'shortage_days' => $skillMetrics->where('fill_rate', '<', 50)->count(),
            ];
        }

        // Sort by shortage (highest shortage first)
        uasort($analysis, fn ($a, $b) => $b['shortage_days'] <=> $a['shortage_days']);

        return $analysis;
    }
}
