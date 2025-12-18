<?php

namespace App\Services;

use App\Models\EarningsSummary;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\WorkerEarning;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * WKR-006: Earnings Service
 *
 * Comprehensive service for managing worker earnings, calculating summaries,
 * generating reports, and exporting earnings data.
 */
class EarningsService
{
    /**
     * Record a new earning for a worker.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordEarning(User $worker, array $data): WorkerEarning
    {
        $grossAmount = $data['gross_amount'];
        $platformFee = $data['platform_fee'] ?? $this->calculatePlatformFee($grossAmount);
        $taxWithheld = $data['tax_withheld'] ?? $this->calculateTaxWithholding($worker, $grossAmount);
        $netAmount = $data['net_amount'] ?? ($grossAmount - $platformFee - $taxWithheld);

        $earning = WorkerEarning::create([
            'user_id' => $worker->id,
            'shift_id' => $data['shift_id'] ?? null,
            'type' => $data['type'],
            'gross_amount' => $grossAmount,
            'platform_fee' => $platformFee,
            'tax_withheld' => $taxWithheld,
            'net_amount' => $netAmount,
            'currency' => $data['currency'] ?? config('earnings.default_currency', 'USD'),
            'status' => $data['status'] ?? WorkerEarning::STATUS_PENDING,
            'description' => $data['description'] ?? null,
            'earned_date' => $data['earned_date'] ?? now()->toDateString(),
            'pay_date' => $data['pay_date'] ?? null,
        ]);

        // Trigger summary refresh for affected periods
        $this->invalidateSummariesForDate($worker, Carbon::parse($earning->earned_date));

        return $earning;
    }

    /**
     * Record earnings from a completed shift.
     */
    public function recordShiftEarning(User $worker, Shift $shift, ShiftAssignment $assignment): WorkerEarning
    {
        $hoursWorked = $assignment->hours_worked ?? $shift->duration_hours;
        $hourlyRate = $shift->final_rate ?? $shift->hourly_rate;
        $grossAmount = $hoursWorked * $hourlyRate;

        return $this->recordEarning($worker, [
            'shift_id' => $shift->id,
            'type' => WorkerEarning::TYPE_SHIFT_PAY,
            'gross_amount' => $grossAmount,
            'earned_date' => $shift->shift_date,
            'description' => "Shift: {$shift->title}",
            'status' => WorkerEarning::STATUS_APPROVED,
        ]);
    }

    /**
     * Get earnings for a specific period.
     *
     * @return Collection<int, WorkerEarning>
     */
    public function getEarningsByPeriod(
        User $worker,
        string $period,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): Collection {
        [$periodStart, $periodEnd] = $this->resolvePeriodDates($period, $start, $end);

        return WorkerEarning::forWorker($worker->id)
            ->inDateRange($periodStart, $periodEnd)
            ->orderBy('earned_date', 'desc')
            ->get();
    }

    /**
     * Calculate and update earnings summary for a period.
     */
    public function calculateEarningsSummary(
        User $worker,
        string $periodType,
        Carbon $date
    ): EarningsSummary {
        [$periodStart, $periodEnd] = $this->getPeriodBoundaries($periodType, $date);

        // Get earnings data for the period
        $earnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($periodStart, $periodEnd)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        // Get shift assignments for hours worked
        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereHas('shift', function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('shift_date', [$periodStart, $periodEnd]);
            })
            ->get();

        $shiftsCompleted = $assignments->count();
        $totalHours = $assignments->sum('hours_worked');
        $grossEarnings = $earnings->sum('gross_amount');
        $totalFees = $earnings->sum('platform_fee');
        $totalTaxes = $earnings->sum('tax_withheld');
        $netEarnings = $earnings->sum('net_amount');
        $avgHourlyRate = $totalHours > 0 ? round($grossEarnings / $totalHours, 2) : 0;

