<?php

namespace App\Services;

use App\Models\AvailabilityPattern;
use App\Models\AvailabilityPrediction;
use App\Models\DemandForecast;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerAvailabilitySchedule;
use App\Models\WorkerBlackoutDate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WKR-013: Availability Forecasting Service
 *
 * ML-based availability prediction system using statistical methods:
 * - Weighted averages
 * - Moving averages
 * - Historical pattern analysis
 */
class AvailabilityForecastingService
{
    /**
     * Weights for different prediction factors.
     */
    protected const WEIGHTS = [
        'historical_pattern' => 0.35,
        'recent_acceptance_rate' => 0.25,
        'explicit_availability' => 0.20,
        'seasonal_adjustment' => 0.10,
        'day_of_week_adjustment' => 0.10,
    ];

    /**
     * Number of weeks to look back for recent data.
     */
    protected const RECENT_WEEKS = 4;

    /**
     * Number of weeks to look back for historical patterns.
     */
    protected const HISTORICAL_WEEKS = 12;

    /**
     * Prediction horizon in days.
     */
    protected const PREDICTION_HORIZON = 14;

    // =========================================
    // Pattern Analysis
    // =========================================

    /**
     * Analyze historical data to build worker patterns.
     */
    public function analyzeWorkerPatterns(User $worker): void
    {
        Log::info("Analyzing patterns for worker {$worker->id}");

        // Get historical shift assignments for this worker
        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->whereIn('status', ['completed', 'checked_out', 'assigned', 'checked_in'])
            ->whereHas('shift', function ($query) {
                $query->where('shift_date', '>=', Carbon::now()->subWeeks(self::HISTORICAL_WEEKS));
            })
            ->with('shift')
            ->get();

        // Group by day of week
        $dayData = [];
        for ($day = 0; $day <= 6; $day++) {
            $dayData[$day] = [
                'shifts' => 0,
                'available' => 0,
                'start_times' => [],
                'end_times' => [],
            ];
        }

        foreach ($assignments as $assignment) {
            if (! $assignment->shift) {
                continue;
            }

            $dayOfWeek = $assignment->shift->shift_date->dayOfWeek;
            $dayData[$dayOfWeek]['shifts']++;
            $dayData[$dayOfWeek]['available']++;

            if ($assignment->shift->start_time) {
                $dayData[$dayOfWeek]['start_times'][] = $assignment->shift->start_time;
            }
            if ($assignment->shift->end_time) {
                $dayData[$dayOfWeek]['end_times'][] = $assignment->shift->end_time;
            }
        }

        // Also analyze explicit availability schedules
        $schedules = WorkerAvailabilitySchedule::where('worker_id', $worker->id)
            ->active()
            ->get();

        foreach ($schedules as $schedule) {
            $dayOfWeek = $this->convertDayNameToNumber($schedule->day_of_week);
            if ($dayOfWeek !== null) {
                // Boost the pattern data for days with explicit availability
                $dayData[$dayOfWeek]['explicit'] = true;
                $dayData[$dayOfWeek]['explicit_start'] = $schedule->start_time;
                $dayData[$dayOfWeek]['explicit_end'] = $schedule->end_time;
            }
        }

        // Update or create patterns for each day
        foreach ($dayData as $dayOfWeek => $data) {
            $probability = $data['shifts'] > 0
                ? $data['available'] / max($data['shifts'], 1)
                : 0;

            // If explicit availability is set, boost probability
            if (isset($data['explicit']) && $data['explicit']) {
                $probability = max($probability, 0.7);
            }

            // Calculate typical times
            $typicalStart = $this->calculateAverageTime($data['start_times']);
            $typicalEnd = $this->calculateAverageTime($data['end_times']);

            // Override with explicit times if available
            if (isset($data['explicit_start'])) {
                $typicalStart = $data['explicit_start'];
            }
            if (isset($data['explicit_end'])) {
                $typicalEnd = $data['explicit_end'];
            }

            AvailabilityPattern::updateOrCreate(
                [
                    'user_id' => $worker->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'typical_start_time' => $typicalStart,
                    'typical_end_time' => $typicalEnd,
                    'availability_probability' => min($probability, 1.0),
                    'historical_shifts_count' => $data['shifts'],
                    'historical_available_count' => $data['available'],
                ]
            );
        }

        Log::info("Completed pattern analysis for worker {$worker->id}");
    }

