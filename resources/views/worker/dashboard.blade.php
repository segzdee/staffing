@extends('layouts.authenticated')

@section('title', 'Worker Dashboard')
@section('page-title', 'My Dashboard')

@section('sidebar-nav')
<a href="{{ route('worker.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Shifts</span>
</a>
<a href="{{ route('worker.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-gradient-to-r from-brand-500 to-brand-600 rounded-xl p-6 text-white">
        <h2 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h2>
        <p class="text-brand-100">Ready to work? Browse available shifts or check your upcoming assignments.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-600">This Week</h3>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $weekStats['scheduled'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 mt-2">Scheduled shifts</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-600">Upcoming</h3>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $upcomingShifts->count() }}</p>
            <p class="text-sm text-gray-500 mt-2">Next 7 days</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-600">Completed</h3>
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">{{ $shiftsCompleted }}</p>
            <p class="text-sm text-gray-500 mt-2">Total shifts</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-600">Total Earned</h3>
                <div class="p-2 bg-brand-100 rounded-lg">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($totalEarnings ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 mt-2">All time</p>
        </div>
    </div>

    <!-- Live Shift Market -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Live Shift Market</h3>
                <p class="text-sm text-gray-500">Real-time shifts with instant claim opportunities</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
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
                <a href="{{ route('worker.assignments') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">View all →</a>
            </div>
            <div class="p-6 space-y-4">
                @forelse($upcomingShifts as $assignment)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-brand-100 rounded-lg">
                            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $assignment->shift->title }}</h4>
                            <p class="text-sm text-gray-500">{{ $assignment->shift->business->name }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M j, Y') }} •
                                {{ \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:i A') }} -
                                {{ \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">${{ number_format($assignment->shift->final_rate, 2) }}/hr</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                            {{ ucfirst($assignment->status) }}
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
                        <a href="{{ route('shifts.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700">
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
            @if($profileCompleteness < 100)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Complete Your Profile</h3>
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">{{ $profileCompleteness }}% Complete</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-brand-600 h-2 rounded-full" style="width: {{ $profileCompleteness }}%"></div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Complete your profile to increase your chances of getting hired.</p>
                <a href="{{ route('worker.profile') }}" class="block text-center px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium">
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
                        <span class="text-sm font-semibold text-gray-900">{{ $recentApplications->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            @if($recentApplications->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Applications</h3>
                    <a href="{{ route('worker.applications') }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium">View all →</a>
                </div>
                <div class="space-y-3">
                    @foreach($recentApplications->take(3) as $application)
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $application->shift->title }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}</p>
                        </div>
                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $application->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $application->status === 'accepted' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $application->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($application->status) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
