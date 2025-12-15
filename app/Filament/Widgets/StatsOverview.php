<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Get counts
        $totalUsers = User::count();
        $totalWorkers = User::where('user_type', 'worker')->count();
        $totalBusinesses = User::where('user_type', 'business')->count();
        $totalAgencies = User::where('user_type', 'agency')->count();

        $totalShifts = Shift::count();
        $openShifts = Shift::where('status', 'open')->count();
        $completedShifts = Shift::where('status', 'completed')->count();

        $totalApplications = ShiftApplication::count();
        $pendingApplications = ShiftApplication::where('status', 'pending')->count();

        // Get payment stats (using raw query since amount_gross is stored as cents)
        $totalRevenue = ShiftPayment::where('status', 'completed')
            ->sum('amount_gross');

        $platformFees = ShiftPayment::where('status', 'completed')
            ->sum('platform_fee');

        return [
            Stat::make('Total Users', number_format($totalUsers))
                ->description("Workers: {$totalWorkers} | Businesses: {$totalBusinesses} | Agencies: {$totalAgencies}")
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Shifts', number_format($totalShifts))
                ->description("Open: {$openShifts} | Completed: {$completedShifts}")
                ->icon('heroicon-o-briefcase')
                ->color('success'),

            Stat::make('Applications', number_format($totalApplications))
                ->description("Pending: {$pendingApplications}")
                ->icon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('Total Revenue', '$' . number_format($totalRevenue / 100, 2))
                ->description('Platform Fees: $' . number_format($platformFees / 100, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
