<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\WorkerEarning;
use App\Services\EarningsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * WKR-006: Worker Earnings Controller
 *
 * Handles earnings dashboard, history, tax summary, and export functionality.
 */
class EarningsController extends Controller
{
    public function __construct(
        protected EarningsService $earningsService
    ) {}

    /**
     * Display the earnings dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'this_month');

        // Get dashboard data from service
        $dashboardData = $this->earningsService->getDashboardData($user);

        // Get period-specific data
        $periodData = $this->earningsService->getEarningsByPeriod($user, $period);
        $comparison = $this->earningsService->compareEarnings($user, $period);

        return view('worker.earnings.dashboard', [
            'totalEarnings' => $dashboardData['year_to_date']['gross_earnings'],
            'periodEarnings' => $dashboardData['current_month']['net_earnings'],
            'pendingPayment' => $dashboardData['pending']['total'],
            'hoursWorked' => $dashboardData['year_to_date']['total_hours'],
            'shiftsCompleted' => $dashboardData['year_to_date']['shifts_completed'],
            'earningsGrowth' => $comparison['changes']['net_earnings'],
            'monthlyEarnings' => $dashboardData['chart_data'],
            'recentTransactions' => $dashboardData['recent_transactions'],
            'avgHourlyRate' => $dashboardData['avg_hourly_rate'],
            'period' => $period,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Display detailed earnings history.
     *
     * @return \Illuminate\View\View
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        // Filter parameters
        $period = $request->get('period', 'all');
        $type = $request->get('type');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build query
        $query = WorkerEarning::forWorker($user->id)
            ->with('shift.business')
            ->orderBy('earned_date', 'desc');

        // Apply filters
        if ($type) {
            $query->ofType($type);
        }

        if ($status) {
            $query->withStatus($status);
        }

        // Apply date filters
        if ($startDate && $endDate) {
            $query->inDateRange(Carbon::parse($startDate), Carbon::parse($endDate));
        } elseif ($period !== 'all') {
            [$periodStart, $periodEnd] = $this->resolvePeriodDates($period);
            $query->inDateRange($periodStart, $periodEnd);
        }

        // Paginate results
        $earnings = $query->paginate(20)->withQueryString();

        // Get summary for current filter
        $summaryQuery = WorkerEarning::forWorker($user->id);
        if ($type) {
            $summaryQuery->ofType($type);
        }
        if ($status) {
            $summaryQuery->withStatus($status);
        }
        if ($startDate && $endDate) {
            $summaryQuery->inDateRange(Carbon::parse($startDate), Carbon::parse($endDate));
        } elseif ($period !== 'all') {
            [$periodStart, $periodEnd] = $this->resolvePeriodDates($period);
            $summaryQuery->inDateRange($periodStart, $periodEnd);
        }

        $summary = [
            'total_gross' => $summaryQuery->sum('gross_amount'),
            'total_net' => $summaryQuery->sum('net_amount'),
            'total_fees' => $summaryQuery->sum('platform_fee'),
            'total_taxes' => $summaryQuery->sum('tax_withheld'),
            'count' => $summaryQuery->count(),
        ];

        return view('worker.earnings.history', [
            'earnings' => $earnings,
            'summary' => $summary,
            'types' => WorkerEarning::getTypes(),
            'statuses' => WorkerEarning::getStatuses(),
            'filters' => [
                'period' => $period,
                'type' => $type,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Display tax summary for a year.
     *
     * @return \Illuminate\View\View
     */
    public function taxSummary(Request $request)
    {
        $user = Auth::user();
        $year = $request->get('year', now()->year);

        // Get tax summary from service
        $taxSummary = $this->earningsService->getTaxSummary($user, (int) $year);

        // Get available years (years with earnings)
        $availableYears = WorkerEarning::forWorker($user->id)
            ->selectRaw('YEAR(earned_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present
        if (! in_array(now()->year, $availableYears)) {
            array_unshift($availableYears, now()->year);
        }

        return view('worker.earnings.tax-summary', [
            'taxSummary' => $taxSummary,
            'year' => (int) $year,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Export earnings to CSV or PDF.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,pdf',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:'.implode(',', array_keys(WorkerEarning::getTypes())),
            'status' => 'nullable|in:'.implode(',', array_keys(WorkerEarning::getStatuses())),
            'year' => 'nullable|integer|min:2020|max:'.(now()->year + 1),
        ]);

        $user = Auth::user();
        $format = $request->get('format');

        $filters = [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'year' => $request->get('year'),
        ];

        $result = $this->earningsService->exportEarnings($user, $format, $filters);

        if ($format === 'csv') {
            $filename = 'earnings_'.date('Y-m-d').'.csv';

            return new StreamedResponse(function () use ($result) {
                echo $result;
            }, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        if ($format === 'pdf') {
            // For PDF, we return a view that can be printed/saved as PDF
            // Or integrate with a PDF library like DomPDF
            return view('worker.earnings.export-pdf', $result);
        }

        return response()->json($result);
    }

    /**
     * Get period comparison data (AJAX endpoint).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function comparePeriodsApi(Request $request)
    {
        $request->validate([
            'period' => 'required|in:this_week,this_month,this_quarter,this_year',
        ]);

        $user = Auth::user();
        $comparison = $this->earningsService->compareEarnings($user, $request->get('period'));

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }

    /**
     * Get chart data for earnings visualization (AJAX endpoint).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function chartDataApi(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:6_months,12_months,this_year',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $user = Auth::user();
        $period = $request->get('period', '6_months');
        $groupBy = $request->get('group_by', 'month');

        $chartData = $this->buildChartData($user, $period, $groupBy);

        return response()->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }

    /**
     * Refresh earnings summary (manual trigger).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refreshSummary()
    {
        $user = Auth::user();

        try {
            $stats = $this->earningsService->refreshAllSummaries($user);

            return redirect()->back()->with('success', 'Earnings summaries refreshed successfully. Updated '
                .array_sum($stats).' period summaries.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to refresh summaries. Please try again.');
        }
    }

    /**
     * Resolve period dates from string.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolvePeriodDates(string $period): array
    {
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
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Build chart data for visualization.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildChartData($user, string $period, string $groupBy): array
    {
        $now = now();
        $chartData = [];

        // Determine date range
        [$startDate, $endDate] = match ($period) {
            '6_months' => [$now->copy()->subMonths(6)->startOfMonth(), $now],
            '12_months' => [$now->copy()->subMonths(12)->startOfMonth(), $now],
            'this_year' => [$now->copy()->startOfYear(), $now],
            default => [$now->copy()->subMonths(6)->startOfMonth(), $now],
        };

        // Build data based on grouping
        if ($groupBy === 'month') {
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $mStart = $current->copy()->startOfMonth();
                $mEnd = $current->copy()->endOfMonth();

                $earnings = WorkerEarning::forWorker($user->id)
                    ->inDateRange($mStart, $mEnd)
                    ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
                    ->get();

                $chartData[] = [
                    'label' => $mStart->format('M Y'),
                    'gross' => $earnings->sum('gross_amount'),
                    'net' => $earnings->sum('net_amount'),
                    'count' => $earnings->count(),
                ];

                $current->addMonth();
            }
        } elseif ($groupBy === 'week') {
            $current = $startDate->copy()->startOfWeek();
            while ($current <= $endDate) {
                $wStart = $current->copy();
                $wEnd = $current->copy()->endOfWeek();

                $earnings = WorkerEarning::forWorker($user->id)
                    ->inDateRange($wStart, $wEnd)
                    ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
                    ->get();

                $chartData[] = [
                    'label' => 'Week of '.$wStart->format('M j'),
                    'gross' => $earnings->sum('gross_amount'),
                    'net' => $earnings->sum('net_amount'),
                    'count' => $earnings->count(),
                ];

                $current->addWeek();
            }
        } else {
            // Daily
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $earnings = WorkerEarning::forWorker($user->id)
                    ->where('earned_date', $current->toDateString())
                    ->whereIn('status', [WorkerEarning::STATUS_APPROVED, WorkerEarning::STATUS_PAID])
                    ->get();

                $chartData[] = [
                    'label' => $current->format('M j'),
                    'gross' => $earnings->sum('gross_amount'),
                    'net' => $earnings->sum('net_amount'),
                    'count' => $earnings->count(),
                ];

                $current->addDay();
            }
        }

        return $chartData;
    }
}
