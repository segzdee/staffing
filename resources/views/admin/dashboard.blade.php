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
                <div class="space-y-4">
                    @forelse(($recent_users ?? collect()) as $user)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <span
                                        class="text-sm font-semibold text-gray-600">{{ substr($user->name ?? 'U', 0, 1) }}</span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $user->name }}</h4>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                                    {{ ucfirst($user->user_type ?? 'user') }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ $user->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
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
            <x-dashboard.quick-actions>
                <x-dashboard.quick-action href="{{ route('admin.users') }}"
                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    variant="primary">
                    Manage Users
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('admin.users') }}"
                    icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                    variant="secondary">
                    Review Verifications
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('admin.shifts.index') }}"
                    icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    variant="secondary">
                    View All Shifts
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('settings.index') }}"
                    icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    variant="secondary">
                    Platform Settings
                </x-dashboard.quick-action>
            </x-dashboard.quick-actions>

            <!-- Recent Shifts -->
            <x-dashboard.sidebar-section title="Recent Shifts" :action="route('admin.shifts.index')" actionLabel="View all">
                <div class="space-y-3">
                    @forelse(($recent_shifts ?? collect()) as $shift)
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <h4 class="font-medium text-gray-900 text-sm truncate">{{ $shift->title }}</h4>
                            <p class="text-xs text-gray-500 mt-1">{{ $shift->business_name ?? 'Unknown Business' }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($shift->created_at)->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No recent shifts</p>
                    @endforelse
                </div>
            </x-dashboard.sidebar-section>

            <!-- System Status -->
            <x-dashboard.sidebar-section title="System Status">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Database</span>
                        <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            Connected
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cache</span>
                        <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Queue</span>
                        <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            Running
                        </span>
                    </div>
                </div>
            </x-dashboard.sidebar-section>
        </div>
    </div>
@endsection