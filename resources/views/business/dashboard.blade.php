@extends('layouts.dashboard')

@section('title', 'Business Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Manage your shifts and find qualified workers')

@section('content')

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming Shifts -->
        <div class="lg:col-span-2">
            <x-dashboard.widget-card title="Upcoming Shifts" :action="route('business.shifts.index')"
                actionLabel="View all">
                <div class="space-y-4">
                    @forelse($upcomingShifts ?? [] as $shift)
                        <x-dashboard.shift-list-item :title="$shift->title" :date="$shift->shift_date"
                            :start-time="$shift->start_time" :end-time="$shift->end_time" :rate="$shift->final_rate ?? 0"
                            :filled="$shift->filled_workers" :required="$shift->required_workers"
                            :href="route('business.shifts.show', $shift->id)" action-label="View" />
                    @empty
                        <x-dashboard.empty-state
                            icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            title="No upcoming shifts" description="Post a shift to start finding workers."
                            :action-url="route('shifts.create')" action-label="Post a Shift" />
                    @endforelse
                </div>
            </x-dashboard.widget-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <x-dashboard.quick-actions>
                <x-dashboard.quick-action href="{{ route('shifts.create') }}" icon="M12 4v16m8-8H4" variant="primary">
                    Post New Shift
                </x-dashboard.quick-action>
                <x-dashboard.quick-action href="{{ route('business.available-workers') }}"
                    icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" variant="secondary">
                    Find Available Workers
                </x-dashboard.quick-action>
            </x-dashboard.quick-actions>

            <!-- Recent Applications -->
            @if(($recentApplications ?? collect())->count() > 0)
                <x-dashboard.sidebar-section title="Recent Applications">
                    <div class="space-y-3">
                        @foreach(($recentApplications ?? collect())->take(5) as $application)
                            <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center">
                                        <span
                                            class="text-sm font-medium text-muted-foreground">{{ substr($application->worker->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground">{{ $application->worker->name ?? 'Unknown' }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ $application->shift->title ?? 'Shift' }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('business.shifts.applications', $application->shift_id) }}"
                                    class="text-sm text-muted-foreground hover:text-foreground">Review</a>
                            </div>
                        @endforeach
                    </div>
                </x-dashboard.sidebar-section>
            @endif

            <!-- Fill Rate -->
            @if(($averageFillRate ?? 0) > 0)
                <x-dashboard.sidebar-section title="Fill Rate">
                    <x-dashboard.progress-bar label="Average Fill Rate" :value="$averageFillRate ?? 0" :max="100"
                        class="mb-2" />
                    <p class="text-sm text-muted-foreground">Based on last 30 days</p>
                </x-dashboard.sidebar-section>
            @endif
        </div>
    </div>

    <!-- Shifts Needing Attention -->
    @if(($shiftsNeedingAttention ?? collect())->count() > 0)
        <div class="mt-6">
            <x-dashboard.widget-card title="Shifts Needing Attention"
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                <div class="space-y-4">
                    @foreach($shiftsNeedingAttention as $shift)
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-muted/50 rounded-lg border border-border gap-3">
                            <div class="min-w-0 flex-1">
                                <h4 class="font-medium text-foreground truncate">{{ $shift->title }}</h4>
                                <p class="text-sm text-muted-foreground">
                                    {{ \Carbon\Carbon::parse($shift->shift_date)->format('M j, Y') }} |
                                    Only {{ $shift->required_workers - $shift->filled_workers }} spots remaining
                                </p>
                            </div>
                            <a href="{{ route('business.shifts.show', $shift->id) }}"
                                class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-sm font-medium text-center flex-shrink-0">
                                View Applications
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-dashboard.widget-card>
        </div>
    @endif
@endsection