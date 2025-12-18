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

    public function __construct(AdminSettings $settings)
    {
        $this->settings = $settings::first();
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
}
