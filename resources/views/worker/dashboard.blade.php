@extends('layouts.dashboard')

@section('title', 'Worker Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Ready to find your next shift')

@section('content')

    <!-- FIN-006: Appeal Outcome Banner -->
    @if(isset($recentAppealOutcome) && $recentAppealOutcome)
        <div
            class="mb-6 rounded-xl border {{ $recentAppealOutcome->status === 'approved' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <div class="p-4 sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        @if($recentAppealOutcome->status === 'approved')
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3
                            class="text-lg font-semibold {{ $recentAppealOutcome->status === 'approved' ? 'text-green-800' : 'text-red-800' }}">
                            Appeal {{ ucfirst($recentAppealOutcome->status) }}
                        </h3>
                        <p
                            class="mt-1 text-sm {{ $recentAppealOutcome->status === 'approved' ? 'text-green-700' : 'text-red-700' }}">
                            @if($recentAppealOutcome->status === 'approved')
                                @if($recentAppealOutcome->adjusted_amount === null || $recentAppealOutcome->adjusted_amount == 0)
                                    Your penalty appeal has been approved with a full waiver. The penalty has been removed from your
                                    account.
                                @else
                                    Your penalty appeal has been approved. The penalty amount has been reduced to
                                    ${{ number_format($recentAppealOutcome->adjusted_amount, 2) }}.
                                @endif
                            @else
                                Your penalty appeal has been reviewed but was not approved. The original penalty of
                                ${{ number_format($recentAppealOutcome->penalty->penalty_amount ?? 0, 2) }} remains in effect.
                            @endif
                        </p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <a href="{{ route('appeals.show', $recentAppealOutcome->id) }}"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg {{ $recentAppealOutcome->status === 'approved' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} transition-colors">
                                View Details
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <span
                                class="text-xs {{ $recentAppealOutcome->status === 'approved' ? 'text-green-600' : 'text-red-600' }}">
                                Reviewed
                                {{ $recentAppealOutcome->reviewed_at ? $recentAppealOutcome->reviewed_at->diffForHumans() : 'recently' }}
                            </span>
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('.mb-6').remove()"
                        class="flex-shrink-0 p-1 rounded-lg {{ $recentAppealOutcome->status === 'approved' ? 'text-green-600 hover:bg-green-100' : 'text-red-600 hover:bg-red-100' }} transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Pending Appeals Alert -->
    @if(isset($pendingAppealsCount) && $pendingAppealsCount > 0)
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800">
                        You have {{ $pendingAppealsCount }} pending appeal{{ $pendingAppealsCount > 1 ? 's' : '' }} under review
                    </p>
                </div>
                <a href="{{ route('worker.penalties.index') }}"
                    class="text-sm font-medium text-yellow-800 hover:text-yellow-900 underline">
                    View Appeals
                </a>
            </div>
        </div>
    @endif

    <!-- Live Shift Market -->
    <x-dashboard.widget-card title="Live Shift Market" :action="route('shifts.index')" actionLabel="Browse all">
        <div class="flex items-center justify-between mb-4 -mt-2">
            <p class="text-sm text-gray-500">Real-time shifts with instant claim opportunities</p>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                <span class="w-2 h-2 rounded-full bg-gray-600 mr-2 animate-pulse"></span>
                LIVE
            </span>
        </div>
        <x-live-shift-market variant="full" :limit="20" />
    </x-dashboard.widget-card>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Upcoming Shifts -->
        <div class="lg:col-span-2">
            <x-dashboard.widget-card title="Upcoming Shifts" :action="route('worker.assignments')" actionLabel="View all">
                <div class="space-y-4">
                    @forelse($upcomingShifts ?? [] as $assignment)
                        <x-dashboard.shift-list-item :title="$assignment->shift->title ?? 'Untitled Shift'"
                            :subtitle="$assignment->shift->business->name ?? 'Unknown Business'"
                            :date="$assignment->shift?->shift_date" :start-time="$assignment->shift?->start_time"
                            :end-time="$assignment->shift?->end_time" :rate="$assignment->shift->final_rate ?? 0"
                            :status="ucfirst($assignment->status ?? 'pending')" status-color="green" />
                    @empty
                        <x-dashboard.empty-state
                            icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            title="No upcoming shifts" description="Start browsing available shifts to get started."
                            :action-url="route('shifts.index')" action-label="Browse Shifts" />
                    @endforelse
                </div>
            </x-dashboard.widget-card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Profile Completeness -->
            @if(($profileCompleteness ?? 0) < 100)
                <x-dashboard.sidebar-section title="Complete Your Profile">
                    <x-dashboard.progress-bar label="" :value="$profileCompleteness ?? 0" :max="100" class="mb-4" />
                    <p class="text-sm text-gray-600 mb-4">Complete your profile to increase your chances of getting hired.</p>
                    <x-dashboard.quick-action href="{{ route('worker.profile') }}" variant="primary">
                        Update Profile
                    </x-dashboard.quick-action>
                </x-dashboard.sidebar-section>
            @endif

            <!-- Quick Stats -->
            <x-dashboard.sidebar-section title="Quick Stats">
                <x-dashboard.stat-list :stats="[
            ['label' => 'Reliability Score', 'value' => (auth()->user()->reliability_score ?? 'N/A') . '%', 'trend' => null],
            ['label' => 'Total Hours', 'value' => number_format($totalHours ?? 0, 1) . 'h'],
            ['label' => 'This Week', 'value' => number_format($weekStats['hours'] ?? 0, 1) . 'h'],
            ['label' => 'Applications', 'value' => ($recentApplications ?? collect())->count()],
        ]" :dividers="false" />
                <div class="mt-4 px-4">
                    <a href="#" class="text-xs text-brand-600 hover:underline">How is this calculated?</a>
                </div>
            </x-dashboard.sidebar-section>

            <!-- Recent Applications -->
            @if(($recentApplications ?? collect())->count() > 0)
                <x-dashboard.sidebar-section title="Recent Applications" :action="route('worker.applications')"
                    actionLabel="View all">
                    <div class="space-y-3">
                        @foreach(($recentApplications ?? collect())->take(3) as $application)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $application->shift->title ?? 'Untitled Shift' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $application->created_at ? \Carbon\Carbon::parse($application->created_at)->diffForHumans() : 'Recently' }}
                                    </p>
                                </div>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'accepted' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$application->status ?? 'pending'] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span
                                    class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($application->status ?? 'pending') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-dashboard.sidebar-section>
            @endif
        </div>
    </div>
@endsection