<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\PenaltyAppeal;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

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
                ->whereHas('shift', function ($query) {
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
                ->whereDoesntHave('applications', function ($query) use ($user) {
                    $query->where('worker_id', $user->id);
                })
                ->whereDoesntHave('assignments', function ($query) use ($user) {
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
            if ($user->name) {
                $profileCompleteness += 20;
            }
            if ($user->email) {
                $profileCompleteness += 20;
            }
            if ($user->phone) {
                $profileCompleteness += 10;
            }
            if ($user->avatar && $user->avatar != 'avatar.jpg') {
                $profileCompleteness += 10;
            }
            if ($user->bio) {
                $profileCompleteness += 10;
            }
            if ($user->city && $user->state) {
                $profileCompleteness += 10;
            }
            // Use count() on the already-loaded collection instead of querying the database
            if ($user->workerProfile && $user->workerProfile->skills->count() > 0) {
                $profileCompleteness += 10;
            }
            if ($user->workerProfile && $user->workerProfile->certifications->count() > 0) {
                $profileCompleteness += 10;
            }

            // This week statistics
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            // Optimized: Single query for all week statistics
            $weekStatsRaw = DB::table('shift_assignments as sa')
                ->join('shifts as s', 'sa.shift_id', '=', 's.id')
                ->where('sa.worker_id', $user->id)
                ->whereIn('sa.status', ['assigned', 'in_progress'])
                ->whereBetween('s.shift_date', [$startOfWeek, $endOfWeek])
                ->selectRaw('
                    COUNT(*) as scheduled,
                    COALESCE(SUM(s.duration_hours), 0) as hours,
                    COALESCE(SUM(s.duration_hours * s.final_rate), 0) as earnings
                ')
                ->first();

            $weekStats = [
                'scheduled' => (int) ($weekStatsRaw->scheduled ?? 0),
                'hours' => (float) ($weekStatsRaw->hours ?? 0),
                'earnings' => (int) ($weekStatsRaw->earnings ?? 0), // Still in cents
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
                    'value' => '$'.number_format($totalEarnings, 2),
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
            \Log::error('Worker Dashboard Error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
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
                'pendingAppealsCount' => 0,
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
                $filename = time().'_'.$user->id.'.'.$avatar->getClientOriginalExtension();
                $path = $avatar->storeAs('avatars', $filename, 'public');
                $user->avatar = 'storage/'.$path;
            }

            $user->save();

            // Update worker profile
            $profile = $user->workerProfile;
            if (! $profile) {
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
            \Log::error('Worker Profile Update Error: '.$e->getMessage());

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
     */
    private function calculateProfileCompleteness($user): int
    {
        $completeness = 0;

        if ($user->name) {
            $completeness += 20;
        }
        if ($user->email) {
            $completeness += 20;
        }
        if ($user->phone) {
            $completeness += 10;
        }
        if ($user->avatar && $user->avatar != 'avatar.jpg') {
            $completeness += 10;
        }
        if ($user->bio) {
            $completeness += 10;
        }
        if ($user->city && $user->state) {
            $completeness += 10;
        }

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

    /**
     * Show the worker calendar view
     */
    public function calendar(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get month/year from request, default to current month
        $month = request('month', Carbon::now()->month);
        $year = request('year', Carbon::now()->year);
        $currentDate = Carbon::createFromDate($year, $month, 1);

        // Get all assigned shifts for the month (plus buffer for display)
        $startDate = $currentDate->copy()->startOfMonth()->subDays(7);
        $endDate = $currentDate->copy()->endOfMonth()->addDays(7);

        // Get shift assignments for calendar display
        $assignments = ShiftAssignment::with(['shift.business', 'shift.venue'])
            ->where('worker_id', $user->id)
            ->whereHas('shift', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('shift_date', [$startDate, $endDate]);
            })
            ->get();

        // Format events for calendar display
        $events = $assignments->map(function ($assignment) {
            $shift = $assignment->shift;
            $statusColors = [
                'assigned' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-800 dark:text-blue-300', 'border' => 'border-blue-300'],
                'in_progress' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'border' => 'border-yellow-300'],
                'completed' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-800 dark:text-green-300', 'border' => 'border-green-300'],
                'cancelled' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300', 'border' => 'border-red-300'],
                'no_show' => ['bg' => 'bg-gray-100 dark:bg-gray-900/30', 'text' => 'text-gray-800 dark:text-gray-300', 'border' => 'border-gray-300'],
            ];
            $colors = $statusColors[$assignment->status] ?? $statusColors['assigned'];

            return [
                'id' => $assignment->id,
                'shift_id' => $shift->id,
                'title' => $shift->title ?? 'Shift',
                'date' => Carbon::parse($shift->shift_date)->format('Y-m-d'),
                'start_time' => Carbon::parse($shift->start_time)->format('g:i A'),
                'end_time' => Carbon::parse($shift->end_time)->format('g:i A'),
                'business' => $shift->business->name ?? 'Unknown',
                'venue' => $shift->venue->name ?? $shift->location ?? 'N/A',
                'status' => $assignment->status,
                'hourly_rate' => $shift->final_rate ?? $shift->hourly_rate,
                'duration' => $shift->duration_hours,
                'colors' => $colors,
            ];
        })->groupBy('date');

        // Get pending applications for display
        $pendingApplications = ShiftApplication::with(['shift.business'])
            ->where('worker_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('shift', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('shift_date', [$startDate, $endDate]);
            })
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'shift_id' => $application->shift->id,
                    'title' => $application->shift->title ?? 'Shift',
                    'date' => Carbon::parse($application->shift->shift_date)->format('Y-m-d'),
                    'start_time' => Carbon::parse($application->shift->start_time)->format('g:i A'),
                    'business' => $application->shift->business->name ?? 'Unknown',
                    'status' => 'pending_application',
                ];
            })->groupBy('date');

        // Calendar navigation
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        // Stats for the month
        $monthStats = [
            'total_shifts' => $assignments->count(),
            'completed' => $assignments->where('status', 'completed')->count(),
            'upcoming' => $assignments->where('status', 'assigned')->count(),
            'hours_scheduled' => $assignments->sum(fn ($a) => $a->shift->duration_hours ?? 0),
            'expected_earnings' => $assignments->where('status', 'assigned')->sum(fn ($a) => ($a->shift->duration_hours ?? 0) * ($a->shift->final_rate ?? 0)),
        ];

        return view('worker.calendar', compact(
            'events',
            'pendingApplications',
            'currentDate',
            'prevMonth',
            'nextMonth',
            'monthStats'
        ));
    }

    /**
     * Show the worker documents page
     */
    public function documents(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get worker certifications
        $certifications = \App\Models\WorkerCertification::where('worker_id', $user->id)
            ->with('certification', 'certificationType')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get verification documents
        $verificationDocuments = \App\Models\VerificationDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get identity verification status
        $identityVerification = \App\Models\IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        // Group documents by type for display
        $documentGroups = [
            'identity' => [
                'title' => 'Identity Documents',
                'description' => 'Government-issued ID and verification documents',
                'documents' => $verificationDocuments->filter(fn ($d) => in_array($d->document_type, ['passport', 'drivers_license', 'national_id', 'id_front', 'id_back'])),
                'status' => $identityVerification?->status ?? 'not_started',
            ],
            'certifications' => [
                'title' => 'Professional Certifications',
                'description' => 'Industry certifications and qualifications',
                'documents' => $certifications,
                'count' => $certifications->count(),
                'verified_count' => $certifications->where('verified', true)->count(),
            ],
            'rtw' => [
                'title' => 'Right to Work',
                'description' => 'Documents proving your eligibility to work',
                'documents' => $verificationDocuments->filter(fn ($d) => in_array($d->document_type, ['work_permit', 'visa', 'rtw'])),
            ],
        ];

        // Calculate document completion status
        $documentStatus = [
            'identity_verified' => $identityVerification?->status === 'verified',
            'has_certifications' => $certifications->count() > 0,
            'certifications_verified' => $certifications->where('verified', true)->count(),
            'pending_verification' => $verificationDocuments->where('status', 'pending')->count() + $certifications->where('verification_status', 'pending')->count(),
        ];

        return view('worker.documents', compact('documentGroups', 'documentStatus', 'identityVerification'));
    }

    /**
     * Show the worker earnings overview
     */
    public function earnings(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get time period from request, default to 'this_month'
        $period = request('period', 'this_month');
        $startDate = match ($period) {
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'this_year' => Carbon::now()->startOfYear(),
            'all_time' => Carbon::createFromTimestamp(0),
            default => Carbon::now()->startOfMonth(), // this_month
        };
        $endDate = match ($period) {
            'last_month' => Carbon::now()->subMonth()->endOfMonth(),
            default => Carbon::now(),
        };

        // Get earnings data for the period
        $earningsQuery = DB::table('shift_payments as sp')
            ->join('shift_assignments as sa', 'sp.shift_assignment_id', '=', 'sa.id')
            ->join('shifts as s', 'sa.shift_id', '=', 's.id')
            ->join('users as b', 's.business_id', '=', 'b.id')
            ->where('sp.worker_id', $user->id)
            ->whereBetween('sp.created_at', [$startDate, $endDate]);

        // Calculate totals for the period
        $periodEarnings = (clone $earningsQuery)->sum('sp.amount_net') / 100; // Convert cents to dollars
        $pendingPayment = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->whereIn('status', ['in_escrow', 'released'])
            ->sum('amount_net') / 100;

        $hoursWorked = ShiftAssignment::where('worker_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('hours_worked');

        $shiftsCompleted = ShiftAssignment::where('worker_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Calculate growth compared to previous period
        $previousStart = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
        $previousEnd = $startDate->copy()->subDay();
        $previousEarnings = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount_net') / 100;
        $earningsGrowth = $previousEarnings > 0
            ? round((($periodEarnings - $previousEarnings) / $previousEarnings) * 100, 1)
            : 0;

        // All-time total earnings
        $totalEarnings = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->where('status', 'paid_out')
            ->sum('amount_net') / 100;

        // Get recent payments with shift and business info
        $payments = DB::table('shift_payments as sp')
            ->join('shift_assignments as sa', 'sp.shift_assignment_id', '=', 'sa.id')
            ->join('shifts as s', 'sa.shift_id', '=', 's.id')
            ->join('users as b', 's.business_id', '=', 'b.id')
            ->where('sp.worker_id', $user->id)
            ->select([
                'sp.*',
                's.title as shift_title',
                'b.name as business_name',
                'sa.hours_worked',
            ])
            ->orderBy('sp.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                $payment->amount = $payment->amount_net / 100;
                $payment->hours = $payment->hours_worked;
                $payment->shift = (object) ['title' => $payment->shift_title, 'business' => (object) ['name' => $payment->business_name]];

                return $payment;
            });

        // Monthly earnings for chart (last 6 months)
        $monthlyEarnings = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $amount = DB::table('shift_payments')
                ->where('worker_id', $user->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('amount_net') / 100;
            $monthlyEarnings->push([
                'month' => $monthStart->format('M'),
                'amount' => $amount,
            ]);
        }

        return view('worker.earnings.index', compact(
            'totalEarnings',
            'periodEarnings',
            'pendingPayment',
            'hoursWorked',
            'shiftsCompleted',
            'earningsGrowth',
            'payments',
            'monthlyEarnings',
            'period'
        ));
    }

    /**
     * Show the worker earnings history
     */
    public function earningsHistory(): \Illuminate\View\View
    {
        return view('worker.earnings.history');
    }

    /**
     * Show the worker pending earnings
     */
    public function earningsPending(): \Illuminate\View\View
    {
        return view('worker.earnings.pending');
    }

    /**
     * Show the worker preferences page
     */
    public function preferences(): \Illuminate\View\View
    {
        $user = Auth::user();
        $user->load('workerProfile');

        $profile = $user->workerProfile;

        // Get current preferences (handle both JSON strings and already-decoded arrays)
        $decodeIfNeeded = fn ($value, $default = []) => is_string($value) ? json_decode($value, true) : ($value ?? $default);

        $preferences = [
            'work' => [
                'preferred_industries' => $decodeIfNeeded($profile?->preferred_industries),
                'preferred_shift_times' => $decodeIfNeeded($profile?->preferred_shift_times),
                'min_hourly_rate' => $profile?->min_hourly_rate ?? 15,
                'max_commute_distance' => $profile?->max_commute_distance ?? 25,
                'has_transportation' => $profile?->has_transportation ?? false,
                'available_weekdays' => $decodeIfNeeded($profile?->available_weekdays, ['mon', 'tue', 'wed', 'thu', 'fri']),
                'available_weekends' => $profile?->available_weekends ?? true,
            ],
            'notifications' => [
                'email_new_shifts' => $user->notify_new_shifts ?? true,
                'email_application_updates' => $user->notify_application_updates ?? true,
                'email_payment_received' => $user->notify_payments ?? true,
                'email_shift_reminders' => $user->notify_reminders ?? true,
                'sms_shift_reminders' => $user->sms_reminders ?? false,
                'push_enabled' => $user->push_notifications ?? true,
            ],
            'privacy' => [
                'profile_visible' => $profile?->is_public ?? true,
                'show_earnings' => $profile?->show_earnings ?? false,
                'allow_business_contact' => $profile?->allow_direct_contact ?? true,
            ],
        ];

        // Available options for dropdowns
        $options = [
            'industries' => [
                'hospitality' => 'Hospitality & Events',
                'retail' => 'Retail',
                'warehouse' => 'Warehouse & Logistics',
                'healthcare' => 'Healthcare Support',
                'food_service' => 'Food Service',
                'security' => 'Security',
                'cleaning' => 'Cleaning & Maintenance',
                'admin' => 'Administrative',
            ],
            'shift_times' => [
                'morning' => 'Morning (6 AM - 12 PM)',
                'afternoon' => 'Afternoon (12 PM - 6 PM)',
                'evening' => 'Evening (6 PM - 12 AM)',
                'overnight' => 'Overnight (12 AM - 6 AM)',
            ],
            'weekdays' => [
                'mon' => 'Monday',
                'tue' => 'Tuesday',
                'wed' => 'Wednesday',
                'thu' => 'Thursday',
                'fri' => 'Friday',
                'sat' => 'Saturday',
                'sun' => 'Sunday',
            ],
        ];

        return view('worker.preferences', compact('user', 'profile', 'preferences', 'options'));
    }

    /**
     * Update the worker preferences
     */
    public function updatePreferences(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'preferred_industries' => 'nullable|array',
            'preferred_shift_times' => 'nullable|array',
            'min_hourly_rate' => 'nullable|numeric|min:0|max:500',
            'max_commute_distance' => 'nullable|integer|min:1|max:200',
            'has_transportation' => 'nullable|boolean',
            'available_weekdays' => 'nullable|array',
            'available_weekends' => 'nullable|boolean',
            'email_new_shifts' => 'nullable|boolean',
            'email_application_updates' => 'nullable|boolean',
            'email_payment_received' => 'nullable|boolean',
            'email_shift_reminders' => 'nullable|boolean',
            'sms_shift_reminders' => 'nullable|boolean',
            'push_enabled' => 'nullable|boolean',
            'profile_visible' => 'nullable|boolean',
            'show_earnings' => 'nullable|boolean',
            'allow_business_contact' => 'nullable|boolean',
        ]);

        try {
            // Update user notification preferences
            $user->update([
                'notify_new_shifts' => $request->boolean('email_new_shifts', true),
                'notify_application_updates' => $request->boolean('email_application_updates', true),
                'notify_payments' => $request->boolean('email_payment_received', true),
                'notify_reminders' => $request->boolean('email_shift_reminders', true),
                'sms_reminders' => $request->boolean('sms_shift_reminders', false),
                'push_notifications' => $request->boolean('push_enabled', true),
            ]);

            // Update worker profile preferences
            $profile = $user->workerProfile;
            if (! $profile) {
                $profile = new \App\Models\WorkerProfile(['user_id' => $user->id]);
            }

            $profile->fill([
                'preferred_industries' => $request->preferred_industries ? json_encode($request->preferred_industries) : null,
                'preferred_shift_times' => $request->preferred_shift_times ? json_encode($request->preferred_shift_times) : null,
                'min_hourly_rate' => $request->min_hourly_rate,
                'max_commute_distance' => $request->max_commute_distance,
                'has_transportation' => $request->boolean('has_transportation'),
                'available_weekdays' => $request->available_weekdays ? json_encode($request->available_weekdays) : null,
                'available_weekends' => $request->boolean('available_weekends'),
                'is_public' => $request->boolean('profile_visible', true),
                'show_earnings' => $request->boolean('show_earnings', false),
                'allow_direct_contact' => $request->boolean('allow_business_contact', true),
            ]);
            $profile->save();

            return redirect()->route('worker.preferences')
                ->with('success', 'Preferences updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Worker Preferences Update Error: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to update preferences. Please try again.');
        }
    }

    /**
     * Show the worker shift history
     */
    public function shiftHistory(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get filter parameters
        $status = request('status', 'all');
        $period = request('period', 'all');

        // Base query for completed/past shifts
        $query = ShiftAssignment::with(['shift.business', 'shift.venue'])
            ->where('worker_id', $user->id);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['completed', 'cancelled', 'no_show']);
        }

        // Apply period filter
        if ($period !== 'all') {
            $startDate = match ($period) {
                'this_week' => Carbon::now()->startOfWeek(),
                'this_month' => Carbon::now()->startOfMonth(),
                'last_month' => Carbon::now()->subMonth()->startOfMonth(),
                'this_year' => Carbon::now()->startOfYear(),
                default => null,
            };
            $endDate = match ($period) {
                'last_month' => Carbon::now()->subMonth()->endOfMonth(),
                default => Carbon::now(),
            };

            if ($startDate) {
                $query->whereHas('shift', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('shift_date', [$startDate, $endDate]);
                });
            }
        }

        // Get paginated results
        $assignments = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_shifts' => ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'total_hours' => ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->sum('hours_worked'),
            'cancelled' => ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'cancelled')
                ->count(),
            'no_shows' => ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'no_show')
                ->count(),
        ];

        return view('worker.shift-history', compact('assignments', 'stats', 'status', 'period'));
    }

    /**
     * Show the worker tax documents
     */
    public function taxDocuments(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get available tax years (years with earnings)
        $taxYears = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->where('status', 'paid_out')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Add current year if not present and has earnings
        $currentYear = Carbon::now()->year;
        if (! in_array($currentYear, $taxYears)) {
            $currentYearEarnings = DB::table('shift_payments')
                ->where('worker_id', $user->id)
                ->whereYear('created_at', $currentYear)
                ->exists();
            if ($currentYearEarnings) {
                array_unshift($taxYears, $currentYear);
            }
        }

        // Get yearly earnings summaries
        $yearlySummaries = [];
        foreach ($taxYears as $year) {
            $yearStart = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $yearEnd = Carbon::createFromDate($year, 12, 31)->endOfYear();

            $totalEarnings = DB::table('shift_payments')
                ->where('worker_id', $user->id)
                ->whereBetween('created_at', [$yearStart, $yearEnd])
                ->sum('amount_net') / 100; // Convert cents to dollars

            $totalShifts = ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$yearStart, $yearEnd])
                ->count();

            $totalHours = ShiftAssignment::where('worker_id', $user->id)
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$yearStart, $yearEnd])
                ->sum('hours_worked');

            $yearlySummaries[$year] = [
                'total_earnings' => $totalEarnings,
                'total_shifts' => $totalShifts,
                'total_hours' => $totalHours,
                'requires_1099' => $totalEarnings >= 600, // IRS threshold
                'form_available' => $year < $currentYear && $totalEarnings >= 600,
            ];
        }

        // Get tax form submissions/downloads
        $taxForms = DB::table('tax_documents')
            ->where('user_id', $user->id)
            ->orderBy('tax_year', 'desc')
            ->get()
            ->keyBy('tax_year');

        // Check if W-9 is on file
        $hasW9 = DB::table('verification_documents')
            ->where('user_id', $user->id)
            ->where('document_type', 'w9')
            ->where('status', 'verified')
            ->exists();

        // Get user's tax info status
        $taxInfoStatus = [
            'has_w9' => $hasW9,
            'has_ssn' => ! empty($user->ssn_last_four),
            'tax_classification' => $user->tax_classification ?? 'individual',
            'business_name' => $user->business_tax_name,
        ];

        return view('worker.tax-documents', compact(
            'taxYears',
            'yearlySummaries',
            'taxForms',
            'taxInfoStatus',
            'currentYear'
        ));
    }

    /**
     * Show the worker withdrawal page
     */
    public function withdraw(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get available balance (payments released but not withdrawn)
        $availableBalance = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->where('status', 'released')
            ->sum('amount_net') / 100; // Convert cents to dollars

        // Get pending balance (in escrow)
        $pendingBalance = DB::table('shift_payments')
            ->where('worker_id', $user->id)
            ->where('status', 'in_escrow')
            ->sum('amount_net') / 100;

        // Get processing balance (withdrawal requested but not completed)
        $processingBalance = DB::table('withdrawals')
            ->where('user_id', $user->id)
            ->where('status', 'processing')
            ->sum('amount') / 100;

        // Get connected payout methods
        $payoutMethods = DB::table('payout_methods')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->get();

        // Get recent withdrawals
        $recentWithdrawals = DB::table('withdrawals')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($withdrawal) {
                $withdrawal->amount = $withdrawal->amount / 100;

                return $withdrawal;
            });

        // Get withdrawal stats
        $withdrawalStats = [
            'total_withdrawn' => DB::table('withdrawals')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->sum('amount') / 100,
            'this_month' => DB::table('withdrawals')
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('amount') / 100,
            'pending_count' => DB::table('withdrawals')
                ->where('user_id', $user->id)
                ->where('status', 'processing')
                ->count(),
        ];

        // Minimum withdrawal amount
        $minWithdrawal = config('payment.min_withdrawal', 25);

        // Check if instant payout is available
        $instantPayoutAvailable = $user->is_verified_worker && $availableBalance >= $minWithdrawal;

        return view('worker.withdraw', compact(
            'availableBalance',
            'pendingBalance',
            'processingBalance',
            'payoutMethods',
            'recentWithdrawals',
            'withdrawalStats',
            'minWithdrawal',
            'instantPayoutAvailable'
        ));
    }

    /**
     * Process a withdrawal request
     */
    public function processWithdrawal(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        // PRIORITY-0: Generate idempotency key from request
        $idempotencyKey = $request->header('Idempotency-Key') ??
                         $request->input('idempotency_key') ??
                         hash('sha256', $user->id.'_'.$request->amount.'_'.$request->payout_method_id.'_'.now()->timestamp);

        // PRIORITY-0: Check for duplicate withdrawal request
        $existingWithdrawal = DB::table('withdrawal_idempotency')
            ->where('idempotency_key', $idempotencyKey)
            ->where('user_id', $user->id)
            ->first();

        if ($existingWithdrawal) {
            if ($existingWithdrawal->status === 'completed') {
                return redirect()->back()
                    ->with('error', 'This withdrawal request has already been processed.');
            }

            if ($existingWithdrawal->status === 'processing') {
                return redirect()->back()
                    ->with('error', 'This withdrawal request is currently being processed.');
            }
        }

        $minWithdrawal = config('payment.min_withdrawal', 25);

        $request->validate([
            'amount' => "required|numeric|min:{$minWithdrawal}",
            'payout_method_id' => 'required|exists:payout_methods,id',
            'instant' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // PRIORITY-0: Record withdrawal request for idempotency
            if (! $existingWithdrawal) {
                DB::table('withdrawal_idempotency')->insert([
                    'user_id' => $user->id,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'processing',
                    'request_data' => json_encode($request->all()),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                // Update existing record to processing
                DB::table('withdrawal_idempotency')
                    ->where('id', $existingWithdrawal->id)
                    ->update([
                        'status' => 'processing',
                        'updated_at' => Carbon::now(),
                    ]);
            }

            // Get available balance
            $availableBalance = DB::table('shift_payments')
                ->where('worker_id', $user->id)
                ->where('status', 'released')
                ->sum('amount_net') / 100;

            $amount = $request->amount;

            if ($amount > $availableBalance) {
                DB::table('withdrawal_idempotency')
                    ->where('idempotency_key', $idempotencyKey)
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Insufficient balance',
                        'updated_at' => Carbon::now(),
                    ]);
                DB::rollBack();

                return redirect()->back()
                    ->with('error', 'Insufficient balance for this withdrawal.');
            }

            // Verify payout method belongs to user
            $payoutMethod = DB::table('payout_methods')
                ->where('id', $request->payout_method_id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (! $payoutMethod) {
                DB::table('withdrawal_idempotency')
                    ->where('idempotency_key', $idempotencyKey)
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Invalid payout method',
                        'updated_at' => Carbon::now(),
                    ]);
                DB::rollBack();

                return redirect()->back()
                    ->with('error', 'Invalid payout method selected.');
            }

            // Calculate fees
            $isInstant = $request->boolean('instant') && $user->is_verified_worker;
            $feeRate = $isInstant ? 0.015 : 0; // 1.5% for instant
            $fee = round($amount * $feeRate, 2);
            $netAmount = $amount - $fee;

            // Create withdrawal record
            $withdrawalId = DB::table('withdrawals')->insertGetId([
                'user_id' => $user->id,
                'payout_method_id' => $payoutMethod->id,
                'amount' => $amount * 100, // Store in cents
                'fee' => $fee * 100,
                'net_amount' => $netAmount * 100,
                'status' => 'processing',
                'is_instant' => $isInstant,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // PRIORITY-0: Update idempotency record with withdrawal ID
            DB::table('withdrawal_idempotency')
                ->where('idempotency_key', $idempotencyKey)
                ->update([
                    'withdrawal_id' => $withdrawalId,
                    'updated_at' => Carbon::now(),
                ]);

            // Mark payments as withdrawn (up to the withdrawal amount)
            $remainingAmount = $amount * 100;
            $payments = DB::table('shift_payments')
                ->where('worker_id', $user->id)
                ->where('status', 'released')
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($payments as $payment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                if ($payment->amount_net <= $remainingAmount) {
                    DB::table('shift_payments')
                        ->where('id', $payment->id)
                        ->update([
                            'status' => 'withdrawn',
                            'withdrawal_id' => $withdrawalId,
                            'updated_at' => Carbon::now(),
                        ]);
                    $remainingAmount -= $payment->amount_net;
                } else {
                    // Partial withdrawal - split the payment
                    DB::table('shift_payments')
                        ->where('id', $payment->id)
                        ->update([
                            'amount_net' => $payment->amount_net - $remainingAmount,
                            'updated_at' => Carbon::now(),
                        ]);

                    // Create a new record for the withdrawn portion
                    DB::table('shift_payments')->insert([
                        'worker_id' => $user->id,
                        'shift_assignment_id' => $payment->shift_assignment_id,
                        'amount_gross' => $remainingAmount,
                        'amount_net' => $remainingAmount,
                        'status' => 'withdrawn',
                        'withdrawal_id' => $withdrawalId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    $remainingAmount = 0;
                }
            }

            // PRIORITY-0: Mark idempotency as completed
            DB::table('withdrawal_idempotency')
                ->where('idempotency_key', $idempotencyKey)
                ->update([
                    'status' => 'completed',
                    'response_data' => json_encode(['withdrawal_id' => $withdrawalId]),
                    'processed_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            DB::commit();

            // Log the activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $netAmount,
                    'payout_method' => $payoutMethod->type,
                    'instant' => $isInstant,
                    'idempotency_key' => $idempotencyKey,
                ])
                ->log('Withdrawal requested');

            $message = $isInstant
                ? "Instant withdrawal of \${$netAmount} initiated. Funds should arrive within minutes."
                : "Withdrawal of \${$netAmount} initiated. Funds will arrive in 2-3 business days.";

            return redirect()->route('worker.withdraw')
                ->with('success', $message);

        } catch (\Exception $e) {
            // PRIORITY-0: Mark idempotency as failed
            if (isset($idempotencyKey)) {
                DB::table('withdrawal_idempotency')
                    ->where('idempotency_key', $idempotencyKey)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'updated_at' => Carbon::now(),
                    ]);
            }

            if (isset($withdrawalId)) {
                DB::rollBack();
            }

            \Log::error('Worker Withdrawal Error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $request->amount,
                'idempotency_key' => $idempotencyKey ?? null,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to process withdrawal. Please try again later.');
        }
    }
}
