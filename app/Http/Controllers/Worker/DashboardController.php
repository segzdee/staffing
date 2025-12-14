<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftAssignment;
use App\Models\ShiftApplication;
use App\Models\Shift;
use Auth;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
{
    /**
     * Show the worker dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            $user = Auth::user();

            // Get statistics
            $shiftsCompleted = ShiftAssignment::where('worker_id', $user->id)
                ->where('shift_assignments.status', 'completed')
                ->count();

        $totalHours = ShiftAssignment::where('worker_id', $user->id)
            ->where('shift_assignments.status', 'completed')
            ->sum('hours_worked');

        $totalEarnings = DB::table('shift_assignments as sa')
            ->join('shifts as s', 'sa.shift_id', '=', 's.id')
            ->where('sa.worker_id', $user->id)
            ->where('sa.status', 'completed')
            ->sum(DB::raw('sa.hours_worked * s.final_rate'));

        // Get upcoming shifts (assigned, not started yet)
        $upcomingShifts = ShiftAssignment::with('shift.business')
            ->where('worker_id', $user->id)
            ->where('shift_assignments.status', 'assigned')
            ->whereHas('shift', function($query) {
                $query->where('shift_date', '>=', Carbon::today());
            })
            ->orderBy('shift_assignments.created_at', 'DESC')
            ->limit(5)
            ->get();

        // Get recommended shifts based on worker's skills, location, and experience
        $recommendedShifts = Shift::with('business')
            ->where('status', 'open')
            ->where('shift_date', '>=', Carbon::today())
            ->whereRaw('filled_workers < required_workers')
            ->whereDoesntHave('applications', function($query) use ($user) {
                $query->where('worker_id', $user->id);
            })
            ->whereDoesntHave('assignments', function($query) use ($user) {
                $query->where('worker_id', $user->id);
            })
            ->orderBy('shift_date', 'ASC')
            ->limit(5)
            ->get();

        // Get recent applications
        $recentApplications = ShiftApplication::with('shift')
            ->where('worker_id', $user->id)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        // Calculate profile completeness
        $profileCompleteness = 0;
        if ($user->name) $profileCompleteness += 20;
        if ($user->email) $profileCompleteness += 20;
        if ($user->phone) $profileCompleteness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $profileCompleteness += 10;
        if ($user->bio) $profileCompleteness += 10;
        if ($user->city && $user->state) $profileCompleteness += 10;
        if ($user->workerProfile && $user->workerProfile->skills()->count() > 0) $profileCompleteness += 10;
        if ($user->workerProfile && $user->workerProfile->certifications()->count() > 0) $profileCompleteness += 10;

        // This week statistics
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekStats = [
            'scheduled' => ShiftAssignment::where('worker_id', $user->id)
                ->whereIn('shift_assignments.status', ['assigned', 'in_progress'])
                ->whereHas('shift', function($query) use ($startOfWeek, $endOfWeek) {
                    $query->whereBetween('shift_date', [$startOfWeek, $endOfWeek]);
                })
                ->count(),
            'hours' => ShiftAssignment::where('shift_assignments.worker_id', $user->id)
                ->whereIn('shift_assignments.status', ['assigned', 'in_progress'])
                ->whereHas('shift', function($query) use ($startOfWeek, $endOfWeek) {
                    $query->whereBetween('shift_date', [$startOfWeek, $endOfWeek]);
                })
                ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->sum('shifts.duration_hours'),
            'earnings' => DB::table('shift_assignments as sa')
                ->join('shifts as s', 'sa.shift_id', '=', 's.id')
                ->where('sa.worker_id', $user->id)
                ->whereIn('sa.status', ['assigned', 'in_progress'])
                ->whereBetween('s.shift_date', [$startOfWeek, $endOfWeek])
                ->sum(DB::raw('s.duration_hours * s.final_rate'))
        ];

        // Get badges/achievements
        $badges = $user->badges()->active()->latest('earned_at')->limit(3)->get();

        return view('worker.dashboard', compact(
            'shiftsCompleted',
            'totalHours',
            'totalEarnings',
            'upcomingShifts',
            'recommendedShifts',
            'recentApplications',
            'profileCompleteness',
            'weekStats',
            'badges'
        ));
        } catch (\Exception $e) {
            \Log::error('Worker Dashboard Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('worker.dashboard', [
                'shiftsCompleted' => 0,
                'totalHours' => 0,
                'totalEarnings' => 0,
                'upcomingShifts' => collect(),
                'recommendedShifts' => collect(),
                'recentApplications' => collect(),
                'profileCompleteness' => 0,
                'weekStats' => ['scheduled' => 0, 'hours' => 0, 'earnings' => 0],
                'badges' => collect()
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }
}
