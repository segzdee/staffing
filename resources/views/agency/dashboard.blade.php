@extends('layouts.dashboard')

@section('title', 'Agency Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Manage your workers and shift assignments')

@section('content')

<!-- Live Market Widget -->
<div class="mb-6">
    <x-dashboard.widget-card title="Live Market Activity" :action="route('agency.shifts.browse')" actionLabel="Browse Shifts">
        <x-live-shift-market variant="compact" :limit="4" />
    </x-dashboard.widget-card>
</div>

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
                <x-dashboard.info-item
                    :title="$assignment->shift->title ?? 'Untitled Shift'"
                    :href="route('shifts.show', $assignment->shift_id)"
                    :meta="[
                        ($assignment->worker->name ?? 'Unknown Worker'),
                        \Carbon\Carbon::parse($assignment->shift->shift_date ?? now())->format('M d, Y')
                    ]"
                    :status="ucfirst(str_replace('_', ' ', $assignment->status))"
                    :statusColor="$assignment->status == 'completed' ? 'green' : 'gray'"
                    sideText=""
                />
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
                <x-dashboard.shift-list-item 
                    :title="$shift->title" 
                    :date="$shift->shift_date"
                    :rate="$shift->final_rate / 100"
                    :filled="$shift->filled_workers" 
                    :required="$shift->required_workers"
                    :href="route('agency.shifts.view', $shift->id)" 
                    action-label="View & Assign" 
                />
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
        <x-dashboard.sidebar-section title="Quick Actions">
            <x-dashboard.quick-action
                href="{{ route('agency.shifts.browse') }}"
                variant="primary"
            >
                Browse Shifts
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.workers.index') }}"
            >
                Manage Workers
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.assignments') }}"
            >
                View Assignments
            </x-dashboard.quick-action>
            <x-dashboard.quick-action
                href="{{ route('agency.commissions') }}"
            >
                Commission Report
            </x-dashboard.quick-action>
        </x-dashboard.sidebar-section>

        <!-- Worker Status -->
        <x-dashboard.sidebar-section title="Worker Status">
            <div class="space-y-4 mb-4">
                <x-dashboard.progress-bar
                    label="Active Workers"
                    :value="$activeWorkers ?? 0"
                    :max="$totalWorkers ?? 1"
                    :show-percentage="false"
                    class="mb-1"
                />
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Active Workers</span>
                    <span class="font-semibold text-foreground">{{ $activeWorkers ?? 0 }}</span>
                </div>
                <x-dashboard.progress-bar
                    label="Available Workers"
                    :value="($totalWorkers ?? 0) - ($activeWorkers ?? 0)"
                    :max="$totalWorkers ?? 1"
                    :show-percentage="false"
                    class="mb-1"
                />
                <div class="flex justify-between text-sm">
                    <span class="text-muted-foreground">Available Workers</span>
                    <span class="font-semibold text-foreground">{{ ($totalWorkers ?? 0) - ($activeWorkers ?? 0) }}</span>
                </div>
            </div>
            <x-dashboard.quick-action
                href="{{ route('agency.workers.index') }}"
                variant="secondary"
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
                    <a href="{{ route('contact') }}" class="flex items-center gap-2 text-muted-foreground hover:text-foreground py-2 transition-colors">
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
