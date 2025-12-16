@extends('layouts.dashboard')

@section('title', 'Agency Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Manage your workers and shift assignments')

@section('content')

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content (2 columns) -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Recent Assignments -->
        <x-dashboard.widget-card
            title="Recent Assignments"
            icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            :action="route('agency.assignments')"
            actionLabel="View all"
        >
            <div class="space-y-4">
                @forelse(($recentAssignments ?? collect()) as $assignment)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg border-l-4 border-gray-500 gap-3">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">
                            <a href="{{ route('shifts.show', $assignment->shift_id) }}" class="hover:text-gray-600">
                                {{ $assignment->shift->title ?? 'Untitled Shift' }}
                            </a>
                        </h4>
                        <div class="mt-1 text-sm text-gray-500 flex flex-wrap items-center gap-2 sm:gap-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="truncate">{{ $assignment->worker->name ?? 'Unknown Worker' }}</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ \Carbon\Carbon::parse($assignment->shift->shift_date ?? now())->format('M d, Y') }}
                            </span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right flex-shrink-0">
                        @php
                            $statusClasses = [
                                'completed' => 'bg-gray-100 text-gray-700',
                                'in_progress' => 'bg-gray-100 text-gray-700',
                                'assigned' => 'bg-gray-100 text-gray-700',
                            ];
                            $statusClass = $statusClasses[$assignment->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                        </span>
                        @if($assignment->status == 'assigned')
                        <p class="text-xs text-gray-400 mt-1">
                            Starts {{ \Carbon\Carbon::parse(($assignment->shift->shift_date ?? now()).' '.($assignment->shift->start_time ?? '00:00'))->diffForHumans() }}
                        </p>
                        @endif
                    </div>
                </div>
                @empty
                <x-dashboard.empty-state
                    icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    title="No recent assignments"
                    description="Browse available shifts to assign your workers."
                    :action-url="route('agency.shifts.browse')"
                    action-label="Browse Available Shifts"
                />
                @endforelse
            </div>
        </x-dashboard.widget-card>

        <!-- Available Shifts -->
        <x-dashboard.widget-card
            title="Available Shifts"
            icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            :action="route('agency.shifts.browse')"
            actionLabel="Browse all"
        >
            <div class="space-y-4">
                @forelse(($availableShifts ?? collect())->take(5) as $shift)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors gap-3">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate">
                            <a href="{{ route('agency.shifts.view', $shift->id) }}" class="hover:text-gray-600">
                                {{ $shift->title }}
                            </a>
                        </h4>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-900">
                                {{ ucfirst($shift->industry) }}
                            </span>
                            @if(($shift->urgency_level ?? 'normal') !== 'normal')
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">
                                {{ ucfirst($shift->urgency_level) }}
                            </span>
                            @endif
                        </div>
                        <div class="mt-2 text-sm text-gray-500 flex flex-wrap items-center gap-2 sm:gap-4">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="truncate">{{ $shift->location_city }}, {{ $shift->location_state }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right flex-shrink-0 sm:ml-4">
                        <p class="text-lg font-bold text-gray-900">${{ number_format(($shift->final_rate ?? 0) / 100, 2) }}/hr</p>
                        <p class="text-sm text-gray-500">{{ $shift->filled_workers ?? 0 }}/{{ $shift->required_workers ?? 1 }} filled</p>
                        <a href="{{ route('agency.shifts.view', $shift->id) }}" class="inline-flex items-center gap-1 mt-2 px-3 py-1 text-sm font-medium text-gray-600 hover:text-gray-900">
                            View & Assign
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @empty
                <x-dashboard.empty-state
                    icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    title="No available shifts"
                    description="Check back later for new opportunities."
                />
                @endforelse
            </div>
        </x-dashboard.widget-card>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <x-dashboard.quick-actions>
            <x-dashboard.quick-action
                href="{{ route('agency.shifts.browse') }}"
                icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                variant="primary"
            >
                Browse Shifts
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.workers.index') }}"
                icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                variant="secondary"
            >
                Manage Workers
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.assignments') }}"
                icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                variant="secondary"
            >
                View Assignments
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.commissions') }}"
                icon="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                variant="secondary"
            >
                Commission Report
            </x-dashboard.quick-action>
        </x-dashboard.quick-actions>

        <!-- Worker Status -->
        <x-dashboard.sidebar-section title="Worker Status">
            <div class="space-y-4">
                <x-dashboard.progress-bar
                    label="Active Workers"
                    :value="$activeWorkers ?? 0"
                    :max="$totalWorkers ?? 1"
                    :show-percentage="false"
                    class="mb-1"
                />
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Active Workers</span>
                    <span class="font-semibold text-gray-900">{{ $activeWorkers ?? 0 }}</span>
                </div>
                <x-dashboard.progress-bar
                    label="Available Workers"
                    :value="($totalWorkers ?? 0) - ($activeWorkers ?? 0)"
                    :max="$totalWorkers ?? 1"
                    :show-percentage="false"
                    class="mb-1"
                />
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Available Workers</span>
                    <span class="font-semibold text-gray-900">{{ ($totalWorkers ?? 0) - ($activeWorkers ?? 0) }}</span>
                </div>
            </div>
            <x-dashboard.quick-action
                href="{{ route('agency.workers.index') }}"
                variant="secondary"
                class="mt-4"
            >
                View All Workers
            </x-dashboard.quick-action>
        </x-dashboard.sidebar-section>

        <!-- Performance Stats -->
        <x-dashboard.sidebar-section title="This Month">
            <x-dashboard.stat-list :stats="[
                ['label' => 'Shifts Filled', 'value' => $completedAssignments ?? 0],
                ['label' => 'Commission Earned', 'value' => '$' . number_format(($totalEarnings ?? 0) / 100, 2)],
                ['label' => 'Completion Rate', 'value' => (($totalAssignments ?? 0) > 0 ? round(($completedAssignments ?? 0) / ($totalAssignments ?? 1) * 100) : 0) . '%'],
            ]" />
        </x-dashboard.sidebar-section>

        <!-- Help & Resources -->
        <x-dashboard.sidebar-section title="Help & Resources">
            <ul class="space-y-2">
                @if(Route::has('contact'))
                <li>
                    <a href="{{ route('contact') }}" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 py-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Contact Support
                    </a>
                </li>
                @endif
            </ul>
        </x-dashboard.sidebar-section>
    </div>
</div>
@endsection
