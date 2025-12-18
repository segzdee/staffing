<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
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
                    ->sum(DB::raw('s.duration_hours * s.final_rate')),
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
                    'value' => '$'.number_format($totalSpent, 2),
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
            \Log::error('Business Dashboard Error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
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
                'unreadMessages' => 0,
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
     */
    private function calculateProfileCompleteness($user): int
    {
        $completeness = 0;

        // Base user fields
        if ($user->name) {
            $completeness += 15;
        }
        if ($user->email) {
            $completeness += 15;
        }
        if ($user->avatar && $user->avatar != 'avatar.jpg') {
            $completeness += 10;
        }

        // Business profile fields
        if ($user->businessProfile) {
            $profile = $user->businessProfile;
            if ($profile->company_name) {
                $completeness += 15;
            }
            if ($profile->business_type) {
                $completeness += 10;
            }
            if ($profile->address) {
                $completeness += 10;
            }
            if ($profile->city && $profile->state) {
                $completeness += 10;
            }
            if ($profile->phone) {
                $completeness += 10;
            }
            if ($profile->description) {
                $completeness += 5;
            }
        }

        return min($completeness, 100);
    }

    /**
     * Show the applications page
     */
    public function applications(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get filter parameters
        $status = request('status', 'pending');
        $shiftId = request('shift_id');

        // Base query for applications to business's shifts
        $query = ShiftApplication::with(['shift', 'worker.workerProfile'])
            ->whereHas('shift', function ($q) use ($user) {
                $q->where('business_id', $user->id);
            });

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Apply shift filter
        if ($shiftId) {
            $query->where('shift_id', $shiftId);
        }

        // Get paginated applications
        $applications = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get stats
        $stats = [
            'pending' => ShiftApplication::whereHas('shift', fn ($q) => $q->where('business_id', $user->id))
                ->where('status', 'pending')
                ->count(),
            'approved' => ShiftApplication::whereHas('shift', fn ($q) => $q->where('business_id', $user->id))
                ->where('status', 'approved')
                ->count(),
            'rejected' => ShiftApplication::whereHas('shift', fn ($q) => $q->where('business_id', $user->id))
                ->where('status', 'rejected')
                ->count(),
            'total' => ShiftApplication::whereHas('shift', fn ($q) => $q->where('business_id', $user->id))
                ->count(),
        ];

        // Get shifts with pending applications for filter dropdown
        $shiftsWithApplications = Shift::where('business_id', $user->id)
            ->whereHas('applications', fn ($q) => $q->where('status', 'pending'))
            ->orderBy('shift_date', 'asc')
            ->get(['id', 'title', 'shift_date']);

        return view('business.applications', compact('applications', 'stats', 'status', 'shiftsWithApplications'));
    }

    /**
     * Show the documents page
     */
    public function documents(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get verification documents
        $verificationDocuments = \App\Models\VerificationDocument::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get business verification status
        $businessVerification = \App\Models\BusinessVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        // Group documents by type for display
        $documentGroups = [
            'business_registration' => [
                'title' => 'Business Registration',
                'description' => 'Business license and registration documents',
                'documents' => $verificationDocuments->filter(fn ($d) => in_array($d->document_type, ['business_license', 'registration', 'articles_of_incorporation'])),
                'required' => true,
            ],
            'tax_documents' => [
                'title' => 'Tax Documents',
                'description' => 'EIN, W-9, or other tax-related documents',
                'documents' => $verificationDocuments->filter(fn ($d) => in_array($d->document_type, ['ein', 'w9', 'tax_registration'])),
                'required' => true,
            ],
            'insurance' => [
                'title' => 'Insurance',
                'description' => 'Liability and workers compensation insurance',
                'documents' => $verificationDocuments->filter(fn ($d) => in_array($d->document_type, ['liability_insurance', 'workers_comp', 'insurance_certificate'])),
                'required' => false,
            ],
            'other' => [
                'title' => 'Other Documents',
                'description' => 'Additional supporting documents',
                'documents' => $verificationDocuments->filter(fn ($d) => ! in_array($d->document_type, ['business_license', 'registration', 'articles_of_incorporation', 'ein', 'w9', 'tax_registration', 'liability_insurance', 'workers_comp', 'insurance_certificate'])),
                'required' => false,
            ],
        ];

        // Calculate document completion status
        $documentStatus = [
            'is_verified' => $user->is_verified_business,
            'verification_status' => $businessVerification?->status ?? 'not_started',
            'total_documents' => $verificationDocuments->count(),
            'verified_documents' => $verificationDocuments->where('status', 'verified')->count(),
            'pending_documents' => $verificationDocuments->where('status', 'pending')->count(),
            'rejected_documents' => $verificationDocuments->where('status', 'rejected')->count(),
        ];

        return view('business.documents', compact('documentGroups', 'documentStatus', 'businessVerification'));
    }

    /**
     * Show the locations page
     */
    public function locations(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get all venues for this business
        $venues = \App\Models\Venue::where('business_id', $user->id)
            ->withCount(['shifts', 'shifts as active_shifts_count' => function ($query) {
                $query->where('shift_date', '>=', Carbon::today())
                    ->whereIn('status', ['open', 'filled', 'in_progress']);
            }])
            ->orderBy('name')
            ->get();

        // Get stats
        $stats = [
            'total_venues' => $venues->count(),
            'active_venues' => $venues->where('is_active', true)->count(),
            'total_shifts' => $venues->sum('shifts_count'),
        ];

        return view('business.locations', compact('venues', 'stats'));
    }

    /**
     * Show the payments add funds page
     */
    public function paymentsAddFunds(): \Illuminate\View\View
    {
        return view('business.payments.add-funds');
    }

    /**
     * Show the escrow page
     */
    public function paymentsEscrow(): \Illuminate\View\View
    {
        return view('business.payments.escrow');
    }

    /**
     * Show the payments history page
     */
    public function paymentsHistory(): \Illuminate\View\View
    {
        return view('business.payments.history');
    }

    /**
     * Show the invoices page
     */
    public function paymentsInvoices(): \Illuminate\View\View
    {
        return view('business.payments.invoices');
    }

    /**
     * Show the pending payments page
     */
    public function paymentsPending(): \Illuminate\View\View
    {
        return view('business.payments.pending');
    }

    /**
     * Show the analytics reports page
     */
    public function reportsAnalytics(): \Illuminate\View\View
    {
        return view('business.reports.analytics');
    }

    /**
     * Show the export reports page
     */
    public function reportsExport(): \Illuminate\View\View
    {
        return view('business.reports.export');
    }

    /**
     * Show the performance reports page
     */
    public function reportsPerformance(): \Illuminate\View\View
    {
        return view('business.reports.performance');
    }

    /**
     * Show the spending reports page
     */
    public function reportsSpending(): \Illuminate\View\View
    {
        return view('business.reports.spending');
    }

    /**
     * Show the shifts history page
     */
    public function shiftsHistory(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get filter parameters
        $status = request('status', 'all');
        $period = request('period', 'all');

        // Base query for past shifts
        $query = Shift::with(['assignments.worker', 'venue'])
            ->where('business_id', $user->id);

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['completed', 'cancelled']);
        }

        // Apply period filter
        if ($period !== 'all') {
            $startDate = match ($period) {
                'this_week' => Carbon::now()->startOfWeek(),
                'last_week' => Carbon::now()->subWeek()->startOfWeek(),
                'this_month' => Carbon::now()->startOfMonth(),
                'last_month' => Carbon::now()->subMonth()->startOfMonth(),
                'this_year' => Carbon::now()->startOfYear(),
                default => null,
            };
            $endDate = match ($period) {
                'last_week' => Carbon::now()->subWeek()->endOfWeek(),
                'last_month' => Carbon::now()->subMonth()->endOfMonth(),
                default => Carbon::now(),
            };

            if ($startDate) {
                $query->whereBetween('shift_date', [$startDate, $endDate]);
            }
        }

        // Get paginated results
        $shifts = $query->orderBy('shift_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get stats
        $stats = [
            'total_completed' => Shift::where('business_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'total_cancelled' => Shift::where('business_id', $user->id)
                ->where('status', 'cancelled')
                ->count(),
            'total_hours' => ShiftAssignment::join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $user->id)
                ->where('shift_assignments.status', 'completed')
                ->sum('shift_assignments.hours_worked'),
            'total_spent' => DB::table('shift_assignments as sa')
                ->join('shifts as s', 'sa.shift_id', '=', 's.id')
                ->where('s.business_id', $user->id)
                ->where('sa.status', 'completed')
                ->sum(DB::raw('sa.hours_worked * s.final_rate')),
        ];

        return view('business.shifts.history', compact('shifts', 'stats', 'status', 'period'));
    }

    /**
     * Show the pending shifts page
     */
    public function shiftsPending(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get shifts with pending applications
        $shiftsWithApplications = Shift::with(['applications' => function ($query) {
            $query->where('status', 'pending')->with('worker');
        }])
            ->where('business_id', $user->id)
            ->where('shift_date', '>=', Carbon::today())
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('applications', function ($query) {
                $query->where('status', 'pending');
            })
            ->orderBy('shift_date', 'asc')
            ->paginate(10)
            ->withQueryString();

        // Get all pending applications count
        $totalPendingApplications = ShiftApplication::join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
            ->where('shifts.business_id', $user->id)
            ->where('shift_applications.status', 'pending')
            ->count();

        // Get stats
        $stats = [
            'total_pending' => $shiftsWithApplications->total(),
            'total_applications' => $totalPendingApplications,
            'needs_action' => $totalPendingApplications,
            'starting_soon' => ShiftApplication::join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
                ->where('shifts.business_id', $user->id)
                ->where('shift_applications.status', 'pending')
                ->whereDate('shifts.shift_date', '<=', Carbon::today()->addDays(2))
                ->count(),
        ];

        $shifts = $shiftsWithApplications;

        return view('business.shifts.pending', compact('shifts', 'stats'));
    }

    /**
     * Show the shift templates page
     */
    public function shiftsTemplates(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get shift templates for this business
        $templates = \App\Models\ShiftTemplate::where('business_id', $user->id)
            ->with('venue')
            ->withCount('shifts')
            ->orderBy('name')
            ->get();

        // Get venues for the create form
        $venues = \App\Models\Venue::where('business_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get stats
        $stats = [
            'total_templates' => $templates->count(),
            'active_templates' => $templates->where('is_active', true)->count(),
            'shifts_created' => $templates->sum('shifts_count'),
        ];

        return view('business.shifts.templates', compact('templates', 'venues', 'stats'));
    }

    /**
     * Show the upcoming shifts page
     */
    public function shiftsUpcoming(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get filter parameters
        $status = request('status', 'all');
        $period = request('period', 'all');

        // Base query for upcoming shifts
        $query = Shift::with(['assignments.worker', 'venue'])
            ->where('business_id', $user->id)
            ->where('shift_date', '>=', Carbon::today());

        // Apply status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['open', 'in_progress', 'filled']);
        }

        // Apply period filter
        if ($period !== 'all') {
            $endDate = match ($period) {
                'today' => Carbon::today()->endOfDay(),
                'this_week' => Carbon::now()->endOfWeek(),
                'next_week' => Carbon::now()->addWeek()->endOfWeek(),
                'this_month' => Carbon::now()->endOfMonth(),
                default => null,
            };

            if ($endDate) {
                $query->where('shift_date', '<=', $endDate);
            }
        }

        // Get paginated results
        $shifts = $query->orderBy('shift_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Get stats
        $stats = [
            'total_upcoming' => Shift::where('business_id', $user->id)
                ->where('shift_date', '>=', Carbon::today())
                ->whereIn('status', ['open', 'in_progress', 'filled'])
                ->count(),
            'open' => Shift::where('business_id', $user->id)
                ->where('shift_date', '>=', Carbon::today())
                ->where('status', 'open')
                ->count(),
            'filled' => Shift::where('business_id', $user->id)
                ->where('shift_date', '>=', Carbon::today())
                ->where('status', 'filled')
                ->count(),
            'today' => Shift::where('business_id', $user->id)
                ->whereDate('shift_date', Carbon::today())
                ->whereIn('status', ['open', 'in_progress', 'filled'])
                ->count(),
            'this_week' => Shift::where('business_id', $user->id)
                ->whereBetween('shift_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->whereIn('status', ['open', 'in_progress', 'filled'])
                ->count(),
        ];

        return view('business.shifts.upcoming', compact('shifts', 'stats', 'status', 'period'));
    }

    /**
     * Show the team page
     */
    public function team(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get team members for this business
        $teamMembers = \App\Models\BusinessTeamMember::where('business_id', $user->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get pending invitations
        $pendingInvites = \App\Models\BusinessTeamInvite::where('business_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get stats
        $stats = [
            'total_members' => $teamMembers->count(),
            'admins' => $teamMembers->where('role', 'admin')->count(),
            'managers' => $teamMembers->where('role', 'manager')->count(),
            'pending_invites' => $pendingInvites->count(),
        ];

        return view('business.team', compact('teamMembers', 'pendingInvites', 'stats'));
    }

    /**
     * Show the blocked workers page
     */
    public function workersBlocked(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get blocked workers for this business
        $blockedWorkers = \App\Models\BlockedWorker::where('business_id', $user->id)
            ->with(['worker.workerProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get stats
        $stats = [
            'total_blocked' => \App\Models\BlockedWorker::where('business_id', $user->id)->count(),
        ];

        return view('business.workers.blocked', compact('blockedWorkers', 'stats'));
    }

    /**
     * Show the favourite workers page
     */
    public function workersFavourites(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get favourite workers for this business
        $favouriteWorkers = \App\Models\FavouriteWorker::where('business_id', $user->id)
            ->with(['worker.workerProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get stats
        $stats = [
            'total_favourites' => \App\Models\FavouriteWorker::where('business_id', $user->id)->count(),
            'available_now' => \App\Models\FavouriteWorker::where('business_id', $user->id)
                ->whereHas('worker.workerProfile', fn ($q) => $q->where('is_available', true))
                ->count(),
        ];

        return view('business.workers.favourites', compact('favouriteWorkers', 'stats'));
    }

    /**
     * Show the worker reviews page
     */
    public function workersReviews(): \Illuminate\View\View
    {
        $user = Auth::user();

        // Get reviews written by this business
        $reviews = \App\Models\Review::where('reviewer_id', $user->id)
            ->where('reviewer_type', 'business')
            ->with(['reviewee.workerProfile', 'shift'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get stats
        $stats = [
            'total_reviews' => \App\Models\Review::where('reviewer_id', $user->id)
                ->where('reviewer_type', 'business')
                ->count(),
            'average_rating' => \App\Models\Review::where('reviewer_id', $user->id)
                ->where('reviewer_type', 'business')
                ->avg('rating'),
            'pending_reviews' => ShiftAssignment::where('status', 'completed')
                ->whereHas('shift', fn ($q) => $q->where('business_id', $user->id))
                ->whereDoesntHave('businessReview')
                ->count(),
        ];

        return view('business.workers.reviews', compact('reviews', 'stats'));
    }
}
