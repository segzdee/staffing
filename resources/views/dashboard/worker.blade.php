@extends('layouts.dashboard')

@section('title', 'Worker Dashboard')
@section('page-title', 'Dashboard')

@section('sidebar-nav')
<!-- Main Navigation -->
<div class="space-y-1">
    <a href="{{ route('dashboard') }}" class="sidebar-link active">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
    </a>
    <a href="{{ route('shifts.index') }}" class="sidebar-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        Browse Shifts
    </a>
    <a href="{{ route('worker.applications') }}" class="sidebar-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        My Applications
    </a>
    <a href="{{ route('worker.assignments') }}" class="sidebar-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        My Assignments
    </a>
    <a href="{{ route('worker.calendar') }}" class="sidebar-link">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Calendar
    </a>
</div>

<!-- Earnings Section -->
<div class="mt-8">
    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Earnings</p>
    <div class="space-y-1">
        <a href="{{ route('worker.earnings') }}" class="sidebar-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Earnings
        </a>
        <a href="#" class="sidebar-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Payouts
        </a>
    </div>
</div>

<!-- Profile Section -->
<div class="mt-8">
    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Profile</p>
    <div class="space-y-1">
        <a href="{{ route('worker.profile') }}" class="sidebar-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            My Profile
        </a>
        <a href="{{ route('worker.profile.badges') }}" class="sidebar-link">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            Badges
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- Welcome Header -->
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome back, {{ auth()->user()->name }}!</h2>
            <p class="text-gray-500">Ready to work? Here's your dashboard overview.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('shifts.index') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Browse Shifts
            </a>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Shifts Today -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1" data-stat="shifts_today">{{ $stats['shifts_today'] }}</div>
        <div class="text-sm text-gray-500">Shifts Today</div>
    </div>

    <!-- Shifts This Week -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1" data-stat="shifts_this_week">{{ $stats['shifts_this_week'] }}</div>
        <div class="text-sm text-gray-500">This Week</div>
    </div>

    <!-- Earnings This Month -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1" data-stat="earnings_this_month">${{ number_format($stats['earnings_this_month'] ?? 0, 0) }}</div>
        <div class="text-sm text-gray-500">Earned This Month</div>
    </div>

    <!-- Rating -->
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1" data-stat="rating">{{ number_format($stats['rating'], 1) }}</div>
        <div class="text-sm text-gray-500">Your Rating</div>
    </div>
</div>

