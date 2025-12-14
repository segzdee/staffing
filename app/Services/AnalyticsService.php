<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get comprehensive analytics for business
     */
    public function getBusinessAnalytics(User $business, $startDate = null, $endDate = null)
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now();

        return [
            'overview' => $this->getOverviewMetrics($business, $start, $end),
            'labor_costs' => $this->getLaborCostAnalysis($business, $start, $end),
            'fill_rate' => $this->getFillRateMetrics($business, $start, $end),
            'worker_performance' => $this->getWorkerPerformanceMetrics($business, $start, $end),
            'shift_trends' => $this->getShiftTrends($business, $start, $end),
            'peak_demand' => $this->getPeakDemandAnalysis($business, $start, $end),
            'cost_breakdown' => $this->getCostBreakdown($business, $start, $end),
        ];
    }

    /**
     * Overview metrics
     */
    protected function getOverviewMetrics(User $business, $start, $end)
    {
        $shifts = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->get();

        $totalShifts = $shifts->count();
        $completedShifts = $shifts->where('status', 'completed')->count();
        $cancelledShifts = $shifts->where('status', 'cancelled')->count();
        $totalWorkers = $shifts->sum('filled_workers');

        $assignments = ShiftAssignment::whereHas('shift', function($q) use ($business, $start, $end) {
            $q->where('business_id', $business->id)
              ->whereBetween('shift_date', [$start, $end]);
        })->get();

        $noShows = $assignments->where('status', 'no_show')->count();

        return [
            'total_shifts_posted' => $totalShifts,
            'shifts_completed' => $completedShifts,
            'shifts_cancelled' => $cancelledShifts,
            'total_workers_hired' => $totalWorkers,
            'no_show_count' => $noShows,
            'no_show_rate' => $totalWorkers > 0 ? round(($noShows / $totalWorkers) * 100, 2) : 0,
            'completion_rate' => $totalShifts > 0 ? round(($completedShifts / $totalShifts) * 100, 2) : 0,
        ];
    }

    /**
     * Labor cost analysis
     */
    protected function getLaborCostAnalysis(User $business, $start, $end)
    {
        $payments = ShiftPayment::where('business_id', $business->id)
            ->whereHas('shift', function($q) use ($start, $end) {
                $q->whereBetween('shift_date', [$start, $end]);
            })
            ->get();

        $totalGross = $payments->sum('amount_gross');
        $totalNet = $payments->sum('amount_net');
        $platformFees = $payments->sum('platform_fee');
        $totalHours = $payments->sum('hours_actual');

        $avgHourlyRate = $totalHours > 0 ? $totalGross / $totalHours : 0;

        // Get cost by industry
        $costByIndustry = ShiftPayment::where('business_id', $business->id)
            ->whereHas('shift', function($q) use ($start, $end) {
                $q->whereBetween('shift_date', [$start, $end]);
            })
            ->join('shifts', 'shift_payments.shift_id', '=', 'shifts.id')
            ->select('shifts.industry', DB::raw('SUM(amount_gross) as total_cost'), DB::raw('COUNT(*) as shift_count'))
            ->groupBy('shifts.industry')
            ->get();

        return [
            'total_spent' => round($totalGross, 2),
            'total_paid_to_workers' => round($totalNet, 2),
            'platform_fees_paid' => round($platformFees, 2),
            'total_hours_worked' => round($totalHours, 2),
            'average_hourly_rate' => round($avgHourlyRate, 2),
            'cost_by_industry' => $costByIndustry,
        ];
    }

    /**
     * Fill rate metrics
     */
    protected function getFillRateMetrics(User $business, $start, $end)
    {
        $shifts = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->get();

        $totalPositions = $shifts->sum('required_workers');
        $filledPositions = $shifts->sum('filled_workers');
        $fullyStaffedShifts = $shifts->filter(function($shift) {
            return $shift->filled_workers >= $shift->required_workers;
        })->count();

        // Calculate average time to fill
        $filledShifts = $shifts->whereNotNull('filled_at');
        $avgFillTime = 0;

        if ($filledShifts->count() > 0) {
            $totalMinutes = $filledShifts->sum(function($shift) {
                return Carbon::parse($shift->created_at)->diffInMinutes($shift->filled_at);
            });
            $count = $filledShifts->count();
            $avgFillTime = $count > 0 ? round($totalMinutes / $count / 60, 1) : 0; // Hours
        }

        // Fill rate by urgency
        $fillRateByUrgency = $shifts->groupBy('urgency_level')->map(function($urgencyShifts) {
            $total = $urgencyShifts->sum('required_workers');
            $filled = $urgencyShifts->sum('filled_workers');
            return $total > 0 ? round(($filled / $total) * 100, 2) : 0;
        });

        return [
            'overall_fill_rate' => $totalPositions > 0 ? round(($filledPositions / $totalPositions) * 100, 2) : 0,
            'fully_staffed_shifts' => $fullyStaffedShifts,
            'partially_staffed_shifts' => $shifts->count() - $fullyStaffedShifts,
            'average_time_to_fill_hours' => $avgFillTime,
            'fill_rate_by_urgency' => $fillRateByUrgency,
        ];
    }

    /**
     * Worker performance metrics
     */
    protected function getWorkerPerformanceMetrics(User $business, $start, $end)
    {
        $assignments = ShiftAssignment::with('worker')
            ->whereHas('shift', function($q) use ($business, $start, $end) {
                $q->where('business_id', $business->id)
                  ->whereBetween('shift_date', [$start, $end]);
            })
            ->get();

        // Top performers
        $workerStats = $assignments->groupBy('worker_id')->map(function($workerAssignments, $workerId) {
            $worker = $workerAssignments->first()->worker;
            $completed = $workerAssignments->where('status', 'completed')->count();
            $noShows = $workerAssignments->where('status', 'no_show')->count();
            $total = $workerAssignments->count();

            return [
                'worker_id' => $workerId,
                'worker_name' => $worker->name,
                'shifts_completed' => $completed,
                'no_shows' => $noShows,
                'reliability_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'rating' => $worker->rating_as_worker ?? 0,
            ];
        })->sortByDesc('shifts_completed')->take(10);

        return [
            'total_unique_workers' => $assignments->unique('worker_id')->count(),
            'top_performers' => $workerStats->values(),
            'average_worker_rating' => round($assignments->avg(function($assignment) {
                return $assignment->worker->rating_as_worker ?? 0;
            }), 2),
        ];
    }

    /**
     * Shift trends over time
     */
    protected function getShiftTrends(User $business, $start, $end)
    {
        $shiftsByDay = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->select(
                DB::raw('DATE(shift_date) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(filled_workers) as workers_hired')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Day of week analysis
        $shiftsByDayOfWeek = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->select(
                DB::raw('DAYNAME(shift_date) as day_of_week'),
                DB::raw('COUNT(*) as shift_count'),
                DB::raw('AVG(filled_workers) as avg_workers')
            )
            ->groupBy('day_of_week')
            ->get();

        return [
            'daily_trends' => $shiftsByDay,
            'day_of_week_analysis' => $shiftsByDayOfWeek,
        ];
    }

    /**
     * Peak demand analysis
     */
    protected function getPeakDemandAnalysis(User $business, $start, $end)
    {
        // Hour of day analysis
        $shiftsByHour = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->select(
                DB::raw('HOUR(start_time) as hour'),
                DB::raw('COUNT(*) as shift_count'),
                DB::raw('SUM(required_workers) as workers_needed')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Industry demand
        $demandByIndustry = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->select(
                'industry',
                DB::raw('COUNT(*) as shift_count'),
                DB::raw('AVG(final_rate) as avg_rate'),
                DB::raw('AVG(filled_workers / required_workers * 100) as avg_fill_rate')
            )
            ->groupBy('industry')
            ->get();

        return [
            'peak_hours' => $shiftsByHour,
            'demand_by_industry' => $demandByIndustry,
        ];
    }

    /**
     * Cost breakdown analysis
     */
    protected function getCostBreakdown(User $business, $start, $end)
    {
        $shifts = Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [$start, $end])
            ->with('payments')
            ->get();

        // Cost per shift
        $avgCostPerShift = $shifts->avg(function($shift) {
            return $shift->payments->sum('amount_gross');
        });

        // Cost by day of week
        $costByDayOfWeek = $shifts->groupBy(function($shift) {
            return Carbon::parse($shift->shift_date)->format('l');
        })->map(function($dayShifts) {
            return $dayShifts->sum(function($shift) {
                return $shift->payments->sum('amount_gross');
            });
        });

        // Budget comparison (if they have a budget set)
        $totalSpent = $shifts->sum(function($shift) {
            return $shift->payments->sum('amount_gross');
        });

        return [
            'total_spent' => round($totalSpent, 2),
            'average_cost_per_shift' => round($avgCostPerShift, 2),
            'cost_by_day_of_week' => $costByDayOfWeek,
        ];
    }

    /**
     * Get real-time dashboard metrics
     */
    public function getRealtimeDashboard(User $business)
    {
        return [
            'shifts_today' => $this->getShiftsToday($business),
            'upcoming_shifts_7days' => $this->getUpcomingShifts($business, 7),
            'pending_applications' => $this->getPendingApplications($business),
            'active_workers_now' => $this->getActiveWorkersNow($business),
            'urgent_shifts' => $this->getUrgentShifts($business),
        ];
    }

    /**
     * Get shifts for today
     */
    protected function getShiftsToday(User $business)
    {
        return Shift::where('business_id', $business->id)
            ->whereDate('shift_date', Carbon::today())
            ->count();
    }

    /**
     * Get upcoming shifts
     */
    protected function getUpcomingShifts(User $business, $days)
    {
        return Shift::where('business_id', $business->id)
            ->whereBetween('shift_date', [Carbon::today(), Carbon::today()->addDays($days)])
            ->count();
    }

    /**
     * Get pending applications count
     */
    protected function getPendingApplications(User $business)
    {
        return DB::table('shift_applications')
            ->join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $business->id)
            ->where('shift_applications.status', 'pending')
            ->count();
    }

    /**
     * Get active workers right now
     */
    protected function getActiveWorkersNow(User $business)
    {
        $now = Carbon::now();

        return ShiftAssignment::whereHas('shift', function($q) use ($business, $now) {
            $q->where('business_id', $business->id)
              ->whereDate('shift_date', $now->toDateString())
              ->whereTime('start_time', '<=', $now->toTimeString())
              ->whereTime('end_time', '>=', $now->toTimeString());
        })
        ->whereIn('status', ['checked_in'])
        ->count();
    }

    /**
     * Get urgent unfilled shifts
     */
    protected function getUrgentShifts(User $business)
    {
        return Shift::where('business_id', $business->id)
            ->where('status', 'open')
            ->whereIn('urgency_level', ['urgent', 'critical'])
            ->where('shift_date', '>=', Carbon::today())
            ->whereRaw('filled_workers < required_workers')
            ->count();
    }

    /**
     * Export analytics data to CSV
     */
    public function exportToCSV(User $business, $startDate, $endDate)
    {
        $analytics = $this->getBusinessAnalytics($business, $startDate, $endDate);

        // TODO: Generate CSV file
        return $analytics;
    }
}
