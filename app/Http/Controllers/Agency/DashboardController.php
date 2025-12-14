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
        $availableShifts = Shift::where('status', 'open')
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

        return view('agency.dashboard', compact(
            'totalWorkers',
            'activeWorkers',
            'totalAssignments',
            'completedAssignments',
            'totalEarnings',
            'recentAssignments',
            'availableShifts',
            'stats'
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
                'availableShifts' => collect()
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }
}
