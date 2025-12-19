<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyWorker;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display agency dashboard.
     */
    public function index()
    {
        try {
            $agency = Auth::user();

            // Get agency workers stats
            $totalWorkers = AgencyWorker::where('agency_id', $agency->id)
                ->where('status', 'active')
                ->count();

            // Optimized: Use join instead of subquery
            $activeWorkers = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->where('agency_workers.status', 'active')
                ->whereIn('shift_assignments.status', ['assigned', 'in_progress'])
                ->distinct('shift_assignments.worker_id')
                ->count('shift_assignments.worker_id');

            // Optimized: Use join instead of subquery
            $totalAssignments = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->count();

            $completedAssignments = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->where('shift_assignments.status', 'completed')
                ->count();

            // Optimized: Use join instead of subquery
            $totalEarnings = DB::table('shift_payments')
                ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                ->join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->where('shift_payments.status', 'released')
                ->sum('shift_payments.agency_commission');

            // Optimized: Use join instead of subquery
            $recentAssignments = ShiftAssignment::with(['shift', 'worker'])
                ->join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->select('shift_assignments.*')
                ->orderBy('shift_assignments.created_at', 'desc')
                ->limit(10)
                ->get();

            // Available shifts that match agency workers' skills
            // Eager load business relationship to prevent N+1 queries when displaying shift details
            $availableShifts = Shift::with('business')
                ->where('status', 'open')
                ->where('shift_date', '>=', now())
                ->where('allow_agencies', true)
                ->orderBy('shift_date', 'asc')
                ->limit(10)
                ->get();

            // Calculate stats for view
            $stats = [
                'total_workers' => $totalWorkers,
                'active_placements' => $activeWorkers, // This is actually active placements count
                'available_workers' => $totalWorkers - $activeWorkers, // Available = total - active
                'total_assignments' => $totalAssignments,
                'completed_assignments' => $completedAssignments,
                'revenue_this_month' => DB::table('shift_payments')
                    ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                    ->join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                    ->where('agency_workers.agency_id', $agency->id)
                    ->whereMonth('shift_payments.payout_completed_at', now()->month)
                    ->where('shift_payments.status', 'released')
                    ->sum('shift_payments.agency_commission'),
                'total_placements_month' => ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                    ->where('agency_workers.agency_id', $agency->id)
                    ->whereMonth('shift_assignments.created_at', now()->month)
                    ->count(),
                'avg_worker_rating' => 0, // TODO: Calculate from worker ratings
            ];

            // Calculate profile completeness for onboarding progress
            $onboardingProgress = $this->calculateProfileCompleteness($agency);

            // Get notification and message counts for unified dashboard layout
            $unreadNotifications = 0; // Placeholder - custom notification system
            $unreadMessages = $agency->unreadMessages ?? 0; // Placeholder until Messages model is ready

            // Prepare metrics array for unified dashboard layout
            $metrics = [
                [
                    'label' => 'Total Workers',
                    'value' => $totalWorkers,
                    'subtitle' => 'Active workers',
                    'icon' => 'M17 20h5v-2a3 3 0 00-3-3h-5v5zm-9-8h4V7H8v5zm2 8h4v-5H10v5z',
                ],
                [
                    'label' => 'Active Placements',
                    'value' => $activeWorkers,
                    'subtitle' => 'Currently working',
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                ],
                [
                    'label' => 'Completed',
                    'value' => $completedAssignments,
                    'subtitle' => 'Total assignments',
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
                [
                    'label' => 'Commission Earned',
                    'value' => '$'.number_format($totalEarnings, 2),
                    'subtitle' => 'All time',
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                ],
            ];

            return view('agency.dashboard', compact(
                'totalWorkers',
                'activeWorkers',
                'totalAssignments',
                'completedAssignments',
                'totalEarnings',
                'recentAssignments',
                'availableShifts',
                'stats',
                'metrics',
                'onboardingProgress',
                'unreadNotifications',
                'unreadMessages'
            ));
        } catch (\Exception $e) {
            \Log::error('Agency Dashboard Error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('agency.dashboard', [
                'totalWorkers' => 0,
                'activeWorkers' => 0,
                'totalAssignments' => 0,
                'completedAssignments' => 0,
                'totalEarnings' => 0,
                'recentAssignments' => collect(),
                'availableShifts' => collect(),
                'stats' => [
                    'total_workers' => 0,
                    'active_placements' => 0,
                    'available_workers' => 0,
                    'total_assignments' => 0,
                    'completed_assignments' => 0,
                    'revenue_this_month' => 0,
                    'total_placements_month' => 0,
                    'avg_worker_rating' => 0,
                ],
                'metrics' => [
                    ['label' => 'Total Workers', 'value' => 0, 'subtitle' => 'Active workers', 'icon' => 'M17 20h5v-2a3 3 0 00-3-3h-5v5zm-9-8h4V7H8v5zm2 8h4v-5H10v5z'],
                    ['label' => 'Active Placements', 'value' => 0, 'subtitle' => 'Currently working', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['label' => 'Completed', 'value' => 0, 'subtitle' => 'Total assignments', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Commission Earned', 'value' => '$0.00', 'subtitle' => 'All time', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ],
                'onboardingProgress' => 0,
                'unreadNotifications' => 0,
                'unreadMessages' => 0,
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }

    /**
     * Calculate profile completeness percentage
     *
     * @param  \App\Models\User  $user
     * @return int
     */
    private function calculateProfileCompleteness($user)
    {
        $completeness = 0;

        // Base user fields
        if ($user->name) {
            $completeness += 15;
        }
        if ($user->email) {
            $completeness += 15;
        }
        if ($user->avatar && $user->avatar != 'avatar.jpg') {
            $completeness += 10;
        }

        // Agency profile fields
        if ($user->agencyProfile) {
            $profile = $user->agencyProfile;
            if ($profile->agency_name) {
                $completeness += 15;
            }
            if ($profile->agency_type) {
                $completeness += 10;
            }
            if ($profile->address) {
                $completeness += 10;
            }
            if ($profile->city && $profile->state) {
                $completeness += 10;
            }
            if ($profile->phone) {
                $completeness += 10;
            }
            if ($profile->description) {
                $completeness += 5;
            }
        }

        return min($completeness, 100);
    }

    /**
     * Display agency assignments.
     */
    public function assignments()
    {
        $user = Auth::user();
        $assignments = []; // Placeholder for now - logic similar to index

        return view('agency.assignments', compact('assignments'));
    }

    public function shiftsBrowse()
    {
        // Reuse the main shifts index view, potentially passing a flag if needed
        return view('shifts.index');
    }

    public function shiftsView($id)
    {
        // Reuse the main shift detail view
        $shift = Shift::findOrFail($id);

        return view('shifts.show', compact('shift'));
    }

    public function workersIndex()
    {
        // Fetch agency workers
        $workers = AgencyWorker::where('agency_id', Auth::id())->with('user')->paginate(20);

        return view('agency.workers.index', compact('workers'));
    }

    public function commissions()
    {
        return view('transactions.index');
    }

    /**
     * Display agency profile page.
     */
    public function profile(): \Illuminate\View\View
    {
        return view('agency.profile');
    }

    /**
     * Display agency branding settings.
     */
    public function branding(): \Illuminate\View\View
    {
        return view('agency.branding');
    }

    /**
     * Display agency compliance page.
     */
    public function compliance(): \Illuminate\View\View
    {
        return view('agency.compliance');
    }

    /**
     * Display agency team page.
     */
    public function team(): \Illuminate\View\View
    {
        return view('agency.team');
    }

    // Analytics routes
    public function analyticsDashboard(): \Illuminate\View\View
    {
        return view('agency.analytics.dashboard');
    }

    public function analyticsReports(): \Illuminate\View\View
    {
        return view('agency.analytics.reports');
    }

    public function analyticsRevenue(): \Illuminate\View\View
    {
        return view('agency.analytics.revenue');
    }

    public function analyticsUtilization(): \Illuminate\View\View
    {
        return view('agency.analytics.utilization');
    }

    // Finance routes
    public function financeOverview(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $agencyProfile = $agency->agencyProfile;

        // Get worker IDs for this agency
        $workerIds = AgencyWorker::where('agency_id', $agency->id)->pluck('worker_id');

        // Calculate financial totals
        $totalEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.status', 'paid_out')
            ->sum('shift_payments.agency_commission');

        $pendingCommission = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereIn('shift_payments.status', ['in_escrow', 'released'])
            ->sum('shift_payments.agency_commission');

        $monthlyEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereMonth('shift_payments.payout_completed_at', now()->month)
            ->whereYear('shift_payments.payout_completed_at', now()->year)
            ->sum('shift_payments.agency_commission');

        $totalPaidOut = $agencyProfile->total_payouts_amount ?? 0;

        // Recent commissions
        $recentCommissions = \App\Models\ShiftPayment::with(['worker', 'assignment.shift'])
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereNotNull('shift_payments.agency_commission')
            ->select('shift_payments.*')
            ->orderBy('shift_payments.created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent payouts (from profile)
        $recentPayouts = collect();
        if ($agencyProfile->last_payout_at) {
            $recentPayouts = collect([[
                'id' => $agencyProfile->total_payouts_count ?? 1,
                'date' => $agencyProfile->last_payout_at,
                'amount' => $agencyProfile->last_payout_amount ?? 0,
                'status' => $agencyProfile->last_payout_status ?? 'paid',
            ]]);
        }

        // Stats
        $shiftsFilledThisMonth = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->where('shift_assignments.status', 'completed')
            ->whereMonth('shift_assignments.updated_at', now()->month)
            ->count();

        $workersPlacedThisMonth = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->whereMonth('shift_assignments.created_at', now()->month)
            ->distinct('shift_assignments.worker_id')
            ->count('shift_assignments.worker_id');

        $avgCommissionPerShift = $shiftsFilledThisMonth > 0 ? $monthlyEarnings / $shiftsFilledThisMonth : 0;

        return view('agency.finance.overview', [
            'stripeConnected' => $agencyProfile->hasCompletedStripeOnboarding(),
            'totalEarnings' => $totalEarnings,
            'pendingCommission' => $pendingCommission,
            'monthlyEarnings' => $monthlyEarnings,
            'totalPaidOut' => $totalPaidOut,
            'recentCommissions' => $recentCommissions,
            'recentPayouts' => $recentPayouts,
            'payoutSchedule' => 'weekly',
            'commissionRate' => $agencyProfile->commission_rate ?? 10,
            'totalPayoutsCount' => $agencyProfile->total_payouts_count ?? 0,
            'shiftsFilledThisMonth' => $shiftsFilledThisMonth,
            'workersPlacedThisMonth' => $workersPlacedThisMonth,
            'avgCommissionPerShift' => $avgCommissionPerShift,
        ]);
    }

    public function financeCommissions(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $workerIds = AgencyWorker::where('agency_id', $agency->id)->pluck('worker_id');

        // Build query for commissions
        $query = \App\Models\ShiftPayment::with(['worker', 'assignment.shift.business'])
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereNotNull('shift_payments.agency_commission')
            ->select('shift_payments.*');

        // Apply filters
        if (request('status')) {
            $query->where('shift_payments.status', request('status'));
        }

        if (request('period')) {
            $query->where('shift_payments.created_at', '>=', match (request('period')) {
                'today' => now()->startOfDay(),
                'week' => now()->startOfWeek(),
                'month' => now()->startOfMonth(),
                'quarter' => now()->startOfQuarter(),
                default => now()->subYears(10),
            });
        }

        $commissions = $query->orderBy('shift_payments.created_at', 'desc')->paginate(15);

        // Calculate summary totals
        $paidCommissions = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.status', 'paid_out')
            ->sum('shift_payments.agency_commission');

        $pendingCommissions = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.status', 'released')
            ->sum('shift_payments.agency_commission');

        $escrowCommissions = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.status', 'in_escrow')
            ->sum('shift_payments.agency_commission');

        $thisMonthCommissions = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereMonth('shift_payments.created_at', now()->month)
            ->sum('shift_payments.agency_commission');

        return view('agency.finance.commissions', [
            'commissions' => $commissions,
            'paidCommissions' => $paidCommissions,
            'pendingCommissions' => $pendingCommissions,
            'escrowCommissions' => $escrowCommissions,
            'thisMonthCommissions' => $thisMonthCommissions,
        ]);
    }

    public function financePayroll(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $workerIds = AgencyWorker::where('agency_id', $agency->id)->pluck('worker_id');

        // Worker payments query
        $query = \App\Models\ShiftPayment::with(['worker', 'assignment.shift'])
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->select('shift_payments.*');

        $workerPayments = $query->orderBy('shift_payments.created_at', 'desc')->paginate(15);

        // Calculate totals
        $totalPaidToWorkers = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.status', 'paid_out')
            ->sum('shift_payments.worker_amount');

        $pendingWorkerPayments = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereIn('shift_payments.status', ['in_escrow', 'released'])
            ->sum('shift_payments.worker_amount');

        $thisMonthPayroll = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereMonth('shift_payments.payout_completed_at', now()->month)
            ->sum('shift_payments.worker_amount');

        $activeWorkersCount = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        // Worker summary
        $workerSummary = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->join('users', 'shift_assignments.worker_id', '=', 'users.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->groupBy('users.id', 'users.name')
            ->select('users.name', DB::raw('SUM(shift_payments.worker_amount) as total_earned'), DB::raw('COUNT(*) as shifts_count'))
            ->orderBy('total_earned', 'desc')
            ->limit(9)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'total_earned' => $item->total_earned ?? 0,
                'shifts_count' => $item->shifts_count,
            ]);

        return view('agency.finance.payroll', [
            'workerPayments' => $workerPayments,
            'totalPaidToWorkers' => $totalPaidToWorkers,
            'pendingWorkerPayments' => $pendingWorkerPayments,
            'thisMonthPayroll' => $thisMonthPayroll,
            'activeWorkersCount' => $activeWorkersCount,
            'workerSummary' => $workerSummary,
        ]);
    }

    public function financeInvoices(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $agencyProfile = $agency->agencyProfile;

        // For now, generate mock invoices based on payouts
        $invoices = collect();
        $totalInvoiced = $agencyProfile->total_payouts_amount ?? 0;
        $totalInvoicesCount = $agencyProfile->total_payouts_count ?? 0;
        $thisMonthInvoiced = 0;

        if ($agencyProfile->last_payout_at && $agencyProfile->last_payout_at->isCurrentMonth()) {
            $thisMonthInvoiced = $agencyProfile->last_payout_amount ?? 0;
        }

        return view('agency.finance.invoices', [
            'invoices' => $invoices,
            'totalInvoiced' => $totalInvoiced,
            'totalInvoicesCount' => $totalInvoicesCount,
            'thisMonthInvoiced' => $thisMonthInvoiced,
        ]);
    }

    public function financeSettlements(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $agencyProfile = $agency->agencyProfile;
        $workerIds = AgencyWorker::where('agency_id', $agency->id)->pluck('worker_id');

        $stripeConnected = $agencyProfile->hasCompletedStripeOnboarding();

        $totalSettled = $agencyProfile->total_payouts_amount ?? 0;

        $pendingPayout = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereIn('shift_payments.status', ['in_escrow', 'released'])
            ->sum('shift_payments.agency_commission');

        $nextPayoutDate = $stripeConnected ? now()->next('Friday')->format('M d') : null;
        $totalPayoutsCount = $agencyProfile->total_payouts_count ?? 0;

        // Generate settlement list from payout data
        $settlements = collect();
        if ($agencyProfile->last_payout_at) {
            $settlements->push([
                'id' => $totalPayoutsCount,
                'date' => $agencyProfile->last_payout_at,
                'amount' => $agencyProfile->last_payout_amount ?? 0,
                'status' => $agencyProfile->last_payout_status === 'paid' ? 'completed' : $agencyProfile->last_payout_status,
                'shifts_count' => 1,
            ]);
        }

        $lastPayout = null;
        if ($agencyProfile->last_payout_at) {
            $lastPayout = [
                'amount' => $agencyProfile->last_payout_amount ?? 0,
                'date' => $agencyProfile->last_payout_at,
            ];
        }

        return view('agency.finance.settlements', [
            'stripeConnected' => $stripeConnected,
            'totalSettled' => $totalSettled,
            'pendingPayout' => $pendingPayout,
            'nextPayoutDate' => $nextPayoutDate,
            'totalPayoutsCount' => $totalPayoutsCount,
            'settlements' => $settlements,
            'payoutSchedule' => 'weekly',
            'payoutDay' => 'Friday',
            'payoutCurrency' => $agencyProfile->stripe_default_currency ?? 'USD',
            'bankAccount' => null, // Would need Stripe API call to get this
            'lastPayout' => $lastPayout,
        ]);
    }

    public function financeReports(): \Illuminate\View\View
    {
        $agency = Auth::user();
        $workerIds = AgencyWorker::where('agency_id', $agency->id)->pluck('worker_id');

        // Monthly summary
        $monthlyEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereMonth('shift_payments.created_at', now()->month)
            ->sum('shift_payments.agency_commission');

        $monthlyShifts = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->where('shift_assignments.status', 'completed')
            ->whereMonth('shift_assignments.created_at', now()->month)
            ->count();

        $monthlyWorkers = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->whereMonth('shift_assignments.created_at', now()->month)
            ->distinct('shift_assignments.worker_id')
            ->count('shift_assignments.worker_id');

        // Quarterly summary
        $quarterlyEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->where('shift_payments.created_at', '>=', now()->startOfQuarter())
            ->sum('shift_payments.agency_commission');

        $quarterlyShifts = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->where('shift_assignments.status', 'completed')
            ->where('shift_assignments.created_at', '>=', now()->startOfQuarter())
            ->count();

        // YTD summary
        $ytdEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', $workerIds)
            ->whereYear('shift_payments.created_at', now()->year)
            ->sum('shift_payments.agency_commission');

        $ytdShifts = ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
            ->where('agency_workers.agency_id', $agency->id)
            ->where('shift_assignments.status', 'completed')
            ->whereYear('shift_assignments.created_at', now()->year)
            ->count();

        $monthsElapsed = now()->month;
        $avgMonthly = $monthsElapsed > 0 ? $ytdEarnings / $monthsElapsed : 0;

        return view('agency.finance.reports', [
            'monthlySummary' => [
                'earnings' => $monthlyEarnings,
                'shifts' => $monthlyShifts,
                'workers' => $monthlyWorkers,
            ],
            'quarterlySummary' => [
                'earnings' => $quarterlyEarnings,
                'shifts' => $quarterlyShifts,
                'growth' => 0, // Would need previous quarter data to calculate
            ],
            'ytdSummary' => [
                'earnings' => $ytdEarnings,
                'shifts' => $ytdShifts,
                'avgMonthly' => $avgMonthly,
            ],
            'taxDocuments' => collect(),
            'recentReports' => collect(),
        ]);
    }

    // Placements routes
    public function placementsActive(): \Illuminate\View\View
    {
        return view('agency.placements.active');
    }

    public function placementsHistory(): \Illuminate\View\View
    {
        return view('agency.placements.history');
    }

    // Shifts routes
    public function shiftsAssign(): \Illuminate\View\View
    {
        return view('agency.shifts.assign');
    }

    public function shiftsCalendar(): \Illuminate\View\View
    {
        return view('agency.shifts.calendar');
    }

    // Venues routes
    public function venuesIndex(): \Illuminate\View\View
    {
        return view('agency.venues.index');
    }

    public function venuesContracts(): \Illuminate\View\View
    {
        return view('agency.venues.contracts');
    }

    public function venuesPerformance(): \Illuminate\View\View
    {
        return view('agency.venues.performance');
    }

    public function venuesRequests(): \Illuminate\View\View
    {
        return view('agency.venues.requests');
    }

    // Workers routes
    public function workersCreate(): \Illuminate\View\View
    {
        return view('agency.workers.create');
    }

    public function workersPending(): \Illuminate\View\View
    {
        return view('agency.workers.pending');
    }

    public function workersCompliance(): \Illuminate\View\View
    {
        return view('agency.workers.compliance');
    }

    public function workersDocuments(): \Illuminate\View\View
    {
        return view('agency.workers.documents');
    }

    public function workersGroups(): \Illuminate\View\View
    {
        return view('agency.workers.groups');
    }
}
