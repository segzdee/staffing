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

        // Calculate profile completeness for onboarding progress
        $onboardingProgress = $this->calculateProfileCompleteness($user);

        // Get notification and message counts for unified dashboard layout
        $unreadNotifications = 0; // Placeholder - custom notification system
        $unreadMessages = $user->unreadMessages ?? 0; // Placeholder until Messages model is ready

        // Prepare metrics array for unified dashboard layout
        $metrics = [
            [
                'label' => 'Active Shifts',
                'value' => $activeShifts,
                'subtitle' => 'Currently open',
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
            [
                'label' => 'Pending',
                'value' => $pendingApplications,
                'subtitle' => 'Applications',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Completed',
                'value' => $completedShifts,
                'subtitle' => 'Total shifts',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Total Spent',
                'value' => '$' . number_format($totalSpent, 2),
                'subtitle' => 'All time',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];

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
            'averageFillRate',
            'metrics',
            'onboardingProgress',
            'unreadNotifications',
            'unreadMessages'
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
                'averageFillRate' => 0,
                'metrics' => [
                    ['label' => 'Active Shifts', 'value' => 0, 'subtitle' => 'Currently open', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['label' => 'Pending', 'value' => 0, 'subtitle' => 'Applications', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Completed', 'value' => 0, 'subtitle' => 'Total shifts', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Total Spent', 'value' => '$0.00', 'subtitle' => 'All time', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ],
                'onboardingProgress' => 0,
                'unreadNotifications' => 0,
                'unreadMessages' => 0
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }

    /**
     * Show the business profile page
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        $user->load('businessProfile');

        // Get profile data
        $profile = $user->businessProfile;

        // Calculate profile completeness
        $profileCompleteness = $this->calculateProfileCompleteness($user);

        return view('business.profile', compact('user', 'profile', 'profileCompleteness'));
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
        if ($user->name) $completeness += 15;
        if ($user->email) $completeness += 15;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $completeness += 10;

        // Business profile fields
        if ($user->businessProfile) {
            $profile = $user->businessProfile;
            if ($profile->company_name) $completeness += 15;
            if ($profile->business_type) $completeness += 10;
            if ($profile->address) $completeness += 10;
            if ($profile->city && $profile->state) $completeness += 10;
            if ($profile->phone) $completeness += 10;
            if ($profile->description) $completeness += 5;
        }

        return min($completeness, 100);
    }
}
