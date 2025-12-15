<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SpendAnalyticsService
{
    /**
     * Get comprehensive spend analytics for a business.
     */
    public function getBusinessAnalytics($businessProfileId, $timeRange = 'month')
    {
        $businessProfile = BusinessProfile::findOrFail($businessProfileId);

        return [
            'budget_overview' => $this->getBudgetOverview($businessProfile),
            'spend_by_role' => $this->getSpendByRole($businessProfileId, $timeRange),
            'trend_analysis' => $this->getTrendAnalysis($businessProfileId),
            'venue_comparison' => $this->getVenueComparison($businessProfileId, $timeRange),
            'budget_alerts' => $this->getBudgetAlerts($businessProfile),
        ];
    }

    /**
     * Get budget overview with current utilization.
     */
    public function getBudgetOverview(BusinessProfile $businessProfile)
    {
        $monthlyBudget = $businessProfile->monthly_budget;
        $currentSpend = $businessProfile->current_month_spend;
        $ytdSpend = $businessProfile->ytd_spend;

        $utilization = $monthlyBudget > 0
            ? round(($currentSpend / $monthlyBudget) * 100, 2)
            : 0;

        $remaining = max(0, $monthlyBudget - $currentSpend);

        return [
            'monthly_budget' => $monthlyBudget,
            'monthly_budget_dollars' => $monthlyBudget / 100,
            'current_spend' => $currentSpend,
            'current_spend_dollars' => $currentSpend / 100,
            'remaining_budget' => $remaining,
            'remaining_budget_dollars' => $remaining / 100,
            'utilization_percentage' => $utilization,
            'ytd_spend' => $ytdSpend,
            'ytd_spend_dollars' => $ytdSpend / 100,
            'average_monthly_spend' => $this->calculateAverageMonthlySpend($businessProfile->id),
        ];
    }

    /**
     * Get spend breakdown by role type.
     */
    public function getSpendByRole($businessProfileId, $timeRange = 'month')
    {
        $dateRange = $this->getDateRange($timeRange);

        $spendByRole = ShiftPayment::join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
            ->where('shifts.business_profile_id', $businessProfileId)
            ->whereBetween('shift_payments.created_at', [$dateRange['start'], $dateRange['end']])
            ->whereIn('shift_payments.status', ['completed', 'paid'])
            ->select(
                'shifts.role',
                DB::raw('COUNT(*) as shift_count'),
                DB::raw('SUM(shift_payments.total_amount) as total_spend'),
                DB::raw('AVG(shift_payments.total_amount) as average_cost')
            )
            ->groupBy('shifts.role')
            ->orderByDesc('total_spend')
            ->get()
            ->map(function ($item) {
                return [
                    'role' => $item->role,
                    'shift_count' => $item->shift_count,
                    'total_spend' => $item->total_spend,
                    'total_spend_dollars' => $item->total_spend / 100,
                    'average_cost' => $item->average_cost,
                    'average_cost_dollars' => $item->average_cost / 100,
                ];
            });

        return $spendByRole;
    }

    /**
     * Get 12-week trend analysis.
     */
    public function getTrendAnalysis($businessProfileId, $weeks = 12)
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subWeeks($weeks);

        $weeklyData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();

            $weeklySpend = ShiftPayment::join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_profile_id', $businessProfileId)
                ->whereBetween('shift_payments.created_at', [$weekStart, $weekEnd])
                ->whereIn('shift_payments.status', ['completed', 'paid'])
                ->sum('shift_payments.total_amount');

            $shiftsCount = Shift::where('business_profile_id', $businessProfileId)
                ->whereBetween('created_at', [$weekStart, $weekEnd])
                ->count();

            $weeklyData[] = [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'week_label' => $weekStart->format('M d'),
                'total_spend' => $weeklySpend,
                'total_spend_dollars' => $weeklySpend / 100,
                'shifts_count' => $shiftsCount,
                'average_cost_per_shift' => $shiftsCount > 0 ? $weeklySpend / $shiftsCount : 0,
            ];

            $currentDate->addWeek();
        }

        return $weeklyData;
    }

    /**
     * Get venue comparison report.
     */
    public function getVenueComparison($businessProfileId, $timeRange = 'month')
    {
        $dateRange = $this->getDateRange($timeRange);

        $venues = Venue::where('business_profile_id', $businessProfileId)
            ->where('is_active', true)
            ->with(['shifts' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }])
            ->get()
            ->map(function ($venue) use ($dateRange) {
                $totalSpend = ShiftPayment::join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
                    ->where('shifts.venue_id', $venue->id)
                    ->whereBetween('shift_payments.created_at', [$dateRange['start'], $dateRange['end']])
                    ->whereIn('shift_payments.status', ['completed', 'paid'])
                    ->sum('shift_payments.total_amount');

                $shiftsCount = $venue->shifts->count();
                $budgetUtilization = $venue->monthly_budget > 0
                    ? round(($venue->current_month_spend / $venue->monthly_budget) * 100, 2)
                    : 0;

                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'code' => $venue->code,
                    'monthly_budget' => $venue->monthly_budget,
                    'monthly_budget_dollars' => $venue->monthly_budget / 100,
                    'current_spend' => $venue->current_month_spend,
                    'current_spend_dollars' => $venue->current_month_spend / 100,
                    'budget_utilization' => $budgetUtilization,
                    'shifts_count' => $shiftsCount,
                    'total_spend_period' => $totalSpend,
                    'total_spend_period_dollars' => $totalSpend / 100,
                    'fill_rate' => $venue->fill_rate,
                    'average_rating' => $venue->average_rating,
                ];
            });

        return $venues->sortByDesc('total_spend_period')->values();
    }

    /**
     * Get budget alerts based on thresholds.
     */
    public function getBudgetAlerts(BusinessProfile $businessProfile)
    {
        $alerts = [];
        $utilization = $businessProfile->monthly_budget > 0
            ? ($businessProfile->current_month_spend / $businessProfile->monthly_budget) * 100
            : 0;

        // Check business-level alerts
        if ($utilization >= $businessProfile->budget_alert_threshold_100) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'budget_exceeded',
                'message' => 'Monthly budget has been reached or exceeded',
                'utilization' => round($utilization, 2),
                'entity' => 'business',
                'entity_id' => $businessProfile->id,
            ];
        } elseif ($utilization >= $businessProfile->budget_alert_threshold_90) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'budget_high',
                'message' => 'Monthly budget is at 90% or higher',
                'utilization' => round($utilization, 2),
                'entity' => 'business',
                'entity_id' => $businessProfile->id,
            ];
        } elseif ($utilization >= $businessProfile->budget_alert_threshold_75) {
            $alerts[] = [
                'level' => 'info',
                'type' => 'budget_medium',
                'message' => 'Monthly budget is at 75% or higher',
                'utilization' => round($utilization, 2),
                'entity' => 'business',
                'entity_id' => $businessProfile->id,
            ];
        }

        // Check venue-level alerts
        $venues = Venue::where('business_profile_id', $businessProfile->id)
            ->where('is_active', true)
            ->get();

        foreach ($venues as $venue) {
            $venueUtilization = $venue->monthly_budget > 0
                ? ($venue->current_month_spend / $venue->monthly_budget) * 100
                : 0;

            if ($venueUtilization >= 100) {
                $alerts[] = [
                    'level' => 'critical',
                    'type' => 'venue_budget_exceeded',
                    'message' => "Venue '{$venue->name}' has reached or exceeded its budget",
                    'utilization' => round($venueUtilization, 2),
                    'entity' => 'venue',
                    'entity_id' => $venue->id,
                    'entity_name' => $venue->name,
                ];
            } elseif ($venueUtilization >= 90) {
                $alerts[] = [
                    'level' => 'warning',
                    'type' => 'venue_budget_high',
                    'message' => "Venue '{$venue->name}' is at 90% or higher of its budget",
                    'utilization' => round($venueUtilization, 2),
                    'entity' => 'venue',
                    'entity_id' => $venue->id,
                    'entity_name' => $venue->name,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Calculate average monthly spend for the business.
     */
    protected function calculateAverageMonthlySpend($businessProfileId)
    {
        $monthsBack = 6;
        $startDate = Carbon::now()->subMonths($monthsBack)->startOfMonth();

        $totalSpend = ShiftPayment::join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
            ->where('shifts.business_profile_id', $businessProfileId)
            ->where('shift_payments.created_at', '>=', $startDate)
            ->whereIn('shift_payments.status', ['completed', 'paid'])
            ->sum('shift_payments.total_amount');

        $averageMonthly = $totalSpend / $monthsBack;

        return [
            'amount' => round($averageMonthly),
            'amount_dollars' => round($averageMonthly / 100, 2),
            'months_calculated' => $monthsBack,
        ];
    }

    /**
     * Get date range based on time range parameter.
     */
    protected function getDateRange($timeRange)
    {
        switch ($timeRange) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'quarter':
                $start = Carbon::now()->startOfQuarter();
                $end = Carbon::now()->endOfQuarter();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Export analytics data to CSV format.
     */
    public function exportToCSV($businessProfileId, $timeRange = 'month')
    {
        $analytics = $this->getBusinessAnalytics($businessProfileId, $timeRange);

        $csvData = [];

        // Add budget overview
        $csvData[] = ['Budget Overview'];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Monthly Budget', '$' . number_format($analytics['budget_overview']['monthly_budget_dollars'], 2)];
        $csvData[] = ['Current Spend', '$' . number_format($analytics['budget_overview']['current_spend_dollars'], 2)];
        $csvData[] = ['Remaining Budget', '$' . number_format($analytics['budget_overview']['remaining_budget_dollars'], 2)];
        $csvData[] = ['Utilization', $analytics['budget_overview']['utilization_percentage'] . '%'];
        $csvData[] = [];

        // Add spend by role
        $csvData[] = ['Spend by Role'];
        $csvData[] = ['Role', 'Shifts', 'Total Spend', 'Average Cost'];
        foreach ($analytics['spend_by_role'] as $role) {
            $csvData[] = [
                $role['role'],
                $role['shift_count'],
                '$' . number_format($role['total_spend_dollars'], 2),
                '$' . number_format($role['average_cost_dollars'], 2),
            ];
        }
        $csvData[] = [];

        // Add venue comparison
        $csvData[] = ['Venue Comparison'];
        $csvData[] = ['Venue', 'Budget', 'Spend', 'Utilization', 'Shifts'];
        foreach ($analytics['venue_comparison'] as $venue) {
            $csvData[] = [
                $venue['name'],
                '$' . number_format($venue['monthly_budget_dollars'], 2),
                '$' . number_format($venue['current_spend_dollars'], 2),
                $venue['budget_utilization'] . '%',
                $venue['shifts_count'],
            ];
        }

        return $csvData;
    }
}
