@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Panel')
@section('page-subtitle', 'Platform overview and management tools')

@section('content')

    <!-- User Type Breakdown & Revenue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- User Breakdown -->
        <x-dashboard.widget-card title="User Breakdown">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Workers</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($total_workers ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Businesses</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($total_businesses ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">Agencies</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ number_format($total_agencies ?? 0) }}</span>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900">Total Users</span>
                        <span class="font-bold text-gray-900">{{ number_format($total_users ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </x-dashboard.widget-card>

        <!-- Revenue Stats -->
        <x-dashboard.widget-card title="Revenue Overview">
            <x-dashboard.stat-list :stats="[
            ['label' => 'Today', 'value' => '$' . number_format(($stat_revenue_today ?? 0) / 100, 2)],
            ['label' => 'This Week', 'value' => '$' . number_format(($stat_revenue_week ?? 0) / 100, 2)],
            ['label' => 'This Month', 'value' => '$' . number_format(($stat_revenue_month ?? 0) / 100, 2)],
        ]" :dividers="false" />
            <div class="pt-4 mt-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">Fill Rate</span>
                    <span class="font-bold text-gray-900">{{ $avg_fill_rate ?? 0 }}%</span>
                </div>
            </div>
        </x-dashboard.widget-card>
    </div>

    <!-- Live Market Widget -->
    <div class="mb-6">
        <x-dashboard.widget-card title="Live Market Activity" :action="route('admin.shifts.index')"
            actionLabel="Manage Shifts">
            <x-live-shift-market variant="compact" :limit="4" />
        </x-dashboard.widget-card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Users -->
        <div class="lg:col-span-2">
            <x-dashboard.widget-card title="Recent Users"
                icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                :action="route('admin.users')" actionLabel="View all">
                <div class="space-y-3">
                    @forelse(($recent_users ?? collect()) as $user)
                        <x-dashboard.user-list-item :name="$user->name" :email="$user->email"
                            :subtext="$user->created_at->diffForHumans()" :status="ucfirst($user->user_type ?? 'user')"
                            :statusColor="'gray'" />
                    @empty
                        <x-dashboard.empty-state
                            icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            title="No users yet" description="Users will appear here when they register." />
                    @endforelse
                </div>
            </x-dashboard.widget-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <x-dashboard.sidebar-section title="Quick Actions">
                <x-dashboard.quick-action href="{{ route('admin.users') }}" variant="primary">
                    Manage Users
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('admin.users') }}">
                    Review Verifications
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('admin.shifts.index') }}">
                    View All Shifts
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('settings.index') }}">
                    Platform Settings
                </x-dashboard.quick-action>
            </x-dashboard.sidebar-section>

            <!-- Recent Shifts -->
            <x-dashboard.sidebar-section title="Recent Shifts" :action="route('admin.shifts.index')" actionLabel="View all">
                <div class="space-y-3">
                    @forelse(($recent_shifts ?? collect()) as $shift)
                        <x-dashboard.info-item :title="$shift->title" :subtitle="$shift->business_name ?? 'Unknown Business'"
                            :meta="\Carbon\Carbon::parse($shift->created_at)->diffForHumans()" />
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No recent shifts</p>
                    @endforelse
                </div>
            </x-dashboard.sidebar-section>

            <!-- System Status -->
            <x-dashboard.sidebar-section title="System Status">
                <x-dashboard.status-list :items="[
            ['label' => 'Database', 'status' => 'Connected', 'color' => 'green'],
            ['label' => 'Cache', 'status' => 'Active', 'color' => 'green'],
            ['label' => 'Queue', 'status' => 'Running', 'color' => 'green'],
        ]" />
            </x-dashboard.sidebar-section>
        </div>
    </div>
@endsection