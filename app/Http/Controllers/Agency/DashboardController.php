<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\AgencyWorker;
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
                    'value' => '$' . number_format($totalEarnings, 2),
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
            \Log::error('Agency Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
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
                'unreadMessages' => 0
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
        if ($user->name)
            $completeness += 15;
        if ($user->email)
            $completeness += 15;
        if ($user->avatar && $user->avatar != 'avatar.jpg')
            $completeness += 10;

        // Agency profile fields
        if ($user->agencyProfile) {
            $profile = $user->agencyProfile;
            if ($profile->agency_name)
                $completeness += 15;
            if ($profile->agency_type)
                $completeness += 10;
            if ($profile->address)
                $completeness += 10;
            if ($profile->city && $profile->state)
                $completeness += 10;
            if ($profile->phone)
                $completeness += 10;
            if ($profile->description)
                $completeness += 5;
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
}