    // =========================================
    // Availability Prediction
    // =========================================

    /**
     * Predict availability for a worker on a specific date.
     */
    public function predictAvailability(User $worker, Carbon $date): AvailabilityPrediction
    {
        $dayOfWeek = $date->dayOfWeek;
        $factors = [];

        // 1. Get historical pattern probability
        $pattern = AvailabilityPattern::forUser($worker->id)
            ->forDay($dayOfWeek)
            ->first();

        $patternProb = $pattern ? $pattern->availability_probability : 0.5;
        $factors['historical_pattern'] = $patternProb;

        // 2. Calculate recent acceptance rate
        $recentAcceptance = $this->calculateRecentAcceptanceRate($worker);
        $factors['recent_acceptance_rate'] = $recentAcceptance;

        // 3. Check explicit availability
        $explicitAvail = $this->checkExplicitAvailability($worker, $date);
        $factors['explicit_availability'] = $explicitAvail;

        // 4. Apply seasonal adjustments
        $seasonalFactor = $this->getSeasonalAdjustment($date);
        $factors['seasonal_adjustment'] = $seasonalFactor;

        // 5. Day of week adjustment (weekends typically different)
        $dayFactor = $this->getDayOfWeekAdjustment($dayOfWeek);
        $factors['day_of_week_adjustment'] = $dayFactor;

        // Calculate weighted overall probability
        $overallProb = $this->calculateWeightedProbability($factors);

        // Calculate time-slot probabilities
        $slotProbs = $this->calculateSlotProbabilities($worker, $date, $pattern);

        // Check blackout dates (overrides everything)
        $blackout = WorkerBlackoutDate::where('worker_id', $worker->id)
            ->forDateRange($date, $date)
            ->first();

        if ($blackout) {
            $overallProb = 0;
            $slotProbs = [
                'morning' => 0,
                'afternoon' => 0,
                'evening' => 0,
                'night' => 0,
            ];
            $factors['blackout'] = true;
        }

        // Create or update prediction
        $prediction = AvailabilityPrediction::updateOrCreate(
            [
                'user_id' => $worker->id,
                'prediction_date' => $date->toDateString(),
            ],
            [
                'morning_probability' => $slotProbs['morning'],
                'afternoon_probability' => $slotProbs['afternoon'],
                'evening_probability' => $slotProbs['evening'],
                'night_probability' => $slotProbs['night'],
                'overall_probability' => $overallProb,
                'factors' => $factors,
            ]
        );

        return $prediction;
    }

    /**
     * Predict availability for multiple workers on a date.
     */
    public function predictBulkAvailability(Collection $workers, Carbon $date): Collection
    {
        $predictions = collect();

        foreach ($workers as $worker) {
            $predictions->push($this->predictAvailability($worker, $date));
        }

        return $predictions;
    }

    /**
     * Get workers likely available on a date for a time slot.
     */
    public function getAvailableWorkers(Carbon $date, string $timeSlot): Collection
    {
        $column = $timeSlot.'_probability';

        return AvailabilityPrediction::forDate($date)
            ->where($column, '>=', 0.5)
            ->with('user')
            ->orderBy($column, 'desc')
            ->get()
            ->map(function ($prediction) {
                return $prediction->user;
            })
            ->filter();
    }

    /**
     * Calculate pattern probability for a worker on a day.
     */
    public function calculatePatternProbability(User $worker, int $dayOfWeek): float
    {
        $pattern = AvailabilityPattern::forUser($worker->id)
            ->forDay($dayOfWeek)
            ->first();

        if (! $pattern) {
            // No pattern data, return neutral probability
            return 0.5;
        }

        if (! $pattern->isReliable()) {
            // Not enough data, blend with default
            return ($pattern->availability_probability + 0.5) / 2;
        }

        return $pattern->availability_probability;
    }

    // =========================================
    // Demand Forecasting
    // =========================================

