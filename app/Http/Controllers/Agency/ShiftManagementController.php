<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\AgencyWorker;
use App\Services\ShiftMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftManagementController extends Controller
{
    protected $matchingService;

    public function __construct(ShiftMatchingService $matchingService)
    {
        $this->middleware(['auth', 'agency']);
        $this->matchingService = $matchingService;
    }

    /**
     * List shifts for agency - shows both assigned shifts and available shifts.
     */
    public function index(Request $request)
    {
        $agency = Auth::user();

        // Get shifts that agency workers are assigned to
        $assignedShifts = Shift::with(['business', 'assignments'])
            ->whereHas('assignments', function($query) use ($agency) {
                $query->whereIn('worker_id', function($q) use ($agency) {
                    $q->select('worker_id')
                        ->from('agency_workers')
                        ->where('agency_id', $agency->id);
                });
            })
            ->orderBy('shift_date', 'desc')
            ->paginate(15);

        // Get available shifts count
        $availableShiftsCount = Shift::where('status', 'open')
            ->where('shift_date', '>=', now())
            ->where('allow_agencies', true)
            ->count();

        // Get agency workers count
        $workersCount = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        return view('agency.shifts.index', compact(
            'assignedShifts',
            'availableShiftsCount',
            'workersCount'
        ));
    }

    /**
     * Agency dashboard.
     */
    public function dashboard()
    {
        $agency = Auth::user();

        // Get agency workers
        $totalWorkers = AgencyWorker::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->count();

        $activeWorkers = ShiftAssignment::whereIn('worker_id', function($query) use ($agency) {
            $query->select('worker_id')
                ->from('agency_workers')
                ->where('agency_id', $agency->id)
                ->where('status', 'active');
        })
        ->whereIn('status', ['assigned', 'in_progress'])
        ->distinct('worker_id')
        ->count('worker_id');

        // Get shift assignments for agency workers
        $totalAssignments = ShiftAssignment::whereIn('worker_id', function($query) use ($agency) {
            $query->select('worker_id')
                ->from('agency_workers')
                ->where('agency_id', $agency->id);
        })->count();

        $completedAssignments = ShiftAssignment::whereIn('worker_id', function($query) use ($agency) {
            $query->select('worker_id')
                ->from('agency_workers')
                ->where('agency_id', $agency->id);
        })
        ->where('status', 'completed')
        ->count();

        // Calculate total earnings (commission)
        $totalEarnings = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->whereIn('shift_assignments.worker_id', function($query) use ($agency) {
                $query->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->where('shift_payments.status', 'released')
            ->sum('shift_payments.agency_commission');

        // Recent assignments
        $recentAssignments = ShiftAssignment::with(['shift', 'worker'])
            ->whereIn('worker_id', function($query) use ($agency) {
                $query->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Available shifts that match agency workers' skills
        $availableShifts = Shift::where('status', 'open')
            ->where('shift_date', '>=', now())
            ->orderBy('shift_date', 'asc')
            ->limit(10)
            ->get();

        return view('agency.dashboard', compact(
            'totalWorkers',
            'activeWorkers',
            'totalAssignments',
            'completedAssignments',
            'totalEarnings',
            'recentAssignments',
            'availableShifts'
        ));
    }

    /**
     * List agency's workers.
     */
    public function workers(Request $request)
    {
        $agency = Auth::user();
        $status = $request->get('status', 'active');

        $workers = AgencyWorker::with(['worker.workerProfile', 'worker.skills'])
            ->where('agency_id', $agency->id);

        if ($status !== 'all') {
            $workers->where('status', $status);
        }

        $workers = $workers->paginate(20);

        // Get stats for each worker
        foreach ($workers as $agencyWorker) {
            $worker = $agencyWorker->worker;
            $agencyWorker->shifts_completed = ShiftAssignment::where('worker_id', $worker->id)
                ->where('status', 'completed')
                ->count();
            $agencyWorker->current_assignments = ShiftAssignment::where('worker_id', $worker->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
        }

        return view('agency.workers.index', compact('workers', 'status'));
    }

    /**
     * Add worker to agency.
     */
    public function addWorker(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $agency = Auth::user();
        $worker = User::find($request->worker_id);

        // Verify it's a worker
        if (!$worker->isWorker()) {
            return back()->with('error', 'User is not a worker.');
        }

        // Check if already exists
        $existing = AgencyWorker::where('agency_id', $agency->id)
            ->where('worker_id', $worker->id)
            ->first();

        if ($existing) {
            return back()->with('error', 'Worker is already in your agency.');
        }

        AgencyWorker::create([
            'agency_id' => $agency->id,
            'worker_id' => $worker->id,
            'commission_rate' => $request->commission_rate,
            'status' => 'active',
            'notes' => $request->notes,
            'added_at' => now(),
        ]);

        return back()->with('success', 'Worker added to your agency successfully.');
    }

    /**
     * Remove worker from agency.
     */
    public function removeWorker($workerId)
    {
        $agency = Auth::user();

        $agencyWorker = AgencyWorker::where('agency_id', $agency->id)
            ->where('worker_id', $workerId)
            ->first();

        if (!$agencyWorker) {
            return back()->with('error', 'Worker not found in your agency.');
        }

        // Check if worker has active assignments
        $activeAssignments = ShiftAssignment::where('worker_id', $workerId)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();

        if ($activeAssignments > 0) {
            return back()->with('error', 'Cannot remove worker with active shift assignments.');
        }

        $agencyWorker->update(['status' => 'removed', 'removed_at' => now()]);

        return back()->with('success', 'Worker removed from your agency.');
    }

    /**
     * Browse available shifts.
     */
    public function browseShifts(Request $request)
    {
        $query = Shift::where('status', 'open')
            ->where('shift_date', '>=', now());

        // Filter by industry
        if ($request->has('industry') && $request->industry !== 'all') {
            $query->where('industry', $request->industry);
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->where('shift_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('shift_date', '<=', $request->date_to);
        }

        // Filter by location
        if ($request->has('city')) {
            $query->where('location_city', 'LIKE', '%' . $request->city . '%');
        }

        // Filter by minimum rate
        if ($request->has('min_rate')) {
            $query->where('final_rate', '>=', $request->min_rate);
        }

        $shifts = $query->orderBy('shift_date', 'asc')
            ->paginate(20);

        return view('agency.shifts.browse', compact('shifts'));
    }

    /**
     * View shift details and assign worker.
     */
    public function viewShift($shiftId)
    {
        $shift = Shift::with(['business', 'applications', 'assignments'])
            ->findOrFail($shiftId);

        $agency = Auth::user();

        // Get available agency workers
        $availableWorkers = AgencyWorker::with(['worker.workerProfile', 'worker.skills'])
            ->where('agency_id', $agency->id)
            ->where('status', 'active')
            ->get()
            ->map(function($agencyWorker) use ($shift) {
                $worker = $agencyWorker->worker;

                // Calculate match score
                $agencyWorker->match_score = $this->matchingService
                    ->calculateWorkerShiftMatch($worker, $shift);

                // Check availability
                $hasConflict = ShiftAssignment::where('worker_id', $worker->id)
                    ->whereIn('status', ['assigned', 'in_progress'])
                    ->whereHas('shift', function($q) use ($shift) {
                        $q->where('shift_date', $shift->shift_date)
                            ->where(function($q2) use ($shift) {
                                $q2->whereBetween('start_time', [$shift->start_time, $shift->end_time])
                                    ->orWhereBetween('end_time', [$shift->start_time, $shift->end_time]);
                            });
                    })
                    ->exists();

                $agencyWorker->is_available = !$hasConflict;

                return $agencyWorker;
            })
            ->sortByDesc('match_score');

        return view('agency.shifts.view', compact('shift', 'availableWorkers'));
    }

    /**
     * Assign worker to shift.
     */
    public function assignWorker(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'worker_id' => 'required|exists:users,id',
        ]);

        $agency = Auth::user();
        $shift = Shift::findOrFail($request->shift_id);
        $worker = User::findOrFail($request->worker_id);

        // Verify worker belongs to this agency
        $agencyWorker = AgencyWorker::where('agency_id', $agency->id)
            ->where('worker_id', $worker->id)
            ->where('status', 'active')
            ->first();

        if (!$agencyWorker) {
            return back()->with('error', 'Worker does not belong to your agency.');
        }

        // Check if shift is full
        if ($shift->isFull()) {
            return back()->with('error', 'Shift is already fully staffed.');
        }

        // Check for conflicting assignments
        $conflict = ShiftAssignment::where('worker_id', $worker->id)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->whereHas('shift', function($q) use ($shift) {
                $q->where('shift_date', $shift->shift_date)
                    ->where(function($q2) use ($shift) {
                        $q2->whereBetween('start_time', [$shift->start_time, $shift->end_time])
                            ->orWhereBetween('end_time', [$shift->start_time, $shift->end_time]);
                    });
            })
            ->exists();

        if ($conflict) {
            return back()->with('error', 'Worker has a conflicting shift assignment.');
        }

        // Check if worker already applied
        $application = ShiftApplication::where('shift_id', $shift->id)
            ->where('worker_id', $worker->id)
            ->first();

        if ($application) {
            // Use existing application
            $application->update([
                'status' => 'accepted',
                'responded_at' => now(),
            ]);
        }

        // Create assignment
        $assignment = ShiftAssignment::create([
            'shift_id' => $shift->id,
            'worker_id' => $worker->id,
            'agency_id' => $agency->id,
            'status' => 'assigned',
            'assigned_at' => now(),
            'payment_status' => 'pending',
            'agency_commission_rate' => $agencyWorker->commission_rate,
        ]);

        // Increment filled workers
        $shift->increment('filled_workers');

        // Update shift status if now full
        if ($shift->fresh()->isFull()) {
            $shift->update([
                'status' => 'assigned',
                'filled_at' => now(),
            ]);
        }

        return redirect()->route('agency.assignments')
            ->with('success', 'Worker assigned to shift successfully.');
    }

    /**
     * View all assignments for agency workers.
     */
    public function assignments(Request $request)
    {
        $agency = Auth::user();
        $status = $request->get('status', 'all');

        $query = ShiftAssignment::with(['shift', 'worker', 'shiftPayment'])
            ->whereIn('worker_id', function($q) use ($agency) {
                $q->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $assignments = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('agency.assignments.index', compact('assignments', 'status'));
    }

    /**
     * View commission tracking and earnings.
     */
    public function commissions(Request $request)
    {
        $agency = Auth::user();

        // Date filter
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->endOfMonth()->format('Y-m-d'));

        // Get earnings by worker
        $earningsByWorker = DB::table('shift_payments')
            ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
            ->join('users', 'shift_assignments.worker_id', '=', 'users.id')
            ->join('agency_workers', function($join) use ($agency) {
                $join->on('shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                    ->where('agency_workers.agency_id', '=', $agency->id);
            })
            ->whereBetween('shift_payments.created_at', [$dateFrom, $dateTo])
            ->where('shift_payments.status', 'released')
            ->select(
                'users.name as worker_name',
                DB::raw('COUNT(*) as shifts_completed'),
                DB::raw('SUM(shift_payments.worker_amount) as worker_earnings'),
                DB::raw('SUM(shift_payments.agency_commission) as commission_earned')
            )
            ->groupBy('users.id', 'users.name')
            ->get();

        // Total stats
        $totalCommission = $earningsByWorker->sum('commission_earned');
        $totalShifts = $earningsByWorker->sum('shifts_completed');
        $totalWorkerEarnings = $earningsByWorker->sum('worker_earnings');

        return view('agency.commissions.index', compact(
            'earningsByWorker',
            'totalCommission',
            'totalShifts',
            'totalWorkerEarnings',
            'dateFrom',
            'dateTo'
        ));
    }

    /**
     * Show form to add a worker to the agency.
     */
    public function showAddWorkerForm()
    {
        return view('agency.workers.add');
    }

    /**
     * Create a new placement (assign worker to client shift).
     */
    public function createPlacement()
    {
        $agency = Auth::user();

        // Get available agency workers
        $availableWorkers = AgencyWorker::with(['worker.workerProfile', 'worker.skills'])
            ->where('agency_id', $agency->id)
            ->where('status', 'active')
            ->get();

        // Get available shifts
        $availableShifts = Shift::where('status', 'open')
            ->where('shift_date', '>=', now())
            ->orderBy('shift_date', 'asc')
            ->limit(50)
            ->get();

        return view('agency.placements.create', compact('availableWorkers', 'availableShifts'));
    }

    /**
     * Analytics dashboard.
     */
    public function analytics()
    {
        $agency = Auth::user();

        // Monthly performance
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $shiftsCompleted = ShiftAssignment::whereIn('worker_id', function($q) use ($agency) {
                $q->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

            $commission = DB::table('shift_payments')
                ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                ->whereIn('shift_assignments.worker_id', function($q) use ($agency) {
                    $q->select('worker_id')
                        ->from('agency_workers')
                        ->where('agency_id', $agency->id);
                })
                ->where('shift_payments.status', 'released')
                ->whereBetween('shift_payments.created_at', [$monthStart, $monthEnd])
                ->sum('shift_payments.agency_commission');

            $monthlyStats[] = [
                'month' => $month->format('M Y'),
                'shifts' => $shiftsCompleted,
                'commission' => $commission,
            ];
        }

        // Top performing workers
        $topWorkers = DB::table('shift_assignments')
            ->join('users', 'shift_assignments.worker_id', '=', 'users.id')
            ->whereIn('shift_assignments.worker_id', function($q) use ($agency) {
                $q->select('worker_id')
                    ->from('agency_workers')
                    ->where('agency_id', $agency->id);
            })
            ->where('shift_assignments.status', 'completed')
            ->select(
                'users.name',
                DB::raw('COUNT(*) as shifts_completed'),
                DB::raw('AVG(shift_assignments.rating_from_business) as avg_rating')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('shifts_completed')
            ->limit(10)
            ->get();

        return view('agency.analytics', compact('monthlyStats', 'topWorkers'));
    }
}
