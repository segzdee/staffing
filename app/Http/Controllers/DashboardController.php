<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Main dashboard - routes to appropriate dashboard based on user type
     */
    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->isWorker()) {
                return $this->workerDashboard();
            } elseif ($user->isBusiness()) {
                return $this->businessDashboard();
            } elseif ($user->isAgency()) {
                return $this->agencyDashboard();
            } elseif ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // Default fallback
            return view('dashboard.welcome');
        } catch (\Exception $e) {
            \Log::error('Dashboard Router Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('dashboard.welcome')->with('error', 'Unable to load dashboard. Please try again.');
        }
    }

    /**
     * Worker Dashboard
     */
    protected function workerDashboard()
    {
        try {
            $worker = Auth::user();

        // Today's shifts
        $todayShifts = ShiftAssignment::where('worker_id', $worker->id)
            ->whereHas('shift', function($q) {
                $q->whereDate('shift_date', Carbon::today());
            })
            ->with('shift.business')
            ->get();

        // Upcoming shifts (next 7 days)
        $upcomingShifts = ShiftAssignment::where('shift_assignments.worker_id', $worker->id)
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->whereBetween('shifts.shift_date', [Carbon::tomorrow(), Carbon::today()->addDays(7)])
            ->whereIn('shifts.status', ['assigned', 'in_progress'])
            ->with('shift.business')
            ->select('shift_assignments.*')
            ->orderBy('shifts.shift_date')
            ->get();

        // Pending applications
        $pendingApplications = ShiftApplication::where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->with('shift.business')
            ->latest()
            ->limit(5)
            ->get();

        // Recommended shifts
        $recommendedShifts = app(\App\Services\ShiftMatchingService::class)
            ->matchShiftsForWorker($worker)
            ->take(5);

        // Stats
        $stats = [
            'shifts_today' => $todayShifts->count(),
            'shifts_this_week' => ShiftAssignment::where('worker_id', $worker->id)
                ->whereHas('shift', function($q) {
                    $q->whereBetween('shift_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                })
                ->count(),
            'pending_applications' => $pendingApplications->count(),
            'earnings_this_week' => ShiftPayment::where('worker_id', $worker->id)
                ->where('status', 'paid_out')
                ->whereBetween('payout_completed_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount_net'),
            'earnings_this_month' => ShiftPayment::where('worker_id', $worker->id)
                ->where('status', 'paid_out')
                ->whereMonth('payout_completed_at', Carbon::now()->month)
                ->sum('amount_net'),
            'total_completed' => ShiftAssignment::where('worker_id', $worker->id)
                ->where('status', 'completed')
                ->count(),
            'rating' => $worker->rating_as_worker ?? 0,
            'reliability_score' => $worker->workerProfile->reliability_score ?? 1.0,
        ];

        // Recent badges
        $recentBadges = $worker->badges()
            ->active()
            ->latest('earned_at')
            ->limit(3)
            ->get();

        // Next shift
        $nextShift = ShiftAssignment::where('shift_assignments.worker_id', $worker->id)
            ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
            ->where('shifts.shift_date', '>=', Carbon::today())
            ->whereIn('shifts.status', ['assigned', 'in_progress'])
            ->with('shift.business')
            ->orderBy('shifts.shift_date')
            ->orderBy('shifts.start_time')
            ->select('shift_assignments.*')
            ->first();

        return view('worker.dashboard', compact(
            'todayShifts',
            'upcomingShifts',
            'pendingApplications',
            'recommendedShifts',
            'stats',
            'recentBadges',
            'nextShift'
        ));
        } catch (\Exception $e) {
            \Log::error('Worker Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('worker.dashboard', [
                'todayShifts' => collect(),
                'upcomingShifts' => collect(),
                'pendingApplications' => collect(),
                'recommendedShifts' => collect(),
                'stats' => ['shifts_today' => 0, 'shifts_this_week' => 0, 'pending_applications' => 0, 'earnings_this_week' => 0, 'earnings_this_month' => 0, 'total_completed' => 0, 'rating' => 0, 'reliability_score' => 0],
                'recentBadges' => collect(),
                'nextShift' => null
            ])->with('error', 'Unable to load dashboard data.');
        }
    }

    /**
     * Business Dashboard
     */
    protected function businessDashboard()
    {
        try {
            $business = Auth::user();

        // Active shifts
        $activeShifts = Shift::where('business_id', $business->id)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->with(['applications' => function($q) {
                $q->where('status', 'pending');
            }])
            ->orderBy('shift_date')
            ->get();

        // Today's shifts
        $todayShifts = Shift::where('business_id', $business->id)
            ->whereDate('shift_date', Carbon::today())
            ->with('assignments.worker')
            ->get();

        // Pending applications
        // Eager load worker with completed_shifts_count to prevent N+1 queries in the view
        $pendingApplications = ShiftApplication::whereHas('shift', function($q) use ($business) {
            $q->where('business_id', $business->id);
        })
        ->where('status', 'pending')
        ->with(['worker' => function($q) {
            // Use withCount to eager load the completed shifts count instead of calling count() in the view
            $q->withCount(['shiftAssignments as completed_shifts_count' => function($query) {
                $query->where('status', 'completed');
            }]);
        }, 'shift'])
        ->latest()
        ->limit(10)
        ->get();

        // Urgent unfilled shifts
        $urgentShifts = Shift::where('business_id', $business->id)
            ->where('status', 'open')
            ->whereIn('urgency_level', ['urgent', 'critical'])
            ->whereRaw('filled_workers < required_workers')
            ->where('shift_date', '>=', Carbon::today())
            ->orderBy('shift_date')
            ->limit(5)
            ->get();

        // Stats
        $stats = [
            'active_shifts' => $activeShifts->count(),
            'pending_applications' => $pendingApplications->count(),
            'workers_today' => ShiftAssignment::whereHas('shift', function($q) use ($business) {
                $q->where('business_id', $business->id)
                  ->whereDate('shift_date', Carbon::today());
            })->count(),
            'urgent_unfilled' => $urgentShifts->count(),
            'cost_this_week' => ShiftPayment::where('business_id', $business->id)
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->sum('amount_gross'),
            'cost_this_month' => ShiftPayment::where('business_id', $business->id)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount_gross'),
            'avg_fill_rate' => $this->calculateFillRate($business->id),
            'total_shifts_posted' => Shift::where('business_id', $business->id)->count(),
        ];

        // Recent activity
        $recentActivity = $this->getRecentBusinessActivity($business->id);

        return view('business.dashboard', compact(
            'activeShifts',
            'todayShifts',
            'pendingApplications',
            'urgentShifts',
            'stats',
            'recentActivity'
        ));
        } catch (\Exception $e) {
            \Log::error('Business Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('business.dashboard', [
                'activeShifts' => collect(),
                'todayShifts' => collect(),
                'pendingApplications' => collect(),
                'urgentShifts' => collect(),
                'stats' => ['active_shifts' => 0, 'pending_applications' => 0, 'workers_today' => 0, 'urgent_unfilled' => 0, 'cost_this_week' => 0, 'cost_this_month' => 0, 'avg_fill_rate' => 0, 'total_shifts_posted' => 0],
                'recentActivity' => []
            ])->with('error', 'Unable to load dashboard data.');
        }
    }

    /**
     * Agency Dashboard
     */
    protected function agencyDashboard()
    {
        try {
            $agency = Auth::user();

        // Agency workers (using relationship through agency_workers pivot table)
        // Eager load assignedShifts and use withCount to get completed shifts count to prevent N+1 queries
        $agencyWorkers = $agency->agencyWorkers()
            ->with(['assignedShifts' => function($q) {
                $q->whereHas('shift', function($query) {
                    $query->where('shift_date', '>=', Carbon::today());
                });
            }])
            ->withCount(['shiftAssignments as completed_shifts_count' => function($query) {
                $query->where('status', 'completed');
            }])
            ->get();

        // Active placements (workers on shifts)
        $activePlacements = ShiftAssignment::whereIn('worker_id', $agencyWorkers->pluck('id'))
            ->whereHas('shift', function($q) {
                $q->whereIn('status', ['assigned', 'in_progress']);
            })
            ->with('worker', 'shift.business')
            ->get();

        // Available shifts (for agency to assign workers)
        $availableShifts = Shift::where('status', 'open')
            ->whereRaw('filled_workers < required_workers')
            ->where('shift_date', '>=', Carbon::today())
            ->where('allow_agencies', true)
            ->orderBy('shift_date')
            ->limit(20)
            ->get();

        // Stats
        $stats = [
            'total_workers' => $agencyWorkers->count(),
            'active_placements' => $activePlacements->count(),
            'available_workers' => $agencyWorkers->filter(function($worker) {
                return $worker->assignedShifts->count() == 0;
            })->count(),
            'revenue_this_month' => DB::table('shift_payments')
                ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
                ->join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->whereMonth('shift_payments.payout_completed_at', Carbon::now()->month)
                ->where('shift_payments.status', 'released')
                ->sum('shift_payments.agency_commission'),
            'total_placements_month' => ShiftAssignment::join('agency_workers', 'shift_assignments.worker_id', '=', 'agency_workers.worker_id')
                ->where('agency_workers.agency_id', $agency->id)
                ->whereMonth('shift_assignments.created_at', Carbon::now()->month)
                ->count(),
            'avg_worker_rating' => $agencyWorkers->avg('rating_as_worker') ?? 0,
        ];

        // Top performing workers
        $topWorkers = $agencyWorkers->sortByDesc('rating_as_worker')->take(5);

        // Client businesses
        $clientBusinesses = User::whereIn('id', function($query) use ($agencyWorkers) {
            $query->select('business_id')
                ->from('shifts')
                ->whereIn('id', function($subQuery) use ($agencyWorkers) {
                    $subQuery->select('shift_id')
                        ->from('shift_assignments')
                        ->whereIn('worker_id', $agencyWorkers->pluck('id'));
                });
        })->get();

        return view('agency.dashboard', compact(
            'agencyWorkers',
            'activePlacements',
            'availableShifts',
            'stats',
            'topWorkers',
            'clientBusinesses'
        ));
        } catch (\Exception $e) {
            \Log::error('Agency Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('agency.dashboard', [
                'agencyWorkers' => collect(),
                'activePlacements' => collect(),
                'availableShifts' => collect(),
                'stats' => ['total_workers' => 0, 'active_placements' => 0, 'available_workers' => 0, 'revenue_this_month' => 0, 'total_placements_month' => 0, 'avg_worker_rating' => 0],
                'topWorkers' => collect(),
                'clientBusinesses' => collect()
            ])->with('error', 'Unable to load dashboard data.');
        }
    }

    /**
     * Calculate fill rate for business
     */
    private function calculateFillRate($businessId)
    {
        $shifts = Shift::where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($shifts->count() == 0) {
            return 0;
        }

        $totalPositions = $shifts->sum('required_workers');
        $filledPositions = $shifts->sum('filled_workers');

        return $totalPositions > 0 ? round(($filledPositions / $totalPositions) * 100, 1) : 0;
    }

    /**
     * Get recent activity for business
     */
    private function getRecentBusinessActivity($businessId)
    {
        $activities = [];

        // Recent applications
        $applications = ShiftApplication::whereHas('shift', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
        ->latest()
        ->limit(10)
        ->get();

        foreach ($applications as $app) {
            $activities[] = [
                'type' => 'application',
                'time' => $app->created_at,
                'description' => "{$app->worker->name} applied to {$app->shift->title}",
                'icon' => 'fa-user-plus',
                'color' => 'primary'
            ];
        }

        // Recent assignments
        $assignments = ShiftAssignment::whereHas('shift', function($q) use ($businessId) {
            $q->where('business_id', $businessId);
        })
        ->latest()
        ->limit(10)
        ->get();

        foreach ($assignments as $assignment) {
            $activities[] = [
                'type' => 'assignment',
                'time' => $assignment->created_at,
                'description' => "{$assignment->worker->name} was assigned to {$assignment->shift->title}",
                'icon' => 'fa-check-circle',
                'color' => 'success'
            ];
        }

        // Sort by time
        usort($activities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });

        return array_slice($activities, 0, 10);
    }
}
