@extends('layouts.dashboard')

@section('title', 'Business Dashboard')
@section('page-title', 'Welcome back, {{ auth()->user()->name }}!')
@section('page-subtitle', 'Manage your shifts and find qualified workers')

@section('content')

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming Shifts -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Upcoming Shifts</h3>
                <a href="{{ route('business.shifts.index') }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">View all</a>
            </div>
            <div class="p-6">
                @forelse($upcomingShifts ?? [] as $shift)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors mb-4 last:mb-0 gap-3">
                    <div class="flex items-center space-x-4 min-w-0">
                        <div class="p-3 bg-gray-100 rounded-lg flex-shrink-0">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-medium text-gray-900 truncate">{{ $shift->title }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($shift->shift_date)->format('M j, Y') }} |
                                {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $shift->filled_workers }}/{{ $shift->required_workers }} workers assigned
                            </p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right flex-shrink-0">
                        <p class="font-semibold text-gray-900">${{ number_format($shift->final_rate ?? 0, 2) }}/hr</p>
                        <a href="{{ route('business.shifts.show', $shift->id) }}" class="text-sm text-gray-600 hover:text-gray-900">View</a>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming shifts</h3>
                    <p class="mt-1 text-sm text-gray-500">Post a shift to start finding workers.</p>
                    <div class="mt-6">
                        <a href="{{ route('shifts.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                            Post a Shift
                        </a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <a href="{{ route('shifts.create') }}" class="flex items-center justify-center w-full px-4 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Post New Shift
                    </a>
                    <a href="{{ route('business.available-workers') }}" class="flex items-center justify-center w-full px-4 py-3 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Find Available Workers
                    </a>
                </div>
            </div>

            <!-- Recent Applications -->
            @if(($recentApplications ?? collect())->count() > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Applications</h3>
                </div>
                <div class="space-y-3">
                    @foreach(($recentApplications ?? collect())->take(5) as $application)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-600">{{ substr($application->worker->name ?? 'U', 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $application->worker->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $application->shift->title ?? 'Shift' }}</p>
                            </div>
                        </div>
                        <a href="{{ route('business.shifts.applications', $application->shift_id) }}" class="text-sm text-gray-600 hover:text-gray-900">Review</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Fill Rate -->
            @if(($averageFillRate ?? 0) > 0)
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Fill Rate</h3>
                <div class="mb-4">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">Average Fill Rate</span>
                        <span class="font-semibold text-gray-900">{{ $averageFillRate ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $averageFillRate ?? 0 }}%"></div>
                    </div>
                </div>
                <p class="text-sm text-gray-500">Based on last 30 days</p>
            </div>
            @endif
        </div>
    </div>

<!-- Shifts Needing Attention -->
@if(($shiftsNeedingAttention ?? collect())->count() > 0)
<div class="bg-white rounded-xl border border-gray-200 mt-6">
    <div class="p-6 border-b border-gray-200 flex items-center">
        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <h3 class="text-lg font-semibold text-gray-900">Shifts Needing Attention</h3>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($shiftsNeedingAttention as $shift)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 gap-3">
                <div class="min-w-0 flex-1">
                    <h4 class="font-medium text-gray-900 truncate">{{ $shift->title }}</h4>
                    <p class="text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($shift->shift_date)->format('M j, Y') }} |
                        Only {{ $shift->required_workers - $shift->filled_workers }} spots remaining
                    </p>
                </div>
                <a href="{{ route('business.shifts.show', $shift->id) }}" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 text-sm font-medium text-center flex-shrink-0">
                    View Applications
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
