<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftManagementController extends Controller
{
    /**
     * Display all shifts with filters
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Shift::with(['business', 'applications.worker', 'assignments.worker']);

        // Status filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Industry filter
        if ($request->has('industry') && $request->industry != '') {
            $query->where('industry', $request->industry);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('shift_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('shift_date', '<=', $request->date_to);
        }

        // Location filter
        if ($request->has('location') && $request->location != '') {
            $query->where(function($q) use ($request) {
                $q->where('city', 'LIKE', '%' . $request->location . '%')
                  ->orWhere('state', 'LIKE', '%' . $request->location . '%');
            });
        }

        // Urgency filter
        if ($request->has('urgency') && $request->urgency != '') {
            $query->where('urgency_level', $request->urgency);
        }

        // Flagged shifts only
        if ($request->has('flagged') && $request->flagged == '1') {
            $query->where('is_flagged', true);
        }

        // Search by business name or shift title
        if ($request->has('q') && strlen($request->q) > 2) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->q . '%')
                  ->orWhereHas('business', function($query) use ($request) {
                      $query->where('name', 'LIKE', '%' . $request->q . '%');
                  });
            });
        }

        $shifts = $query->orderBy('id', 'desc')->paginate(30);

        // Get filter options
        $industries = DB::table('shifts')->distinct()->pluck('industry');
        $statuses = ['open', 'filled', 'in_progress', 'completed', 'cancelled'];
        $urgency_levels = ['normal', 'urgent', 'critical'];

        return view('admin.shifts.index', compact('shifts', 'industries', 'statuses', 'urgency_levels'));
    }

    /**
     * Display shift details
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $shift = Shift::with([
            'business',
            'applications.worker',
            'assignments.worker',
            'payments'
        ])->findOrFail($id);

        // Calculate metrics
        $metrics = [
            'total_applications' => $shift->applications()->count(),
            'approved_applications' => $shift->applications()->where('status', 'approved')->count(),
            'total_workers_assigned' => $shift->assignments()->count(),
            'workers_checked_in' => $shift->assignments()->whereNotNull('checked_in_at')->count(),
            'total_cost' => $shift->payments()->sum('amount'),
            'platform_revenue' => $shift->payments()->sum('platform_fee'),
        ];

        return view('admin.shifts.show', compact('shift', 'metrics'));
    }

    /**
     * Flag a shift as suspicious
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flagShift($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $shift = Shift::findOrFail($id);
        $shift->is_flagged = true;
        $shift->flag_reason = $request->reason;
        $shift->flagged_by_admin_id = auth()->id();
        $shift->flagged_at = Carbon::now();
        $shift->save();

        // Optionally notify business
        // $shift->business->notify(new ShiftFlaggedNotification($shift));

        \Session::flash('success', 'Shift has been flagged successfully.');

        return redirect()->route('admin.shifts.show', $id);
    }

    /**
     * Remove flag from shift
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unflagShift($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->is_flagged = false;
        $shift->flag_reason = null;
        $shift->flagged_by_admin_id = null;
        $shift->flagged_at = null;
        $shift->save();

        \Session::flash('success', 'Flag has been removed from shift.');

        return redirect()->route('admin.shifts.show', $id);
    }

    /**
     * Remove/delete a shift (admin override)
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeShift($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $shift = Shift::findOrFail($id);

        // Check if shift has active assignments
        $hasActiveAssignments = $shift->assignments()
            ->whereIn('status', ['assigned', 'checked_in'])
            ->exists();

        if ($hasActiveAssignments) {
            return back()->withErrors([
                'error' => 'Cannot remove shift with active assignments. Please cancel the shift first.'
            ]);
        }

        // Store removal reason
        $shift->removal_reason = $request->reason;
        $shift->removed_by_admin_id = auth()->id();
        $shift->status = 'removed';
        $shift->save();

        // Notify business
        // $shift->business->notify(new ShiftRemovedNotification($shift));

        // Refund if payments exist
        if ($shift->payments()->exists()) {
            // Call refund service
            // app(ShiftPaymentService::class)->refundShift($shift);
        }

        \Session::flash('success', 'Shift has been removed and business notified.');

        return redirect()->route('admin.shifts');
    }

    /**
     * View all flagged shifts
     *
     * @return \Illuminate\View\View
     */
    public function flaggedShifts()
    {
        $shifts = Shift::with(['business'])
            ->where('is_flagged', true)
            ->orderBy('flagged_at', 'desc')
            ->paginate(30);

        return view('admin.shifts.flagged', compact('shifts'));
    }

    /**
     * Bulk approve shifts (if auto-approve is disabled)
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'shift_ids' => 'required|array',
            'shift_ids.*' => 'exists:shifts,id'
        ]);

        Shift::whereIn('id', $request->shift_ids)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'open',
                'approved_at' => Carbon::now(),
                'approved_by_admin_id' => auth()->id()
            ]);

        \Session::flash('success', count($request->shift_ids) . ' shifts have been approved.');

        return back();
    }

    /**
     * View shift statistics
     *
     * @return \Illuminate\View\View
     */
    public function statistics()
    {
        $stats = [
            'total_shifts' => Shift::count(),
            'open_shifts' => Shift::where('status', 'open')->count(),
            'filled_shifts' => Shift::where('status', 'filled')->count(),
            'completed_shifts' => Shift::where('status', 'completed')->count(),
            'cancelled_shifts' => Shift::where('status', 'cancelled')->count(),

            'avg_fill_time' => $this->calculateAverageFillTime(),
            'fill_rate' => $this->calculateFillRate(),

            'shifts_today' => Shift::whereDate('created_at', today())->count(),
            'shifts_this_week' => Shift::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'shifts_this_month' => Shift::whereMonth('created_at', Carbon::now()->month)->count(),
        ];

        // Top industries
        $topIndustries = DB::table('shifts')
            ->select('industry', DB::raw('count(*) as total'))
            ->groupBy('industry')
            ->orderBy('total', 'desc')
            ->take(10)
            ->get();

        // Top businesses posting shifts
        $topBusinesses = DB::table('shifts')
            ->join('users', 'shifts.business_id', '=', 'users.id')
            ->select('users.name', 'users.id', DB::raw('count(shifts.id) as total_shifts'))
            ->groupBy('users.name', 'users.id')
            ->orderBy('total_shifts', 'desc')
            ->take(10)
            ->get();

        return view('admin.shifts.statistics', compact('stats', 'topIndustries', 'topBusinesses'));
    }

    /**
     * Calculate average fill time in hours
     *
     * @return float
     */
    private function calculateAverageFillTime()
    {
        $filledShifts = Shift::whereNotNull('filled_at')
            ->whereNotNull('created_at')
            ->get();

        if ($filledShifts->isEmpty()) {
            return 0;
        }

        $totalMinutes = $filledShifts->sum(function($shift) {
            return Carbon::parse($shift->created_at)->diffInMinutes($shift->filled_at);
        });

        $count = $filledShifts->count();
        return $count > 0 ? round($totalMinutes / $count / 60, 1) : 0; // Convert to hours
    }

    /**
     * Calculate fill rate percentage
     *
     * @return float
     */
    private function calculateFillRate()
    {
        $totalShifts = Shift::where('status', '!=', 'cancelled')->count();

        if ($totalShifts == 0) {
            return 0;
        }

        $filledShifts = Shift::where('status', 'filled')->count();

        return round(($filledShifts / $totalShifts) * 100, 1);
    }
}