        // Update or create summary
        return EarningsSummary::updateOrCreate(
            [
                'user_id' => $worker->id,
                'period_type' => $periodType,
                'period_start' => $periodStart->toDateString(),
            ],
            [
                'period_end' => $periodEnd->toDateString(),
                'shifts_completed' => $shiftsCompleted,
                'total_hours' => $totalHours,
                'gross_earnings' => $grossEarnings,
                'total_fees' => $totalFees,
                'total_taxes' => $totalTaxes,
                'net_earnings' => $netEarnings,
                'avg_hourly_rate' => $avgHourlyRate,
            ]
        );
    }

    /**
     * Refresh all summaries for a worker.
     *
     * @return array<string, int>
     */
    public function refreshAllSummaries(User $worker): array
    {
        $stats = [
            'daily' => 0,
            'weekly' => 0,
            'monthly' => 0,
            'yearly' => 0,
        ];

        // Get the date range of worker's earnings
        $firstEarning = WorkerEarning::forWorker($worker->id)
            ->orderBy('earned_date', 'asc')
            ->first();

        if (! $firstEarning) {
            return $stats;
        }

        $startDate = Carbon::parse($firstEarning->earned_date)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Generate monthly summaries
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $this->calculateEarningsSummary($worker, EarningsSummary::PERIOD_MONTHLY, $current);
            $stats['monthly']++;
            $current->addMonth();
        }

        // Generate weekly summaries for the current year
        $yearStart = now()->startOfYear();
        $current = $yearStart->copy()->startOfWeek();
        while ($current <= $endDate) {
            $this->calculateEarningsSummary($worker, EarningsSummary::PERIOD_WEEKLY, $current);
            $stats['weekly']++;
            $current->addWeek();
        }

        // Generate yearly summaries
        $currentYear = $startDate->copy()->startOfYear();
        while ($currentYear <= $endDate) {
            $this->calculateEarningsSummary($worker, EarningsSummary::PERIOD_YEARLY, $currentYear);
            $stats['yearly']++;
            $currentYear->addYear();
        }

        // Generate daily summaries for the last 30 days
        $current = now()->subDays(30);
        while ($current <= now()) {
            $this->calculateEarningsSummary($worker, EarningsSummary::PERIOD_DAILY, $current);
            $stats['daily']++;
            $current->addDay();
        }

        return $stats;
    }

    /**
     * Get year-to-date earnings for a worker.
     *
     * @return array<string, mixed>
     */
    public function getYearToDateEarnings(User $worker): array
    {
        $yearStart = now()->startOfYear();
        $now = now();

        $earnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($yearStart, $now)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereHas('shift', function ($query) use ($yearStart, $now) {
                $query->whereBetween('shift_date', [$yearStart, $now]);
            })
            ->count();

        return [
            'gross_earnings' => $earnings->sum('gross_amount'),
            'net_earnings' => $earnings->sum('net_amount'),
            'total_fees' => $earnings->sum('platform_fee'),
            'total_taxes' => $earnings->sum('tax_withheld'),
            'shifts_completed' => $assignments,
            'total_hours' => $earnings->where('type', WorkerEarning::TYPE_SHIFT_PAY)->count() > 0
                ? ShiftAssignment::where('worker_id', $worker->id)
                    ->where('status', 'completed')
                    ->whereHas('shift', function ($query) use ($yearStart, $now) {
                        $query->whereBetween('shift_date', [$yearStart, $now]);
                    })
                    ->sum('hours_worked')
                : 0,
            'year' => $yearStart->year,
            'period_start' => $yearStart,
            'period_end' => $now,
        ];
    }

    /**
     * Get average hourly rate for a worker over a period.
     */
    public function getAverageHourlyRate(User $worker, int $days = 30): float
    {
        $startDate = now()->subDays($days);

        $assignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereHas('shift', function ($query) use ($startDate) {
                $query->where('shift_date', '>=', $startDate);
            })
            ->with('shift')
            ->get();

        if ($assignments->isEmpty()) {
            return 0;
        }

        $totalEarnings = 0;
        $totalHours = 0;

        foreach ($assignments as $assignment) {
            $hours = $assignment->hours_worked ?? $assignment->shift->duration_hours ?? 0;
            $rate = $assignment->shift->final_rate ?? $assignment->shift->hourly_rate ?? 0;
            $totalEarnings += $hours * $rate;
            $totalHours += $hours;
        }

        return $totalHours > 0 ? round($totalEarnings / $totalHours, 2) : 0;
    }

    /**
     * Compare earnings between current and previous period.
     *
     * @return array<string, mixed>
     */
    public function compareEarnings(User $worker, string $period): array
    {
        [$currentStart, $currentEnd] = $this->resolvePeriodDates($period);
        $periodLength = $currentStart->diffInDays($currentEnd) + 1;
        $previousStart = $currentStart->copy()->subDays($periodLength);
        $previousEnd = $currentStart->copy()->subDay();

        // Current period data
        $currentEarnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($currentStart, $currentEnd)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        $currentAssignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereHas('shift', function ($query) use ($currentStart, $currentEnd) {
                $query->whereBetween('shift_date', [$currentStart, $currentEnd]);
            })
            ->get();

        // Previous period data
        $previousEarnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($previousStart, $previousEnd)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        $previousAssignments = ShiftAssignment::where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereHas('shift', function ($query) use ($previousStart, $previousEnd) {
                $query->whereBetween('shift_date', [$previousStart, $previousEnd]);
            })
            ->get();

        $currentGross = $currentEarnings->sum('gross_amount');
        $previousGross = $previousEarnings->sum('gross_amount');
        $currentNet = $currentEarnings->sum('net_amount');
        $previousNet = $previousEarnings->sum('net_amount');
        $currentShifts = $currentAssignments->count();
        $previousShifts = $previousAssignments->count();
        $currentHours = $currentAssignments->sum('hours_worked');
        $previousHours = $previousAssignments->sum('hours_worked');

        return [
            'current' => [
                'gross_earnings' => $currentGross,
                'net_earnings' => $currentNet,
                'shifts_completed' => $currentShifts,
                'total_hours' => $currentHours,
                'period_start' => $currentStart,
                'period_end' => $currentEnd,
            ],
            'previous' => [
                'gross_earnings' => $previousGross,
                'net_earnings' => $previousNet,
                'shifts_completed' => $previousShifts,
                'total_hours' => $previousHours,
                'period_start' => $previousStart,
                'period_end' => $previousEnd,
            ],
            'changes' => [
                'gross_earnings' => $this->calculatePercentageChange($previousGross, $currentGross),
                'net_earnings' => $this->calculatePercentageChange($previousNet, $currentNet),
                'shifts_completed' => $this->calculatePercentageChange($previousShifts, $currentShifts),
                'total_hours' => $this->calculatePercentageChange($previousHours, $currentHours),
            ],
        ];
    }

    /**
     * Export earnings data.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|string
     */
    public function exportEarnings(User $worker, string $format, array $filters = []): array|string
    {
        $query = WorkerEarning::forWorker($worker->id);

        // Apply filters
        if (! empty($filters['start_date'])) {
            $query->where('earned_date', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('earned_date', '<=', $filters['end_date']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['year'])) {
            $query->whereYear('earned_date', $filters['year']);
        }

        $earnings = $query->with('shift.business')->orderBy('earned_date', 'desc')->get();

        if ($format === 'csv') {
            return $this->generateCsvExport($worker, $earnings, $filters);
        }

        if ($format === 'pdf') {
            return $this->generatePdfExport($worker, $earnings, $filters);
        }

        // Return array for other processing
        return [
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'email' => $worker->email,
            ],
            'filters' => $filters,
            'earnings' => $earnings->map(function ($earning) {
                return [
                    'id' => $earning->id,
                    'date' => $earning->earned_date->format('Y-m-d'),
                    'type' => $earning->type_label,
                    'description' => $earning->description,
                    'shift' => $earning->shift?->title,
                    'business' => $earning->shift?->business?->name,
                    'gross_amount' => $earning->gross_amount,
                    'platform_fee' => $earning->platform_fee,
                    'tax_withheld' => $earning->tax_withheld,
                    'net_amount' => $earning->net_amount,
                    'currency' => $earning->currency,
                    'status' => $earning->status_label,
                ];
            })->toArray(),
            'totals' => [
                'gross_amount' => $earnings->sum('gross_amount'),
                'platform_fee' => $earnings->sum('platform_fee'),
                'tax_withheld' => $earnings->sum('tax_withheld'),
                'net_amount' => $earnings->sum('net_amount'),
                'count' => $earnings->count(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get tax summary for a specific year.
     *
     * @return array<string, mixed>
     */
    public function getTaxSummary(User $worker, int $year): array
    {
        $yearStart = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $yearEnd = Carbon::createFromDate($year, 12, 31)->endOfYear();

        $earnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($yearStart, $yearEnd)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        // Group by type
        $byType = $earnings->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'gross_amount' => $group->sum('gross_amount'),
                'net_amount' => $group->sum('net_amount'),
                'tax_withheld' => $group->sum('tax_withheld'),
            ];
        });

        // Quarterly breakdown
        $quarters = [];
        for ($q = 1; $q <= 4; $q++) {
            $qStart = Carbon::createFromDate($year, ($q - 1) * 3 + 1, 1)->startOfMonth();
            $qEnd = Carbon::createFromDate($year, $q * 3, 1)->endOfMonth();

            $quarterEarnings = $earnings->filter(function ($earning) use ($qStart, $qEnd) {
                return $earning->earned_date >= $qStart && $earning->earned_date <= $qEnd;
            });

            $quarters["Q{$q}"] = [
                'gross_earnings' => $quarterEarnings->sum('gross_amount'),
                'net_earnings' => $quarterEarnings->sum('net_amount'),
                'tax_withheld' => $quarterEarnings->sum('tax_withheld'),
                'platform_fees' => $quarterEarnings->sum('platform_fee'),
                'period_start' => $qStart->format('M j, Y'),
                'period_end' => $qEnd->format('M j, Y'),
            ];
        }

        // Monthly breakdown
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $mStart = Carbon::createFromDate($year, $m, 1)->startOfMonth();
            $mEnd = Carbon::createFromDate($year, $m, 1)->endOfMonth();

            $monthEarnings = $earnings->filter(function ($earning) use ($mStart, $mEnd) {
                return $earning->earned_date >= $mStart && $earning->earned_date <= $mEnd;
            });

            $monthly[$mStart->format('F')] = [
                'gross_earnings' => $monthEarnings->sum('gross_amount'),
                'net_earnings' => $monthEarnings->sum('net_amount'),
                'tax_withheld' => $monthEarnings->sum('tax_withheld'),
            ];
        }

        // Determine if 1099 is required (IRS threshold is $600)
        $totalGross = $earnings->sum('gross_amount');
        $requires1099 = $totalGross >= 600;

        return [
            'year' => $year,
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'email' => $worker->email,
            ],
            'summary' => [
                'total_gross_earnings' => $earnings->sum('gross_amount'),
                'total_net_earnings' => $earnings->sum('net_amount'),
                'total_tax_withheld' => $earnings->sum('tax_withheld'),
                'total_platform_fees' => $earnings->sum('platform_fee'),
                'total_transactions' => $earnings->count(),
            ],
            'by_type' => $byType,
            'quarters' => $quarters,
            'monthly' => $monthly,
            'tax_info' => [
                'requires_1099' => $requires1099,
                'threshold' => 600,
                'over_threshold_by' => max(0, $totalGross - 600),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get dashboard data for a worker.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(User $worker): array
    {
        $now = now();

        // Current month stats
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthlyEarnings = WorkerEarning::forWorker($worker->id)
            ->inDateRange($monthStart, $monthEnd)
            ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
            ->get();

        // Pending earnings
        $pendingEarnings = WorkerEarning::forWorker($worker->id)
            ->where('status', WorkerEarning::STATUS_PENDING)
            ->get();

        // Year to date
        $ytdData = $this->getYearToDateEarnings($worker);

        // Comparison with last month
        $comparison = $this->compareEarnings($worker, 'this_month');

        // Last 6 months for chart
        $chartData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mStart = $now->copy()->subMonths($i)->startOfMonth();
            $mEnd = $now->copy()->subMonths($i)->endOfMonth();

            $monthData = WorkerEarning::forWorker($worker->id)
                ->inDateRange($mStart, $mEnd)
                ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
                ->selectRaw('SUM(net_amount) as total')
                ->first();

            $chartData->push([
                'month' => $mStart->format('M'),
                'amount' => $monthData->total ?? 0,
            ]);
        }

        // Recent transactions
        $recentTransactions = WorkerEarning::forWorker($worker->id)
            ->with('shift.business')
            ->orderBy('earned_date', 'desc')
            ->limit(10)
            ->get();

        return [
            'current_month' => [
                'gross_earnings' => $monthlyEarnings->sum('gross_amount'),
                'net_earnings' => $monthlyEarnings->sum('net_amount'),
                'transaction_count' => $monthlyEarnings->count(),
            ],
            'pending' => [
                'total' => $pendingEarnings->sum('net_amount'),
                'count' => $pendingEarnings->count(),
            ],
            'year_to_date' => $ytdData,
            'comparison' => $comparison,
            'chart_data' => $chartData,
            'recent_transactions' => $recentTransactions,
            'avg_hourly_rate' => $this->getAverageHourlyRate($worker, 30),
        ];
    }

    /**
     * Calculate platform fee based on gross amount.
     */
    protected function calculatePlatformFee(float $grossAmount): float
    {
        $feeRate = config('earnings.platform_fee_rate', 0.10); // Default 10%

        return round($grossAmount * $feeRate, 2);
    }

    /**
     * Calculate tax withholding based on worker settings.
     */
    protected function calculateTaxWithholding(User $worker, float $grossAmount): float
    {
        if (! config('earnings.tax_withholding_enabled', true)) {
            return 0;
        }

        // Use worker's effective tax rate if available
        $taxRate = method_exists($worker, 'getEffectiveTaxRate')
            ? $worker->getEffectiveTaxRate() / 100
            : config('earnings.default_tax_rate', 0);

        return round($grossAmount * $taxRate, 2);
    }

    /**
     * Resolve period dates from period string.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePeriodDates(
        string $period,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): array {
        if ($start && $end) {
            return [$start, $end];
        }

        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year' => [
                $now->copy()->subYear()->startOfYear(),
                $now->copy()->subYear()->endOfYear(),
            ],
            'all_time' => [Carbon::createFromTimestamp(0), $now],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Get period boundaries for summary calculation.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function getPeriodBoundaries(string $periodType, Carbon $date): array
    {
        return match ($periodType) {
            EarningsSummary::PERIOD_DAILY => [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ],
            EarningsSummary::PERIOD_WEEKLY => [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ],
            EarningsSummary::PERIOD_MONTHLY => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
            EarningsSummary::PERIOD_YEARLY => [
                $date->copy()->startOfYear(),
                $date->copy()->endOfYear(),
            ],
            default => [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentageChange(float $old, float $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100 : 0;
        }

        return round((($new - $old) / $old) * 100, 2);
    }

    /**
     * Invalidate cached summaries for affected periods.
     */
    protected function invalidateSummariesForDate(User $worker, Carbon $date): void
    {
        // Delete daily summary for the specific date
        EarningsSummary::forWorker($worker->id)
            ->ofType(EarningsSummary::PERIOD_DAILY)
            ->where('period_start', $date->toDateString())
            ->delete();

        // Delete weekly summary containing this date
        EarningsSummary::forWorker($worker->id)
            ->ofType(EarningsSummary::PERIOD_WEEKLY)
            ->where('period_start', '<=', $date->toDateString())
            ->where('period_end', '>=', $date->toDateString())
            ->delete();

        // Delete monthly summary for the month
        EarningsSummary::forWorker($worker->id)
            ->ofType(EarningsSummary::PERIOD_MONTHLY)
            ->where('period_start', $date->copy()->startOfMonth()->toDateString())
            ->delete();

        // Delete yearly summary for the year
        EarningsSummary::forWorker($worker->id)
            ->ofType(EarningsSummary::PERIOD_YEARLY)
            ->where('period_start', $date->copy()->startOfYear()->toDateString())
            ->delete();
    }

    /**
     * Generate CSV export content.
     *
     * @param  Collection<int, WorkerEarning>  $earnings
     * @param  array<string, mixed>  $filters
     */
    protected function generateCsvExport(User $worker, Collection $earnings, array $filters): string
    {
        $csv = [];

        // Header row
        $csv[] = ['Date', 'Type', 'Description', 'Shift', 'Business', 'Gross Amount', 'Platform Fee', 'Tax Withheld', 'Net Amount', 'Currency', 'Status'];

        // Data rows
        foreach ($earnings as $earning) {
            $csv[] = [
                $earning->earned_date->format('Y-m-d'),
                $earning->type_label,
                $earning->description ?? '',
                $earning->shift?->title ?? '',
                $earning->shift?->business?->name ?? '',
                number_format($earning->gross_amount, 2),
                number_format($earning->platform_fee, 2),
                number_format($earning->tax_withheld, 2),
                number_format($earning->net_amount, 2),
                $earning->currency,
                $earning->status_label,
            ];
        }

        // Totals row
        $csv[] = [];
        $csv[] = [
            'TOTALS',
            '',
            '',
            '',
            '',
            number_format($earnings->sum('gross_amount'), 2),
            number_format($earnings->sum('platform_fee'), 2),
            number_format($earnings->sum('tax_withheld'), 2),
            number_format($earnings->sum('net_amount'), 2),
            '',
            '',
        ];

        // Convert to CSV string
        $output = '';
        foreach ($csv as $row) {
            $output .= implode(',', array_map(function ($field) {
                return '"'.str_replace('"', '""', (string) $field).'"';
            }, $row))."\n";
        }

        return $output;
    }

    /**
     * Generate PDF export data.
     *
     * @param  Collection<int, WorkerEarning>  $earnings
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function generatePdfExport(User $worker, Collection $earnings, array $filters): array
    {
        // Return data that can be used to generate PDF via view
        return [
            'type' => 'pdf',
            'worker' => $worker,
            'earnings' => $earnings,
            'filters' => $filters,
            'totals' => [
                'gross_amount' => $earnings->sum('gross_amount'),
                'platform_fee' => $earnings->sum('platform_fee'),
                'tax_withheld' => $earnings->sum('tax_withheld'),
                'net_amount' => $earnings->sum('net_amount'),
                'count' => $earnings->count(),
            ],
            'generated_at' => now(),
        ];
    }
}