    /**
     * Forecast demand for a date, optionally filtered by region/skill.
     */
    public function forecastDemand(Carbon $date, ?string $region = null, ?string $skill = null): DemandForecast
    {
        $factors = [];

        // 1. Historical demand from shifts
        $historicalDemand = $this->calculateHistoricalDemand($date, $region, $skill);
        $factors['historical_demand'] = $historicalDemand;

        // 2. Calculate predicted supply
        $predictedSupply = $this->calculatePredictedSupply($date, $region, $skill);
        $factors['predicted_supply'] = $predictedSupply;

        // 3. Day of week adjustment
        $dayFactor = $this->getDemandDayAdjustment($date->dayOfWeek);
        $factors['day_adjustment'] = $dayFactor;

        // 4. Seasonal adjustment
        $seasonalFactor = $this->getDemandSeasonalAdjustment($date);
        $factors['seasonal_adjustment'] = $seasonalFactor;

        // Calculate final predicted demand
        $predictedDemand = (int) round($historicalDemand * $dayFactor * $seasonalFactor);

        // Calculate ratio and level
        $ratio = $predictedDemand > 0 ? $predictedSupply / $predictedDemand : ($predictedSupply > 0 ? 999.99 : 0);
        $level = DemandForecast::calculateDemandLevel($ratio);

        // Create or update forecast
        $forecast = DemandForecast::updateOrCreate(
            [
                'forecast_date' => $date->toDateString(),
                'region' => $region,
                'skill_category' => $skill,
                'venue_id' => null,
            ],
            [
                'predicted_demand' => $predictedDemand,
                'predicted_supply' => $predictedSupply,
                'supply_demand_ratio' => min($ratio, 999.99),
                'demand_level' => $level,
                'factors' => $factors,
            ]
        );

        return $forecast;
    }

    /**
     * Get supply-demand gap analysis for a date and region.
     */
    public function getSupplyDemandGap(Carbon $date, string $region): array
    {
        $forecast = DemandForecast::forDate($date)
            ->forRegion($region)
            ->first();

        if (! $forecast) {
            $forecast = $this->forecastDemand($date, $region);
        }

        return [
            'date' => $date->toDateString(),
            'region' => $region,
            'predicted_demand' => $forecast->predicted_demand,
            'predicted_supply' => $forecast->predicted_supply,
            'gap' => $forecast->supply_gap,
            'has_shortage' => $forecast->has_shortage,
            'shortage_percent' => $forecast->shortage_percent,
            'surplus_percent' => $forecast->surplus_percent,
            'demand_level' => $forecast->demand_level,
            'recommendations' => $forecast->getRecommendations(),
        ];
    }

    /**
     * Get recommended workers for a shift based on predictions.
     */
    public function getRecommendedWorkers(Shift $shift): Collection
    {
        $date = $shift->shift_date;
        $startTime = $shift->start_time;
        $slot = AvailabilityPrediction::getSlotForHour(
            (int) Carbon::parse($startTime)->format('H')
        );

        $column = $slot.'_probability';

        // Get predictions for workers likely available
        $predictions = AvailabilityPrediction::forDate($date)
            ->where($column, '>=', 0.6)
            ->with('user.workerProfile', 'user.skills')
            ->orderBy($column, 'desc')
            ->limit(50)
            ->get();

        // Filter by shift requirements and location
        $recommended = $predictions->filter(function ($prediction) use ($shift) {
            $user = $prediction->user;

            // Must be a worker
            if (! $user || ! $user->isWorker()) {
                return false;
            }

            // Check skills match if required
            if (! empty($shift->required_skills)) {
                $workerSkills = $user->skills->pluck('id')->toArray();
                if (! array_intersect($shift->required_skills, $workerSkills)) {
                    return false;
                }
            }

            // Check if already assigned/applied
            if ($shift->applications()->where('worker_id', $user->id)->exists()) {
                return false;
            }

            return true;
        })->map(function ($prediction) {
            return [
                'user' => $prediction->user,
                'probability' => $prediction->overall_probability,
                'slot_probability' => $prediction->{$prediction->best_slot.'_probability'},
            ];
        });

        return $recommended->sortByDesc('probability')->take(20);
    }

    // =========================================
    // Accuracy Tracking
    // =========================================

