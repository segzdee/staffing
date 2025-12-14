<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use Auth;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    /**
     * Show the business dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();

            // Get statistics
            $totalShifts = Shift::where('business_id', $user->id)->count();

        $activeShifts = Shift::where('business_id', $user->id)
            ->where('status', 'open')
            ->where('shift_date', '>=', Carbon::today())
            ->count();

        $completedShifts = Shift::where('business_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Optimized: Use join instead of whereHas
        $pendingApplications = ShiftApplication::join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $user->id)
            ->where('shift_applications.status', 'pending')
            ->count();

        $totalSpent = DB::table('shift_assignments as sa')
            ->join('shifts as s', 'sa.shift_id', '=', 's.id')
            ->where('s.business_id', $user->id)
            ->where('sa.status', 'completed')
            ->sum(DB::raw('sa.hours_worked * s.final_rate'));

        // Get upcoming shifts
        $upcomingShifts = Shift::with(['assignments.worker'])
            ->where('business_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->where('shift_date', '>=', Carbon::today())
            ->orderBy('shift_date', 'ASC')
            ->limit(5)
            ->get();

        // Optimized: Use join instead of whereHas
        $recentApplications = ShiftApplication::with(['worker', 'shift'])
            ->join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $user->id)
            ->where('shift_applications.status', 'pending')
            ->select('shift_applications.*')
            ->orderBy('shift_applications.created_at', 'DESC')
            ->limit(5)
            ->get();

        // Get shifts needing attention (open shifts with low fill rate)
        $shiftsNeedingAttention = Shift::where('business_id', $user->id)
            ->where('status', 'open')
            ->where('shift_date', '>=', Carbon::today())
            ->whereRaw('filled_workers < required_workers')
            ->orderBy('shift_date', 'ASC')
            ->limit(5)
            ->get();

        // This week statistics
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekStats = [
            'scheduled' => Shift::where('business_id', $user->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->whereBetween('shift_date', [$startOfWeek, $endOfWeek])
                ->count(),
            // Optimized: Use join instead of whereHas
            'workers' => ShiftAssignment::join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $user->id)
                ->whereBetween('shifts.shift_date', [$startOfWeek, $endOfWeek])
                ->whereIn('shift_assignments.status', ['assigned', 'in_progress'])
                ->count(),
            'spending' => DB::table('shift_assignments as sa')
                ->join('shifts as s', 'sa.shift_id', '=', 's.id')
                ->where('s.business_id', $user->id)
                ->whereIn('sa.status', ['assigned', 'in_progress'])
                ->whereBetween('s.shift_date', [$startOfWeek, $endOfWeek])
                ->sum(DB::raw('s.duration_hours * s.final_rate'))
        ];

        // Calculate average fill rate
        $allShifts = Shift::where('business_id', $user->id)
            ->where('shift_date', '>=', Carbon::now()->subDays(30))
            ->get();

        $averageFillRate = 0;
        if ($allShifts->count() > 0) {
            $totalFillRate = 0;
            foreach ($allShifts as $shift) {
                if ($shift->required_workers > 0) {
                    $totalFillRate += ($shift->filled_workers / $shift->required_workers) * 100;
                }
            }
            $averageFillRate = round($totalFillRate / $allShifts->count());
        }

        return view('business.dashboard', compact(
            'totalShifts',
            'activeShifts',
            'completedShifts',
            'pendingApplications',
            'totalSpent',
            'upcomingShifts',
            'recentApplications',
            'shiftsNeedingAttention',
            'weekStats',
            'averageFillRate'
        ));
        } catch (\Exception $e) {
            \Log::error('Business Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('business.dashboard', [
                'totalShifts' => 0,
                'activeShifts' => 0,
                'completedShifts' => 0,
                'pendingApplications' => 0,
                'totalSpent' => 0,
                'upcomingShifts' => collect(),
                'recentApplications' => collect(),
                'shiftsNeedingAttention' => collect(),
                'weekStats' => ['scheduled' => 0, 'workers' => 0, 'spending' => 0],
                'averageFillRate' => 0
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }
}
