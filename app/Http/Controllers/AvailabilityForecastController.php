<?php

namespace App\Http\Controllers;

use App\Models\AvailabilityPattern;
use App\Models\AvailabilityPrediction;
use App\Models\DemandForecast;
use App\Models\Shift;
use App\Models\User;
use App\Services\AvailabilityForecastingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WKR-013: Availability Forecast Controller
 *
 * Provides endpoints for viewing availability predictions,
 * demand forecasts, and supply-demand analysis.
 */
class AvailabilityForecastController extends Controller
{
    public function __construct(
        protected AvailabilityForecastingService $forecastingService
    ) {}

    // =========================================
    // Worker Patterns & Predictions
    // =========================================

    /**
     * Get availability patterns for a worker.
     */
    public function getWorkerPatterns(Request $request, int $workerId): JsonResponse
    {
        $worker = User::findOrFail($workerId);

        // Authorization check
        if (! $this->canViewWorkerData($request, $worker)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $patterns = AvailabilityPattern::forUser($workerId)
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($pattern) {
                return [
                    'day_of_week' => $pattern->day_of_week,
                    'day_name' => $pattern->day_name,
                    'typical_hours' => $pattern->typical_hours,
                    'probability' => $pattern->availability_probability,
                    'probability_percent' => $pattern->probability_percent,
                    'historical_shifts' => $pattern->historical_shifts_count,
                    'confidence' => $pattern->getConfidenceLevel(),
                ];
            });

        return response()->json([
            'worker_id' => $workerId,
            'patterns' => $patterns,
        ]);
    }

    /**
     * Get availability predictions for a worker.
     */
    public function getWorkerPredictions(Request $request, int $workerId): JsonResponse
    {
        $worker = User::findOrFail($workerId);

        if (! $this->canViewWorkerData($request, $worker)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->addDays(14)->toDateString());

        $predictions = AvailabilityPrediction::forUser($workerId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('prediction_date')
            ->get()
            ->map(function ($prediction) {
                return [
                    'date' => $prediction->prediction_date->toDateString(),
                    'day_name' => $prediction->prediction_date->format('l'),
                    'overall_probability' => $prediction->overall_probability,
                    'overall_percent' => $prediction->overall_percent,
                    'strength' => $prediction->strength_label,
                    'slots' => [
                        'morning' => $prediction->morning_probability,
                        'afternoon' => $prediction->afternoon_probability,
                        'evening' => $prediction->evening_probability,
                        'night' => $prediction->night_probability,
                    ],
                    'best_slot' => $prediction->best_slot,
                    'factors' => $prediction->factors,
                ];
            });

        return response()->json([
            'worker_id' => $workerId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'predictions' => $predictions,
        ]);
    }

    /**
     * Get prediction for a worker on a specific date.
     */
    public function getWorkerPredictionForDate(Request $request, int $workerId, string $date): JsonResponse
    {
        $worker = User::findOrFail($workerId);

        if (! $this->canViewWorkerData($request, $worker)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateCarbon = Carbon::parse($date);
        $prediction = AvailabilityPrediction::forUser($workerId)
            ->forDate($dateCarbon)
            ->first();

        if (! $prediction) {
            // Generate prediction on-demand
            $prediction = $this->forecastingService->predictAvailability($worker, $dateCarbon);
        }

        return response()->json([
            'worker_id' => $workerId,
            'date' => $date,
            'prediction' => [
                'overall_probability' => $prediction->overall_probability,
                'strength' => $prediction->strength_label,
                'slots' => $prediction->slot_probabilities,
                'best_slot' => $prediction->best_slot,
                'factors' => $prediction->getFactorsSummary(),
            ],
        ]);
    }

    /**
     * Refresh patterns for a worker.
     */
    public function refreshWorkerPatterns(Request $request, int $workerId): JsonResponse
    {
        $worker = User::findOrFail($workerId);

        if (! $this->canManageWorkerData($request, $worker)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->forecastingService->analyzeWorkerPatterns($worker);

        return response()->json([
            'success' => true,
            'message' => 'Worker patterns refreshed successfully',
        ]);
    }

    // =========================================
    // Demand Forecasts
    // =========================================

    /**
     * Get demand forecasts for a date range.
     */
    public function getDemandForecasts(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->addDays(14)->toDateString());
        $region = $request->input('region');
        $skill = $request->input('skill');

        $query = DemandForecast::forDateRange($startDate, $endDate);

        if ($region) {
            $query->forRegion($region);
        }

        if ($skill) {
            $query->forSkill($skill);
        }

        $forecasts = $query->orderBy('forecast_date')
            ->get()
            ->map(function ($forecast) {
                return [
                    'date' => $forecast->forecast_date->toDateString(),
                    'day_name' => $forecast->forecast_date->format('l'),
                    'region' => $forecast->region,
                    'skill' => $forecast->skill_category,
                    'predicted_demand' => $forecast->predicted_demand,
                    'predicted_supply' => $forecast->predicted_supply,
                    'supply_gap' => $forecast->supply_gap,
                    'ratio' => $forecast->supply_demand_ratio,
                    'level' => $forecast->demand_level,
                    'level_label' => $forecast->level_label,
                    'level_color' => $forecast->level_color,
                    'has_shortage' => $forecast->has_shortage,
                    'shortage_percent' => $forecast->shortage_percent,
                ];
            });

        return response()->json([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'region' => $region,
            'skill' => $skill,
            'forecasts' => $forecasts,
        ]);
    }

    /**
     * Get supply-demand gap analysis.
     */
    public function getSupplyDemandGap(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'region' => 'required|string',
        ]);

        $date = Carbon::parse($request->input('date'));
        $region = $request->input('region');

        $gap = $this->forecastingService->getSupplyDemandGap($date, $region);

        return response()->json($gap);
    }

