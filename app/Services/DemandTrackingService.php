<?php

namespace App\Services;

use App\Models\DemandMetric;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SL-008: Demand Tracking Service
 *
 * Tracks and calculates demand metrics for surge pricing calculations.
 * Records shift demand, calculates daily metrics, and provides demand predictions.
 */
class DemandTrackingService
{
    /**
     * Record demand when a shift is posted.
     *
     * Called when a new shift is created to update demand metrics.
     */
    public function recordShiftDemand(Shift $shift): void
    {
        $region = $shift->location_city ?? $shift->location_state ?? $shift->location_country;
        $skill = $shift->role_type ?? ($shift->required_skills[0] ?? null);
        $date = $shift->shift_date instanceof Carbon ? $shift->shift_date : Carbon::parse($shift->shift_date);

        try {
            DB::transaction(function () use ($date, $region, $skill) {
                // Update overall regional metric
                $this->incrementShiftsPosted($date, $region, null);

                // Update skill-specific metric if skill is defined
                if ($skill) {
                    $this->incrementShiftsPosted($date, $region, $skill);
                }

                // Also update global metrics (no region)
                $this->incrementShiftsPosted($date, null, null);
            });
        } catch (\Exception $e) {
            Log::error('Failed to record shift demand', [
                'shift_id' => $shift->id,
                'region' => $region,
                'skill' => $skill,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record when a shift is filled.
     */
    public function recordShiftFilled(Shift $shift): void
    {
        $region = $shift->location_city ?? $shift->location_state ?? $shift->location_country;
        $skill = $shift->role_type ?? ($shift->required_skills[0] ?? null);
        $date = $shift->shift_date instanceof Carbon ? $shift->shift_date : Carbon::parse($shift->shift_date);

        try {
            DB::transaction(function () use ($date, $region, $skill) {
                // Update overall regional metric
                $this->incrementShiftsFilled($date, $region, null);

                // Update skill-specific metric if skill is defined
                if ($skill) {
                    $this->incrementShiftsFilled($date, $region, $skill);
                }

                // Also update global metrics
                $this->incrementShiftsFilled($date, null, null);
            });
        } catch (\Exception $e) {
            Log::error('Failed to record shift filled', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Increment shifts_posted counter for a metric.
     */
    protected function incrementShiftsPosted(Carbon $date, ?string $region, ?string $skill): void
    {
        $metric = DemandMetric::getOrCreateFor($date, $region, $skill);
        $metric->increment('shifts_posted');
        $this->recalculateMetricRates($metric);
    }

    /**
     * Increment shifts_filled counter for a metric.
     */
    protected function incrementShiftsFilled(Carbon $date, ?string $region, ?string $skill): void
    {
        $metric = DemandMetric::getOrCreateFor($date, $region, $skill);
        $metric->increment('shifts_filled');
        $this->recalculateMetricRates($metric);
    }

    /**
     * Recalculate fill rate and surge for a metric.
     */
    protected function recalculateMetricRates(DemandMetric $metric): void
    {
        $metric->refresh();

        // Calculate fill rate
        if ($metric->shifts_posted > 0) {
            $metric->fill_rate = ($metric->shifts_filled / $metric->shifts_posted) * 100;
        } else {
            $metric->fill_rate = 0;
        }

        // Calculate supply/demand ratio
        if ($metric->workers_available > 0 && $metric->shifts_posted > 0) {
            $metric->supply_demand_ratio = $metric->workers_available / $metric->shifts_posted;
        } else {
            $metric->supply_demand_ratio = 1;
        }

        // Update calculated surge
        $metric->calculated_surge = $metric->calculateSurgeMultiplier();
        $metric->save();
    }

    /**
     * Calculate daily metrics for a given date.
     *
     * This is typically run via scheduled command at the end of each day
     * or at the start of the next day for the previous day.
     */
    public function calculateDailyMetrics(Carbon $date): void
    {
        Log::info('Calculating daily demand metrics', ['date' => $date->toDateString()]);

        // Get all regions that had shifts on this date
        $regions = Shift::query()
            ->whereDate('shift_date', $date)
            ->select('location_city', 'location_state', 'location_country')
            ->distinct()
            ->get()
            ->map(function ($shift) {
                return $shift->location_city ?? $shift->location_state ?? $shift->location_country;
            })
            ->filter()
            ->unique()
            ->values();

        // Add null for global metrics
        $regions->push(null);

        foreach ($regions as $region) {
            $this->calculateMetricsForRegion($date, $region);
        }

        Log::info('Daily demand metrics calculated', [
            'date' => $date->toDateString(),
            'regions_processed' => $regions->count(),
        ]);
    }

    /**
     * Calculate metrics for a specific region.
     */
    protected function calculateMetricsForRegion(Carbon $date, ?string $region): void
    {
        // Build base query for shifts in this region
        $shiftsQuery = Shift::query()->whereDate('shift_date', $date);

        if ($region) {
            $shiftsQuery->where(function ($q) use ($region) {
                $q->where('location_city', $region)
                    ->orWhere('location_state', $region)
                    ->orWhere('location_country', $region);
            });
        }

        // Get overall stats
        $shiftsPosted = $shiftsQuery->count();
        $shiftsFilled = (clone $shiftsQuery)->where('status', 'filled')->count();
        $workersAvailable = $this->countAvailableWorkers($date, $region);

        // Update overall regional metric
        $metric = DemandMetric::getOrCreateFor($date, $region, null);
        $metric->update([
            'shifts_posted' => $shiftsPosted,
            'shifts_filled' => $shiftsFilled,
            'workers_available' => $workersAvailable,
        ]);
        $this->recalculateMetricRates($metric);

        // Calculate skill-specific metrics
        $skills = (clone $shiftsQuery)
            ->whereNotNull('role_type')
            ->distinct()
            ->pluck('role_type');

        foreach ($skills as $skill) {
            $skillShiftsQuery = (clone $shiftsQuery)->where('role_type', $skill);
            $skillShiftsPosted = $skillShiftsQuery->count();
            $skillShiftsFilled = (clone $skillShiftsQuery)->where('status', 'filled')->count();
            $skillWorkersAvailable = $this->countAvailableWorkers($date, $region, $skill);

            $skillMetric = DemandMetric::getOrCreateFor($date, $region, $skill);
            $skillMetric->update([
                'shifts_posted' => $skillShiftsPosted,
                'shifts_filled' => $skillShiftsFilled,
                'workers_available' => $skillWorkersAvailable,
            ]);
            $this->recalculateMetricRates($skillMetric);
        }
    }

    /**
     * Count available workers for a given date, region, and optionally skill.
     */
    protected function countAvailableWorkers(Carbon $date, ?string $region, ?string $skill = null): int
    {
        $query = User::query()
            ->where('user_type', 'worker')
            ->where('status', 'active');

        // Filter by region if specified
        if ($region) {
            $query->whereHas('workerProfile', function ($q) use ($region) {
                $q->where('city', $region)
                    ->orWhere('state', $region)
                    ->orWhere('country', $region);
            });
        }

        // Filter by skill if specified
        if ($skill) {
            $query->whereHas('workerSkills.skill', function ($q) use ($skill) {
                $q->where('name', 'like', "%{$skill}%")
                    ->orWhere('category', 'like', "%{$skill}%");
            });
        }

        // Check availability schedule
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        $query->where(function ($q) use ($dayOfWeek) {
            // Workers with availability on this day
            $q->whereHas('availabilitySchedules', function ($aq) use ($dayOfWeek) {
                $aq->where('is_available', true)
                    ->where('day_of_week', $dayOfWeek);
            })
                // Or workers without any schedule set (assume available)
                ->orWhereDoesntHave('availabilitySchedules');
        });

        // Exclude workers with blackout dates
        $query->whereDoesntHave('blackoutDates', function ($bq) use ($date) {
            $bq->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        });

        return $query->count();
    }

    /**
     * Predict demand for a future date.
     *
     * Uses historical data to predict demand patterns.
     *
     * @return array{
     *   predicted_shifts: int,
     *   predicted_fill_rate: float,
     *   predicted_surge: float,
     *   confidence: string,
     *   based_on_days: int
     * }
     */
    public function predictDemand(Carbon $date, string $region): array
    {
        // Look at the same day of week from the past 4 weeks
        $historicalDates = collect();
        for ($i = 1; $i <= 4; $i++) {
            $historicalDates->push($date->copy()->subWeeks($i));
        }

        $historicalMetrics = DemandMetric::query()
            ->forRegion($region)
            ->whereNull('skill_category')
            ->whereIn('metric_date', $historicalDates->map->toDateString())
            ->get();

        if ($historicalMetrics->isEmpty()) {
            return [
                'predicted_shifts' => 0,
                'predicted_fill_rate' => 0,
                'predicted_surge' => 1.0,
                'confidence' => 'low',
                'based_on_days' => 0,
            ];
        }

        // Calculate weighted average (more recent = higher weight)
        $totalWeight = 0;
        $weightedShifts = 0;
        $weightedFillRate = 0;
        $weightedSurge = 0;

        foreach ($historicalMetrics as $index => $metric) {
            $weight = 4 - $index; // 4, 3, 2, 1
            $totalWeight += $weight;
            $weightedShifts += $metric->shifts_posted * $weight;
            $weightedFillRate += $metric->fill_rate * $weight;
            $weightedSurge += $metric->calculated_surge * $weight;
        }

        $predictedShifts = $totalWeight > 0 ? round($weightedShifts / $totalWeight) : 0;
        $predictedFillRate = $totalWeight > 0 ? round($weightedFillRate / $totalWeight, 2) : 0;
        $predictedSurge = $totalWeight > 0 ? round($weightedSurge / $totalWeight, 2) : 1.0;

        // Determine confidence level
        $confidence = match (true) {
            $historicalMetrics->count() >= 4 => 'high',
            $historicalMetrics->count() >= 2 => 'medium',
            default => 'low',
        };

        return [
            'predicted_shifts' => $predictedShifts,
            'predicted_fill_rate' => $predictedFillRate,
            'predicted_surge' => $predictedSurge,
            'confidence' => $confidence,
            'based_on_days' => $historicalMetrics->count(),
        ];
    }

    /**
     * Get a demand heatmap for a region over specified days.
     *
     * @return array<string, array{
     *   date: string,
     *   shifts_posted: int,
     *   shifts_filled: int,
     *   fill_rate: float,
     *   calculated_surge: float,
     *   demand_level: string
     * }>
     */
    public function getDemandHeatmap(string $region, int $days = 7): array
    {
        $endDate = now();
        $startDate = now()->subDays($days - 1);

        $metrics = DemandMetric::query()
            ->forRegion($region)
            ->whereNull('skill_category')
            ->dateRange($startDate, $endDate)
            ->orderBy('metric_date')
            ->get()
            ->keyBy(fn ($m) => $m->metric_date->toDateString());

        $heatmap = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $metric = $metrics->get($dateString);

            $heatmap[$dateString] = [
                'date' => $dateString,
                'day_name' => $currentDate->format('l'),
                'shifts_posted' => $metric?->shifts_posted ?? 0,
                'shifts_filled' => $metric?->shifts_filled ?? 0,
                'fill_rate' => $metric?->fill_rate ?? 0,
                'calculated_surge' => $metric?->calculated_surge ?? 1.0,
                'demand_level' => $this->getDemandLevel($metric?->fill_rate ?? 100),
            ];

            $currentDate->addDay();
        }

        return $heatmap;
    }

    /**
     * Get demand level label based on fill rate.
     */
    protected function getDemandLevel(?float $fillRate): string
    {
        if ($fillRate === null) {
            return 'unknown';
        }

        return match (true) {
            $fillRate >= 80 => 'low',
            $fillRate >= 60 => 'moderate',
            $fillRate >= 40 => 'high',
            default => 'critical',
        };
    }

    /**
     * Get surge forecast for the next N days.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getSurgeForecast(?string $region, int $days = 7): array
    {
        $forecast = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->addDays($i);
            $dateString = $date->toDateString();

            // Get prediction
            $prediction = $this->predictDemand($date, $region ?? '');

            // Check for events
            $events = \App\Models\SurgeEvent::getActiveEventsFor($date, $region);

            $forecast[$dateString] = [
                'date' => $dateString,
                'day_name' => $date->format('l'),
                'predicted_demand' => $prediction,
                'events' => $events->map(fn ($e) => [
                    'name' => $e->name,
                    'type' => $e->event_type,
                    'multiplier' => $e->surge_multiplier,
                ])->toArray(),
                'estimated_surge' => max(
                    $prediction['predicted_surge'],
                    $events->max('surge_multiplier') ?? 1.0
                ),
            ];
        }

        return $forecast;
    }
}