    /**
     * Update prediction accuracy for past dates.
     */
    public function updatePredictionAccuracy(): void
    {
        $predictions = AvailabilityPrediction::needingAccuracyUpdate()
            ->with('user')
            ->limit(500)
            ->get();

        foreach ($predictions as $prediction) {
            $wasActuallyAvailable = $this->checkActualAvailability(
                $prediction->user,
                $prediction->prediction_date
            );

            $predictedAvailable = $prediction->overall_probability >= 0.5;
            $wasAccurate = ($wasActuallyAvailable && $predictedAvailable)
                || (! $wasActuallyAvailable && ! $predictedAvailable);

            $prediction->markAccuracy($wasAccurate);
        }

        Log::info("Updated accuracy for {$predictions->count()} predictions");
    }

    // =========================================
    // Bulk Operations
    // =========================================

    /**
     * Generate predictions for all active workers for the next N days.
     */
    public function generateDailyPredictions(?int $days = null): array
    {
        $days = $days ?? self::PREDICTION_HORIZON;
        $results = [
            'workers_processed' => 0,
            'predictions_created' => 0,
            'forecasts_created' => 0,
            'errors' => [],
        ];

        // Get active workers
        $workers = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->get();

        $results['workers_processed'] = $workers->count();

        // Generate predictions for each worker
        foreach ($workers as $worker) {
            try {
                // First analyze patterns if not recently done
                $pattern = AvailabilityPattern::forUser($worker->id)->first();
                if (! $pattern || $pattern->updated_at->lt(Carbon::now()->subDays(7))) {
                    $this->analyzeWorkerPatterns($worker);
                }

                // Generate predictions for next N days
                for ($i = 0; $i < $days; $i++) {
                    $date = Carbon::today()->addDays($i);
                    $this->predictAvailability($worker, $date);
                    $results['predictions_created']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Worker {$worker->id}: ".$e->getMessage();
                Log::error("Prediction error for worker {$worker->id}: ".$e->getMessage());
            }
        }

        // Generate demand forecasts by region
        $regions = $this->getActiveRegions();
        foreach ($regions as $region) {
            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::today()->addDays($i);
                try {
                    $this->forecastDemand($date, $region);
                    $results['forecasts_created']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Forecast {$region}/{$date->toDateString()}: ".$e->getMessage();
                }
            }
        }

        return $results;
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Calculate weighted probability from factors.
     */
    protected function calculateWeightedProbability(array $factors): float
    {
        $weighted = 0;
        $totalWeight = 0;

        foreach (self::WEIGHTS as $factor => $weight) {
            if (isset($factors[$factor])) {
                $weighted += $factors[$factor] * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? min($weighted / $totalWeight, 1.0) : 0.5;
    }

    /**
     * Calculate recent acceptance rate for a worker.
     */
    protected function calculateRecentAcceptanceRate(User $worker): float
    {
        $recentWeeks = Carbon::now()->subWeeks(self::RECENT_WEEKS);

        $invitations = DB::table('shift_invitations')
            ->where('worker_id', $worker->id)
            ->where('created_at', '>=', $recentWeeks)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted
            ')
            ->first();

        if (! $invitations || $invitations->total == 0) {
            // Check applications instead
            $applications = DB::table('shift_applications')
                ->where('worker_id', $worker->id)
                ->where('created_at', '>=', $recentWeeks)
                ->count();

            // If they're applying, they're probably available
            return $applications > 0 ? 0.7 : 0.5;
        }

        return $invitations->accepted / $invitations->total;
    }

    /**
     * Check explicit availability settings.
     */
    protected function checkExplicitAvailability(User $worker, Carbon $date): float
    {
        $dayName = strtolower($date->format('l'));

        $schedule = WorkerAvailabilitySchedule::where('worker_id', $worker->id)
            ->where('day_of_week', $dayName)
            ->where('is_available', true)
            ->active()
            ->first();

        if ($schedule) {
            return 0.9; // High confidence if explicitly set
        }

        // Check if any availability is set for the week
        $hasAnyAvailability = WorkerAvailabilitySchedule::where('worker_id', $worker->id)
            ->where('is_available', true)
            ->active()
            ->exists();

        if ($hasAnyAvailability) {
            // They set availability but not for this day
            return 0.3;
        }

        return 0.5; // Neutral
    }

    /**
     * Get seasonal adjustment factor.
     */
    protected function getSeasonalAdjustment(Carbon $date): float
    {
        $month = $date->month;

        // Holiday seasons typically have lower availability
        $seasonalFactors = [
            1 => 0.9,   // January - post-holiday
            2 => 1.0,   // February - normal
            3 => 1.0,   // March - normal
            4 => 1.0,   // April - spring break effect
            5 => 1.0,   // May - normal
            6 => 0.9,   // June - summer starts
            7 => 0.85,  // July - peak summer
            8 => 0.85,  // August - summer
            9 => 1.0,   // September - back to normal
            10 => 1.0,  // October - normal
            11 => 0.95, // November - holiday prep
            12 => 0.8,  // December - holidays
        ];

        return $seasonalFactors[$month] ?? 1.0;
    }

    /**
     * Get day of week adjustment factor.
     */
    protected function getDayOfWeekAdjustment(int $dayOfWeek): float
    {
        // Weekend availability typically different
        $dayFactors = [
            0 => 0.7,  // Sunday
            1 => 1.0,  // Monday
            2 => 1.0,  // Tuesday
            3 => 1.0,  // Wednesday
            4 => 1.0,  // Thursday
            5 => 0.95, // Friday
            6 => 0.75, // Saturday
        ];

        return $dayFactors[$dayOfWeek] ?? 1.0;
    }

    /**
     * Calculate slot probabilities based on pattern.
     */
    protected function calculateSlotProbabilities(User $worker, Carbon $date, ?AvailabilityPattern $pattern): array
    {
        $base = $pattern ? $pattern->availability_probability : 0.5;

        // Default distribution if no specific times
        $slots = [
            'morning' => $base * 0.8,
            'afternoon' => $base * 1.0,
            'evening' => $base * 0.9,
            'night' => $base * 0.3,
        ];

        // Adjust based on typical times if available
        if ($pattern && $pattern->typical_start_time && $pattern->typical_end_time) {
            $startHour = (int) Carbon::parse($pattern->typical_start_time)->format('H');
            $endHour = (int) Carbon::parse($pattern->typical_end_time)->format('H');

            // Boost probability for slots within typical hours
            foreach (AvailabilityPrediction::TIME_SLOTS as $slot => $range) {
                if ($startHour <= $range['start'] && $endHour >= $range['end']) {
                    $slots[$slot] = min($base * 1.2, 1.0);
                } elseif ($startHour < $range['end'] && $endHour > $range['start']) {
                    $slots[$slot] = $base;
                } else {
                    $slots[$slot] = $base * 0.3;
                }
            }
        }

        return $slots;
    }

    /**
     * Calculate historical demand for forecasting.
     */
    protected function calculateHistoricalDemand(Carbon $date, ?string $region, ?string $skill): int
    {
        $dayOfWeek = $date->dayOfWeek;
        $weekAgo = Carbon::now()->subWeeks(self::HISTORICAL_WEEKS);

        // Build query with database-agnostic day of week filtering
        $query = Shift::where('created_at', '>=', $weekAgo);

        // Use database-agnostic approach: query shifts and filter by day of week in PHP
        // This is more compatible across SQLite, MySQL, and PostgreSQL
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL: DAYOFWEEK returns 1 (Sunday) to 7 (Saturday)
            $query->whereRaw('DAYOFWEEK(shift_date) = ?', [$dayOfWeek + 1]);
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: EXTRACT returns 0 (Sunday) to 6 (Saturday)
            $query->whereRaw('EXTRACT(DOW FROM shift_date) = ?', [$dayOfWeek]);
        } else {
            // SQLite or others: Use strftime, returns 0 (Sunday) to 6 (Saturday)
            $query->whereRaw("CAST(strftime('%w', shift_date) AS INTEGER) = ?", [$dayOfWeek]);
        }

        if ($region) {
            $query->where(function ($q) use ($region) {
                $q->where('location_city', $region)
                    ->orWhere('location_state', $region)
                    ->orWhere('location_country', $region);
            });
        }

        if ($skill) {
            $query->whereJsonContains('required_skills', $skill);
        }

        // Sum required workers
        $totalWorkers = $query->sum('required_workers');
        $shiftCount = $query->count();

        // Average per day
        return $shiftCount > 0 ? (int) ceil($totalWorkers / max($shiftCount / self::HISTORICAL_WEEKS, 1)) : 5;
    }

    /**
     * Calculate predicted supply of workers.
     */
    protected function calculatePredictedSupply(Carbon $date, ?string $region, ?string $skill): int
    {
        $dayOfWeek = $date->dayOfWeek;

        // Get workers with high availability prediction for this date
        $query = AvailabilityPrediction::forDate($date)
            ->where('overall_probability', '>=', 0.5)
            ->with('user.workerProfile');

        $predictions = $query->get();

        $availableCount = 0;
        foreach ($predictions as $prediction) {
            $user = $prediction->user;
            if (! $user || ! $user->workerProfile) {
                continue;
            }

            // Check region match
            if ($region) {
                $profile = $user->workerProfile;
                $workerRegion = $profile->city ?? $profile->state ?? $profile->country ?? null;
                if ($workerRegion && stripos($workerRegion, $region) === false) {
                    continue;
                }
            }

            // Check skill match
            if ($skill) {
                $workerSkills = $user->skills->pluck('name')->toArray();
                if (! in_array($skill, $workerSkills)) {
                    continue;
                }
            }

            // Weight by probability
            $availableCount += $prediction->overall_probability;
        }

        return (int) round($availableCount);
    }

    /**
     * Get demand day adjustment.
     */
    protected function getDemandDayAdjustment(int $dayOfWeek): float
    {
        // Weekend typically higher demand for certain industries
        $factors = [
            0 => 1.2,  // Sunday
            1 => 0.9,  // Monday
            2 => 0.9,  // Tuesday
            3 => 0.9,  // Wednesday
            4 => 1.0,  // Thursday
            5 => 1.3,  // Friday
            6 => 1.4,  // Saturday
        ];

        return $factors[$dayOfWeek] ?? 1.0;
    }

    /**
     * Get demand seasonal adjustment.
     */
    protected function getDemandSeasonalAdjustment(Carbon $date): float
    {
        $month = $date->month;

        $factors = [
            1 => 0.8,  // January - slow
            2 => 0.9,  // February
            3 => 1.0,  // March
            4 => 1.1,  // April
            5 => 1.1,  // May
            6 => 1.2,  // June - events
            7 => 1.3,  // July - peak
            8 => 1.3,  // August
            9 => 1.1,  // September
            10 => 1.0, // October
            11 => 1.2, // November - holidays
            12 => 1.4, // December - peak holidays
        ];

        return $factors[$month] ?? 1.0;
    }

    /**
     * Check actual availability (for accuracy tracking).
     */
    protected function checkActualAvailability(User $worker, Carbon $date): bool
    {
        // Check if worker worked any shift on that date
        $worked = ShiftAssignment::where('worker_id', $worker->id)
            ->whereHas('shift', function ($query) use ($date) {
                $query->whereDate('shift_date', $date);
            })
            ->whereIn('status', ['completed', 'checked_out', 'checked_in'])
            ->exists();

        if ($worked) {
            return true;
        }

        // Check if they applied to any shift on that date
        $applied = DB::table('shift_applications')
            ->where('worker_id', $worker->id)
            ->whereDate('created_at', $date)
            ->exists();

        return $applied;
    }

    /**
     * Convert day name to number.
     */
    protected function convertDayNameToNumber(string $dayName): ?int
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $map[strtolower($dayName)] ?? null;
    }

    /**
     * Calculate average time from array of times.
     */
    protected function calculateAverageTime(array $times): ?string
    {
        if (empty($times)) {
            return null;
        }

        $totalMinutes = 0;
        foreach ($times as $time) {
            $carbon = Carbon::parse($time);
            $totalMinutes += $carbon->hour * 60 + $carbon->minute;
        }

        $avgMinutes = (int) ($totalMinutes / count($times));
        $hours = (int) ($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    /**
     * Get list of active regions.
     */
    protected function getActiveRegions(): array
    {
        return Shift::query()
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->whereNotNull('location_city')
            ->distinct()
            ->pluck('location_city')
            ->take(20)
            ->toArray();
    }
}
