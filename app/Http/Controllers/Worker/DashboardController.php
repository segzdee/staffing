<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftAssignment;
use App\Models\ShiftApplication;
use App\Models\Shift;
use App\Models\PenaltyAppeal;
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
        // Eager load workerProfile with skills and certifications to prevent N+1 queries
        $user->load(['workerProfile.skills', 'workerProfile.certifications']);

        $profileCompleteness = 0;
        if ($user->name) $profileCompleteness += 20;
        if ($user->email) $profileCompleteness += 20;
        if ($user->phone) $profileCompleteness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $profileCompleteness += 10;
        if ($user->bio) $profileCompleteness += 10;
        if ($user->city && $user->state) $profileCompleteness += 10;
        // Use count() on the already-loaded collection instead of querying the database
        if ($user->workerProfile && $user->workerProfile->skills->count() > 0) $profileCompleteness += 10;
        if ($user->workerProfile && $user->workerProfile->certifications->count() > 0) $profileCompleteness += 10;

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

        // Get notification and message counts for unified dashboard layout
        $unreadNotifications = 0; // Placeholder - custom notification system
        $unreadMessages = $user->unreadMessages ?? 0; // Placeholder until Messages model is ready

        // FIN-006: Get recent appeal outcomes for dashboard banner
        $recentAppealOutcome = PenaltyAppeal::where('worker_id', $user->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('reviewed_at', '>=', Carbon::now()->subDays(7))
            ->with('penalty')
            ->orderBy('reviewed_at', 'desc')
            ->first();

        // Get pending appeals count for notifications dropdown
        $pendingAppealsCount = PenaltyAppeal::where('worker_id', $user->id)
            ->whereIn('status', ['pending', 'under_review'])
            ->count();

        // Prepare metrics array for unified dashboard layout
        $metrics = [
            [
                'label' => 'This Week',
                'value' => $weekStats['scheduled'],
                'subtitle' => 'Scheduled shifts',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Upcoming',
                'value' => $upcomingShifts->count(),
                'subtitle' => 'Next 30 days',
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            ],
            [
                'label' => 'Completed',
                'value' => $shiftsCompleted,
                'subtitle' => 'Total shifts',
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Total Earned',
                'value' => '$' . number_format($totalEarnings, 2),
                'subtitle' => 'All time',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];

        return view('worker.dashboard', compact(
            'shiftsCompleted',
            'totalHours',
            'totalEarnings',
            'upcomingShifts',
            'recommendedShifts',
            'recentApplications',
            'profileCompleteness',
            'weekStats',
            'badges',
            'metrics',
            'unreadNotifications',
            'unreadMessages',
            'recentAppealOutcome',
            'pendingAppealsCount'
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
                'badges' => collect(),
                'metrics' => [
                    ['label' => 'This Week', 'value' => 0, 'subtitle' => 'Scheduled shifts', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Upcoming', 'value' => 0, 'subtitle' => 'Next 30 days', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['label' => 'Completed', 'value' => 0, 'subtitle' => 'Total shifts', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['label' => 'Total Earned', 'value' => '$0.00', 'subtitle' => 'All time', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ],
                'unreadNotifications' => 0,
                'unreadMessages' => 0,
                'recentAppealOutcome' => null,
                'pendingAppealsCount' => 0
            ])->with('error', 'Unable to load dashboard data. Please refresh the page.');
        }
    }

    /**
     * Show the worker profile page
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        $user->load('workerProfile');

        // Calculate profile completeness
        $profileCompleteness = $this->calculateProfileCompleteness($user);

        // Get profile data
        $profile = $user->workerProfile;

        return view('worker.profile', compact('user', 'profile', 'profileCompleteness'));
    }

    /**
     * Update the worker profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'industries' => 'nullable|array',
            'experience_level' => 'nullable|in:entry,intermediate,experienced',
            'skills' => 'nullable|string|max:500',
            'max_distance' => 'nullable|integer|min:1|max:100',
            'min_hourly_rate' => 'nullable|numeric|min:0|max:500',
            'shift_times' => 'nullable|array',
            'has_transportation' => 'nullable|boolean',
        ]);

        try {
            // Update user bio
            if ($request->has('bio')) {
                $user->bio = $request->bio;
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $filename = time() . '_' . $user->id . '.' . $avatar->getClientOriginalExtension();
                $path = $avatar->storeAs('avatars', $filename, 'public');
                $user->avatar = 'storage/' . $path;
            }

            $user->save();

            // Update worker profile
            $profile = $user->workerProfile;
            if (!$profile) {
                $profile = new \App\Models\WorkerProfile(['user_id' => $user->id]);
            }

            $profile->fill([
                'preferred_industries' => $request->industries ? json_encode($request->industries) : null,
                'experience_level' => $request->experience_level,
                'skills' => $request->skills,
                'max_commute_distance' => $request->max_distance,
                'min_hourly_rate' => $request->min_hourly_rate,
                'preferred_shift_times' => $request->shift_times ? json_encode($request->shift_times) : null,
                'has_transportation' => $request->has_transportation,
            ]);
            $profile->save();

            return redirect()->route('worker.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Worker Profile Update Error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Show the worker badges page
     *
     * @return \Illuminate\View\View
     */
    public function badges()
    {
        $user = Auth::user();

        // Get all badges for the worker
        $badges = $user->badges()->orderBy('earned_at', 'desc')->get();

        // Get available badges that can be earned
        $availableBadges = \App\Models\WorkerBadge::getAvailableBadgeTypes();

        return view('worker.profile.badges', compact('user', 'badges', 'availableBadges'));
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

        if ($user->name) $completeness += 20;
        if ($user->email) $completeness += 20;
        if ($user->phone) $completeness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $completeness += 10;
        if ($user->bio) $completeness += 10;
        if ($user->city && $user->state) $completeness += 10;

        if ($user->workerProfile) {
            if ($user->workerProfile->skills && count(explode(',', $user->workerProfile->skills)) > 0) {
                $completeness += 10;
            }
            if ($user->workerProfile->experience_level) {
                $completeness += 10;
            }
        }

        return min($completeness, 100);
    }
}