<!-- Next Shift Alert -->
@if($nextShift)
    @php
        $shiftStart = \Carbon\Carbon::parse($nextShift->shift->shift_date . ' ' . $nextShift->shift->start_time);
        $hoursUntil = now()->diffInHours($shiftStart, false);
    @endphp

    @if($hoursUntil > 0 && $hoursUntil < 24)
    <div class="mb-8 bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 text-white shadow-xl shadow-primary-500/25">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-1">Your Next Shift</h3>
                    <p class="text-2xl font-bold mb-2">{{ $nextShift->shift->title }}</p>
                    <div class="flex flex-wrap gap-4 text-sm text-white/90">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                            </svg>
                            {{ $nextShift->shift->business->name ?? 'Business' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $nextShift->shift->location_city }}, {{ $nextShift->shift->location_state }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $shiftStart->format('l, M d @ g:i A') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex items-center gap-6">
                <div class="text-center">
                    <div class="text-5xl font-black">{{ $hoursUntil }}h</div>
                    <div class="text-sm text-white/80">Until start</div>
                </div>
                <a href="{{ route('worker.assignments.show', $nextShift->id) }}" class="px-6 py-3 bg-white text-primary-700 font-bold rounded-xl hover:bg-gray-100 transition-colors">
                    View Details
                </a>
            </div>
        </div>
    </div>
    @endif
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Today's Shifts -->
        @if($todayShifts->count() > 0)
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Today's Shifts
                </h3>
            </div>
            <div class="card-body space-y-3">
                @foreach($todayShifts as $assignment)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border-l-4 border-primary-500 hover:bg-gray-100 transition-colors">
                    <div>
                        <h4 class="font-bold text-gray-900">{{ $assignment->shift->title }}</h4>
                        <p class="text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} -
                            {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                        </p>
                    </div>
                    @if($assignment->status == 'assigned')
                        <a href="{{ route('worker.assignments.checkIn', $assignment->id) }}" class="px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors">
                            Check In
                        </a>
                    @elseif($assignment->status == 'checked_in')
                        <span class="px-3 py-1.5 bg-green-100 text-green-700 text-sm font-semibold rounded-full">Checked In</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Recommended Shifts -->
        @if($recommendedShifts->count() > 0)
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Recommended For You
                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">AI-matched</span>
                </h3>
            </div>
            <div class="card-body space-y-4">
                @foreach($recommendedShifts as $shift)
                <div class="p-4 bg-gradient-to-br from-amber-50 to-white rounded-xl border-2 border-amber-200 hover:border-amber-300 transition-colors">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900 mb-1">{{ $shift->title }}</h4>
                            <span class="inline-block px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-full">
                                {{ $shift->match_score }}% MATCH
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xl font-bold text-green-600">${{ number_format($shift->final_rate, 2) }}</span>
                            <span class="text-sm text-gray-500">/hr</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-gray-600 mb-3">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            {{ $shift->location_city }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-green-600 font-semibold">
                            Est. earnings: ${{ number_format($shift->final_rate * $shift->duration_hours, 2) }}
                        </span>
                        <a href="{{ route('shifts.show', $shift->id) }}" class="btn-primary text-sm py-2 px-4">
                            View & Apply
                        </a>
                    </div>
                </div>
                @endforeach
                <div class="text-center pt-2">
                    <a href="{{ route('shifts.index') }}" class="btn-secondary">
                        Browse All Shifts
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Pending Applications -->
        @if($pendingApplications->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pending Applications
                </h3>
            </div>
            <div class="card-body space-y-3">
                @foreach($pendingApplications as $application)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border-l-4 border-amber-400 hover:bg-gray-100 transition-colors">
                    <div>
                        <h4 class="font-bold text-gray-900">{{ $application->shift->title }}</h4>
                        <p class="text-sm text-gray-500">Applied {{ $application->created_at->diffForHumans() }}</p>
                    </div>
                    <a href="{{ route('shifts.show', $application->shift_id) }}" class="btn-secondary text-sm py-2 px-4">
                        View Shift
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-gray-900">Quick Actions</h3>
            </div>
            <div class="card-body space-y-2">
                <a href="{{ route('shifts.index') }}" class="btn-primary w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Browse Shifts
                </a>
                <a href="{{ route('worker.calendar') }}" class="btn-secondary w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    My Calendar
                </a>
                <a href="{{ route('worker.assignments') }}" class="btn-secondary w-full justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    My Assignments
                </a>
            </div>
        </div>

        <!-- Recent Badges -->
        @if($recentBadges->count() > 0)
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-bold text-gray-900">Recent Achievements</h3>
            </div>
            <div class="card-body space-y-4">
                @foreach($recentBadges as $badge)
                <div class="text-center p-4 bg-gradient-to-br from-primary-50 to-white rounded-xl border border-primary-100">
                    <div class="text-4xl mb-2">{{ $badge->icon }}</div>
                    <h4 class="font-bold text-gray-900">{{ $badge->badge_name }}</h4>
                    <p class="text-xs text-gray-500 mt-1">{{ $badge->description }}</p>
                    <p class="text-xs text-primary-600 mt-2 font-medium">Earned {{ $badge->earned_at->diffForHumans() }}</p>
                </div>
                @endforeach
                <a href="{{ route('worker.profile.badges') }}" class="btn-secondary w-full justify-center text-sm">
                    View All Badges
                </a>
            </div>
        </div>
        @endif

        <!-- Performance Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-gray-900">Performance</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600">Rating</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['rating'], 1) }}/5.0</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full" style="width: {{ ($stats['rating'] / 5) * 100 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600">Reliability</span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($stats['reliability_score'] * 100, 0) }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full" style="width: {{ $stats['reliability_score'] * 100 }}%"></div>
                    </div>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Completed Shifts</span>
                        <span class="text-sm font-bold text-gray-900">{{ $stats['total_completed'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold text-gray-900">Earnings</h3>
            </div>
            <div class="card-body space-y-4">
                <div class="p-4 bg-gradient-to-br from-green-50 to-white rounded-xl border border-green-100">
                    <p class="text-sm text-gray-500 mb-1">This Week</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($stats['earnings_this_week'], 2) }}</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-50 to-white rounded-xl border border-green-100">
                    <p class="text-sm text-gray-500 mb-1">This Month</p>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($stats['earnings_this_month'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
