<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\User;
use App\Models\VerificationQueue;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $settings;

    public function __construct()
    {
        try {
            $this->settings = \App\Models\AdminSettings::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Show Dashboard section - OvertimeStaff Shift Marketplace
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $user = auth()->user();

        // Skip permission check for dev accounts
        if (! $user->is_dev_account && ! $user->hasPermission('dashboard')) {
            return view('admin.unauthorized');
        }

        // ==== USER METRICS ====
        $total_users = User::count();
        $total_workers = User::where('user_type', 'worker')->count();
        $total_businesses = User::where('user_type', 'business')->count();
        $total_agencies = User::where('user_type', 'agency')->count();
        $recent_users = User::orderBy('id', 'DESC')->take(5)->get();

        // ==== SHIFT MARKETPLACE METRICS ====
        $total_shifts = \DB::table('shifts')->count();
        $shifts_open = \DB::table('shifts')->where('status', 'open')->count();
        $shifts_filled_today = \DB::table('shifts')
            ->where('status', 'filled')
            ->whereDate('updated_at', today())
            ->count();
        $shifts_completed = \DB::table('shifts')->where('status', 'completed')->count();
        $recent_shifts = \DB::table('shifts')
            ->join('users', 'shifts.business_id', '=', 'users.id')
            ->select('shifts.*', 'users.name as business_name')
            ->orderBy('shifts.id', 'desc')
            ->take(5)
            ->get();

        // ==== FINANCIAL METRICS ====
        $total_platform_revenue = \DB::table('shift_payments')->sum('platform_fee');

        // Revenue today
        $revenue_today = \DB::table('shift_payments')
            ->whereDate('created_at', today())
            ->sum('platform_fee');

        // Revenue this week
        $revenue_week = \DB::table('shift_payments')
            ->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])
            ->sum('platform_fee');

        // Revenue this month
        $revenue_month = \DB::table('shift_payments')
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->sum('platform_fee');

        // ==== PERFORMANCE METRICS ====
        $avg_fill_rate = \DB::table('shifts')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('filled_at')
            ->count() > 0
            ? (function () {
                $filled = \DB::table('shifts')->where('status', 'filled')->count();
                $total = \DB::table('shifts')->where('status', '!=', 'cancelled')->count();

                return $total > 0 ? ($filled / $total) * 100 : 0;
            })()
            : 0;

        // Active users (last 24 hours)
        $active_users_today = User::where('updated_at', '>=', Carbon::now()->subDay())->count();

        // Pending verifications (OvertimeStaff verification queue)
        $pending_verifications = VerificationQueue::pending()->count();

        // Get notification and message counts for unified dashboard layout
        $unreadNotifications = 0;
        $unreadMessages = 0;

        // Profile completeness for onboarding progress (admin always 100%)
        $onboardingProgress = 100;

        // Prepare metrics array for unified dashboard layout
        $metrics = [
            [
                'label' => 'Total Users',
                'value' => $total_users,
                'subtitle' => 'All user types',
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            ],
            [
                'label' => 'Open Shifts',
                'value' => $shifts_open,
                'subtitle' => 'Currently available',
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            ],
            [
                'label' => 'Revenue Today',
                'value' => '$'.number_format($revenue_today, 2),
                'subtitle' => 'Platform fees',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label' => 'Pending',
                'value' => $pending_verifications,
                'subtitle' => 'Verifications',
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
        ];

        return view('admin.dashboard', [
            // Users
            'total_users' => $total_users,
            'total_workers' => $total_workers,
            'total_businesses' => $total_businesses,
            'total_agencies' => $total_agencies,
            'recent_users' => $recent_users,
            'active_users_today' => $active_users_today,

            // Shifts
            'total_shifts' => $total_shifts,
            'shifts_open' => $shifts_open,
            'shifts_filled_today' => $shifts_filled_today,
            'shifts_completed' => $shifts_completed,
            'recent_shifts' => $recent_shifts,
            'avg_fill_rate' => round($avg_fill_rate, 1),

            // Revenue
            'total_platform_revenue' => $total_platform_revenue,
            'stat_revenue_today' => $revenue_today,
            'stat_revenue_week' => $revenue_week,
            'stat_revenue_month' => $revenue_month,

            // Other
            'pending_verifications' => $pending_verifications,

            // Unified dashboard layout data
            'metrics' => $metrics,
            'onboardingProgress' => $onboardingProgress,
            'unreadNotifications' => $unreadNotifications,
            'unreadMessages' => $unreadMessages,
        ]);
    }

    // Access Control routes
    public function accessAdmins(): \Illuminate\View\View
    {
        return view('admin.access.admins');
    }

    public function accessRoles(): \Illuminate\View\View
    {
        return view('admin.access.roles');
    }

    public function accessAudit(): \Illuminate\View\View
    {
        return view('admin.access.audit');
    }

    // Activity & Alerts
    public function activity(): \Illuminate\View\View
    {
        return view('admin.activity');
    }

    public function alerts(): \Illuminate\View\View
    {
        return view('admin.alerts');
    }

    // Analytics routes
    public function analyticsExport(): \Illuminate\View\View
    {
        return view('admin.analytics.export');
    }

    public function analyticsGeographic(): \Illuminate\View\View
    {
        return view('admin.analytics.geographic');
    }

    public function analyticsGrowth(): \Illuminate\View\View
    {
        return view('admin.analytics.growth');
    }

    public function analyticsPlatform(): \Illuminate\View\View
    {
        return view('admin.analytics.platform');
    }

    public function analyticsRevenue(): \Illuminate\View\View
    {
        return view('admin.analytics.revenue');
    }

    // Finance routes
    public function financeTransactions(): \Illuminate\View\View
    {
        return view('admin.finance.transactions');
    }

    public function financeEscrow(): \Illuminate\View\View
    {
        return view('admin.finance.escrow');
    }

    public function financePayouts(): \Illuminate\View\View
    {
        return view('admin.finance.payouts');
    }

    public function financeRefunds(): \Illuminate\View\View
    {
        return view('admin.finance.refunds');
    }

    public function financeDisputed(): \Illuminate\View\View
    {
        return view('admin.finance.disputed');
    }

    public function financeCommissions(): \Illuminate\View\View
    {
        return view('admin.finance.commissions');
    }

    public function financeReports(): \Illuminate\View\View
    {
        return view('admin.finance.reports');
    }

    // Moderation routes
    public function moderationReports(): \Illuminate\View\View
    {
        return view('admin.moderation.reports');
    }

    public function moderationDisputes(): \Illuminate\View\View
    {
        return view('admin.moderation.disputes');
    }

    public function moderationBans(): \Illuminate\View\View
    {
        return view('admin.moderation.bans');
    }

    public function moderationReviews(): \Illuminate\View\View
    {
        return view('admin.moderation.reviews');
    }

    // Revenue
    public function revenue(): \Illuminate\View\View
    {
        return view('admin.revenue');
    }

    // Settings routes
    public function settingsGeneral(): \Illuminate\View\View
    {
        return view('admin.settings.general');
    }

    public function settingsCategories(): \Illuminate\View\View
    {
        return view('admin.settings.categories');
    }

    public function settingsSkills(): \Illuminate\View\View
    {
        return view('admin.settings.skills');
    }

    public function settingsAreas(): \Illuminate\View\View
    {
        return view('admin.settings.areas');
    }

    public function settingsCommissions(): \Illuminate\View\View
    {
        return view('admin.settings.commissions');
    }

    public function settingsFeatures(): \Illuminate\View\View
    {
        return view('admin.settings.features');
    }

    public function settingsNotifications(): \Illuminate\View\View
    {
        return view('admin.settings.notifications');
    }

    public function settingsEmails(): \Illuminate\View\View
    {
        return view('admin.settings.emails');
    }

    // Shifts routes
    public function shiftsIndex(): \Illuminate\View\View
    {
        return view('admin.shifts.index');
    }

    public function shiftsActive(): \Illuminate\View\View
    {
        return view('admin.shifts.active');
    }

    public function shiftsPending(): \Illuminate\View\View
    {
        return view('admin.shifts.pending');
    }

    public function shiftsCancelled(): \Illuminate\View\View
    {
        return view('admin.shifts.cancelled');
    }

    public function shiftsDisputed(): \Illuminate\View\View
    {
        return view('admin.shifts.disputed');
    }

    public function shiftsAudit(): \Illuminate\View\View
    {
        return view('admin.shifts.audit');
    }

    // Statistics
    public function statistics(): \Illuminate\View\View
    {
        return view('admin.statistics');
    }

    // Support routes
    public function supportTickets(): \Illuminate\View\View
    {
        return view('admin.support.tickets');
    }

    // System routes
    public function systemHealth(): \Illuminate\View\View
    {
        return view('admin.system-health');
    }

    public function systemLogs(): \Illuminate\View\View
    {
        return view('admin.system.logs');
    }

    public function systemJobs(): \Illuminate\View\View
    {
        return view('admin.system.jobs');
    }

    public function systemApiKeys(): \Illuminate\View\View
    {
        return view('admin.system.api-keys');
    }

    public function systemWebhooks(): \Illuminate\View\View
    {
        return view('admin.system.webhooks');
    }

    public function systemIntegrations(): \Illuminate\View\View
    {
        return view('admin.system.integrations');
    }

    // Users routes
    public function users(): \Illuminate\View\View
    {
        // Get filter parameters
        $type = request('type', 'all');
        $status = request('status', 'all');
        $search = request('search', '');

        // Base query
        $query = User::query()->with('workerProfile', 'businessProfile', 'agencyProfile');

        // Apply type filter
        if ($type !== 'all') {
            $query->where('user_type', $type);
        }

        // Apply status filter
        if ($status !== 'all') {
            if ($status === 'active') {
                $query->where('is_active', true)->whereNull('suspended_at');
            } elseif ($status === 'suspended') {
                $query->whereNotNull('suspended_at');
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Get paginated users
        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Get stats
        $stats = [
            'total' => User::count(),
            'workers' => User::where('user_type', 'worker')->count(),
            'businesses' => User::where('user_type', 'business')->count(),
            'agencies' => User::where('user_type', 'agency')->count(),
            'suspended' => User::whereNotNull('suspended_at')->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'type', 'status', 'search'));
    }

    public function usersWorkers(): \Illuminate\View\View
    {
        return view('admin.users.workers');
    }

    public function usersVenues(): \Illuminate\View\View
    {
        return view('admin.users.venues');
    }

    public function usersAgencies(): \Illuminate\View\View
    {
        return view('admin.users.agencies');
    }

    public function usersSuspended(): \Illuminate\View\View
    {
        return view('admin.users.suspended');
    }

    public function usersReports(): \Illuminate\View\View
    {
        return view('admin.users.reports');
    }

    // Verification routes
    public function verificationsPending(): \Illuminate\View\View
    {
        // Get filter parameters
        $type = request('type', 'all');
        $sla = request('sla', 'all');

        // Base query
        $query = VerificationQueue::with('verifiable')
            ->whereIn('status', ['pending', 'in_review']);

        // Apply type filter
        if ($type !== 'all') {
            $query->where('verification_type', $type);
        }

        // Apply SLA filter
        if ($sla !== 'all') {
            $query->where('sla_status', $sla);
        }

        // Get paginated results ordered by priority
        $verifications = $query->orderBy('priority_score', 'desc')
            ->orderBy('submitted_at', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Get stats
        $stats = [
            'total_pending' => VerificationQueue::pending()->count(),
            'in_review' => VerificationQueue::inReview()->count(),
            'at_risk' => VerificationQueue::atRisk()->count(),
            'breached' => VerificationQueue::breached()->count(),
            'identity' => VerificationQueue::pending()->where('verification_type', 'identity')->count(),
            'business' => VerificationQueue::pending()->where('verification_type', 'business_license')->count(),
        ];

        return view('admin.verifications.pending', compact('verifications', 'stats', 'type', 'sla'));
    }

    public function verificationId(): \Illuminate\View\View
    {
        return view('admin.verification.id');
    }

    public function verificationDocuments(): \Illuminate\View\View
    {
        return view('admin.verification.documents');
    }

    public function verificationBusiness(): \Illuminate\View\View
    {
        return view('admin.verification.business');
    }

    public function verificationCompliance(): \Illuminate\View\View
    {
        return view('admin.verification.compliance');
    }
}
