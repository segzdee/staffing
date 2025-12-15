@extends('layouts.dashboard')

@section('title', 'Worker Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Ready to find your next shift')

@section('content')

<!-- FIN-006: Appeal Outcome Banner -->
@if(isset($recentAppealOutcome) && $recentAppealOutcome)
<div class="mb-6 rounded-xl border {{ $recentAppealOutcome->status === 'approved' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
    <div class="p-4 sm:p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                @if($recentAppealOutcome->status === 'approved')
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                @else
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold {{ $recentAppealOutcome->status === 'approved' ? 'text-green-800' : 'text-red-800' }}">
                    Appeal {{ ucfirst($recentAppealOutcome->status) }}
                </h3>
                <p class="mt-1 text-sm {{ $recentAppealOutcome->status === 'approved' ? 'text-green-700' : 'text-red-700' }}">
                    @if($recentAppealOutcome->status === 'approved')
                        @if($recentAppealOutcome->adjusted_amount === null || $recentAppealOutcome->adjusted_amount == 0)
                            Your penalty appeal has been approved with a full waiver. The penalty has been removed from your account.
                        @else
                            Your penalty appeal has been approved. The penalty amount has been reduced to ${{ number_format($recentAppealOutcome->adjusted_amount, 2) }}.
                        @endif
                    @else
                        Your penalty appeal has been reviewed but was not approved. The original penalty of ${{ number_format($recentAppealOutcome->penalty->penalty_amount ?? 0, 2) }} remains in effect.
                    @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="{{ route('worker.appeals.show', $recentAppealOutcome->id) }}"
                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg {{ $recentAppealOutcome->status === 'approved' ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }} transition-colors">
                        View Details
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <span class="text-xs {{ $recentAppealOutcome->status === 'approved' ? 'text-green-600' : 'text-red-600' }}">
                        Reviewed {{ $recentAppealOutcome->reviewed_at ? $recentAppealOutcome->reviewed_at->diffForHumans() : 'recently' }}
                    </span>
                </div>
            </div>
            <button type="button" onclick="this.closest('.mb-6').remove()"
                    class="flex-shrink-0 p-1 rounded-lg {{ $recentAppealOutcome->status === 'approved' ? 'text-green-600 hover:bg-green-100' : 'text-red-600 hover:bg-red-100' }} transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-medium text-yellow-800">
                You have {{ $pendingAppealsCount }} pending appeal{{ $pendingAppealsCount > 1 ? 's' : '' }} under review
            </p>
        </div>
        <a href="{{ route('worker.penalties.index') }}" class="text-sm font-medium text-yellow-800 hover:text-yellow-900 underline">
            View Appeals
        </a>
    </div>
</div>
@endif

<!-- Live Shift Market -->
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Live Shift Market</h3>
            <p class="text-sm text-gray-500">Real-time shifts with instant claim opportunities</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
            <span class="w-2 h-2 rounded-full bg-gray-600 mr-2 animate-pulse"></span>
            LIVE
        </span>
    </div>
    <x-live-shift-market variant="full" :limit="20" />
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming Shifts -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Upcoming Shifts</h3>
                <a href="{{ route('worker.assignments') }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">View all →</a>
            </div>
            <div class="p-6 space-y-4">
                @forelse($upcomingShifts ?? [] as $assignment)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors gap-3">
                    <div class="flex items-center space-x-4 min-w-0">
                        <div class="p-3 bg-gray-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-medium text-gray-900 truncate">{{ $assignment->shift->title ?? 'Untitled Shift' }}</h4>
                            <p class="text-sm text-gray-500 truncate">{{ $assignment->shift->business->name ?? 'Unknown Business' }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $assignment->shift?->shift_date ? \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M j, Y') : 'Date TBD' }} •
                                {{ $assignment->shift?->start_time ? \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') : '--:--' }} -
                                {{ $assignment->shift?->end_time ? \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') : '--:--' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right flex-shrink-0">
                        <p class="font-semibold text-gray-900">${{ number_format($assignment->shift->final_rate ?? 0, 2) }}/hr</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                            {{ ucfirst($assignment->status ?? 'pending') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming shifts</h3>
                    <p class="mt-1 text-sm text-gray-500">Start browsing available shifts to get started.</p>
                    <div class="mt-6">
                        <a href="{{ route('shifts.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                            Browse Shifts
                        </a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Profile Completeness -->
            @if(($profileCompleteness ?? 0) < 100)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Complete Your Profile</h3>
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">{{ $profileCompleteness ?? 0 }}% Complete</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $profileCompleteness ?? 0 }}%"></div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Complete your profile to increase your chances of getting hired.</p>
                <a href="{{ route('worker.profile') }}" class="block text-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium">
                    Update Profile
                </a>
            </div>
            @endif

            <!-- Quick Stats -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Hours</span>
                        <span class="text-sm font-semibold text-gray-900">{{ number_format($totalHours ?? 0, 1) }}h</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">This Week</span>
                        <span class="text-sm font-semibold text-gray-900">{{ number_format($weekStats['hours'] ?? 0, 1) }}h</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Applications</span>
                        <span class="text-sm font-semibold text-gray-900">{{ ($recentApplications ?? collect())->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            @if(($recentApplications ?? collect())->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Applications</h3>
                    <a href="{{ route('worker.applications') }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">View all →</a>
                </div>
                <div class="space-y-3">
                    @foreach(($recentApplications ?? collect())->take(3) as $application)
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $application->shift->title ?? 'Untitled Shift' }}</p>
                            <p class="text-xs text-gray-500">{{ $application->created_at ? \Carbon\Carbon::parse($application->created_at)->diffForHumans() : 'Recently' }}</p>
                        </div>
                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ ($application->status ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ ($application->status ?? '') === 'accepted' ? 'bg-green-100 text-green-800' : '' }}
                            {{ ($application->status ?? '') === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($application->status ?? 'pending') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