    /**
     * Get critical forecasts (worker shortages).
     */
    public function getCriticalForecasts(Request $request): JsonResponse
    {
        $days = $request->input('days', 14);

        $forecasts = DemandForecast::future()
            ->highDemand()
            ->orderBy('forecast_date')
            ->limit(50)
            ->get()
            ->map(function ($forecast) {
                return [
                    'date' => $forecast->forecast_date->toDateString(),
                    'region' => $forecast->region,
                    'skill' => $forecast->skill_category,
                    'level' => $forecast->demand_level,
                    'shortage_percent' => $forecast->shortage_percent,
                    'recommendations' => $forecast->getRecommendations(),
                ];
            });

        return response()->json([
            'critical_forecasts' => $forecasts,
            'total' => $forecasts->count(),
        ]);
    }

    // =========================================
    // Available Workers
    // =========================================

    /**
     * Get workers likely available on a date.
     */
    public function getAvailableWorkers(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'time_slot' => 'required|in:morning,afternoon,evening,night',
        ]);

        $date = Carbon::parse($request->input('date'));
        $timeSlot = $request->input('time_slot');

        $workers = $this->forecastingService->getAvailableWorkers($date, $timeSlot);

        return response()->json([
            'date' => $date->toDateString(),
            'time_slot' => $timeSlot,
            'available_workers' => $workers->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'reliability_score' => $worker->reliability_score,
                    'rating' => $worker->rating_as_worker,
                ];
            }),
            'total' => $workers->count(),
        ]);
    }

    /**
     * Get recommended workers for a shift.
     */
    public function getRecommendedWorkersForShift(Request $request, int $shiftId): JsonResponse
    {
        $shift = Shift::findOrFail($shiftId);

        // Check authorization
        $user = $request->user();
        if (! $user || ($user->id !== $shift->business_id && ! $user->isAdmin())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $recommended = $this->forecastingService->getRecommendedWorkers($shift);

        return response()->json([
            'shift_id' => $shiftId,
            'shift_date' => $shift->shift_date->toDateString(),
            'recommended_workers' => $recommended->values()->map(function ($rec) {
                return [
                    'worker' => [
                        'id' => $rec['user']->id,
                        'name' => $rec['user']->name,
                        'reliability_score' => $rec['user']->reliability_score,
                        'rating' => $rec['user']->rating_as_worker,
                    ],
                    'availability_probability' => round($rec['probability'] * 100, 1),
                    'slot_probability' => round($rec['slot_probability'] * 100, 1),
                ];
            }),
            'total' => $recommended->count(),
        ]);
    }

    // =========================================
    // Analytics & Reports
    // =========================================

    /**
     * Get prediction accuracy stats.
     */
    public function getPredictionAccuracy(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::today()->subDays($days);

        $stats = AvailabilityPrediction::where('prediction_date', '>=', $startDate)
            ->whereNotNull('was_accurate')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) as accurate,
                AVG(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) * 100 as accuracy_rate
            ')
            ->first();

        return response()->json([
            'period_days' => $days,
            'total_predictions' => $stats->total ?? 0,
            'accurate_predictions' => $stats->accurate ?? 0,
            'accuracy_rate' => round($stats->accuracy_rate ?? 0, 2),
        ]);
    }

    /**
     * Get demand trends.
     */
    public function getDemandTrends(Request $request): JsonResponse
    {
        $days = $request->input('days', 14);
        $region = $request->input('region');

        $query = DemandForecast::forDateRange(
            Carbon::today(),
            Carbon::today()->addDays($days)
        );

        if ($region) {
            $query->forRegion($region);
        }

        $forecasts = $query->orderBy('forecast_date')->get();

        $trends = [
            'dates' => [],
            'demand' => [],
            'supply' => [],
            'gap' => [],
        ];

        foreach ($forecasts as $forecast) {
            $trends['dates'][] = $forecast->forecast_date->format('M j');
            $trends['demand'][] = $forecast->predicted_demand;
            $trends['supply'][] = $forecast->predicted_supply;
            $trends['gap'][] = $forecast->supply_gap;
        }

        $avgDemand = count($trends['demand']) > 0 ? array_sum($trends['demand']) / count($trends['demand']) : 0;
        $avgSupply = count($trends['supply']) > 0 ? array_sum($trends['supply']) / count($trends['supply']) : 0;

        return response()->json([
            'period_days' => $days,
            'region' => $region,
            'trends' => $trends,
            'summary' => [
                'avg_demand' => round($avgDemand, 1),
                'avg_supply' => round($avgSupply, 1),
                'avg_gap' => round($avgSupply - $avgDemand, 1),
            ],
        ]);
    }

    /**
     * Get available regions for forecasting.
     */
    public function getRegions(): JsonResponse
    {
        $regions = DemandForecast::getDistinctRegions();

        return response()->json([
            'regions' => $regions,
        ]);
    }

    /**
     * Get available skills for forecasting.
     */
    public function getSkillCategories(): JsonResponse
    {
        $skills = DemandForecast::getDistinctSkills();

        return response()->json([
            'skill_categories' => $skills,
        ]);
    }

    // =========================================
    // Authorization Helpers
    // =========================================

    /**
     * Check if user can view worker data.
     */
    protected function canViewWorkerData(Request $request, User $worker): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Worker can view own data
        if ($user->id === $worker->id) {
            return true;
        }

        // Admins can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Businesses can view workers who have applied to their shifts
        if ($user->isBusiness()) {
            return $worker->shiftApplications()
                ->whereHas('shift', function ($query) use ($user) {
                    $query->where('business_id', $user->id);
                })
                ->exists();
        }

        return false;
    }

    /**
     * Check if user can manage worker data.
     */
    protected function canManageWorkerData(Request $request, User $worker): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Worker can manage own data
        if ($user->id === $worker->id) {
            return true;
        }

        // Admins can manage all
        return $user->isAdmin();
    }
}
