@extends('layouts.authenticated')

@section('title', 'Review Applications')
@section('page-title', 'Review Applications')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    @if($shift)
    <!-- Back Button & Shift Info -->
    <div>
        <a href="{{ route('business.shifts.show', $shift->id) }}" class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Shift Details
        </a>
        <div class="mt-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ $shift->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, M j, Y') }} •
                {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
            </p>
        </div>
    </div>
    @else
    <!-- All Applications Header -->
    <div>
        <h2 class="text-2xl font-bold text-gray-900">All Applications</h2>
        <p class="text-sm text-gray-500 mt-1">Review applications from workers for your shifts</p>
    </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Total Applications</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] ?? $applications->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Pending Review</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] ?? $applications->where('status', 'pending')->count() }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['approved'] ?? ($shift ? $shift->assignments->count() : 0) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-600">Rejected</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $stats['rejected'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Applications List -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Applications</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($applications as $application)
            <div class="p-6 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-start">
                            <!-- Worker Avatar -->
                            <div class="w-16 h-16 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center text-xl font-bold text-gray-600 overflow-hidden">
                                @if($application->worker->avatar)
                                    <img src="{{ $application->worker->avatar }}" alt="{{ $application->worker->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr($application->worker->name, 0, 1)) }}
                                @endif
                            </div>

                            <!-- Worker Info -->
                            <div class="ml-4 flex-1">
                                <div class="flex items-center">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $application->worker->name }}</h4>
                                    @if($application->worker->workerProfile && $application->worker->workerProfile->reliability_score >= 90)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Top Rated
                                        </span>
                                    @endif
                                </div>

                                <!-- Worker Stats -->
                                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $application->worker->completed_shifts_count ?? 0 }} shifts completed
                                    </span>
                                    @if($application->worker->workerProfile)
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ $application->worker->workerProfile->reliability_score ?? 0 }}% reliability
                                    </span>
                                    @endif
                                </div>

                                <!-- Bio -->
                                @if($application->worker->workerProfile && $application->worker->workerProfile->bio)
                                <p class="text-sm text-gray-600 mt-3">{{ Str::limit($application->worker->workerProfile->bio, 150) }}</p>
                                @endif

                                <!-- Skills/Experience -->
                                @if($application->worker->workerProfile && $application->worker->workerProfile->skills)
                                <div class="flex flex-wrap gap-2 mt-3">
                                    @foreach(explode(',', $application->worker->workerProfile->skills) as $skill)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ trim($skill) }}
                                        </span>
                                    @endforeach
                                </div>
                                @endif

                                <!-- Badges -->
                                @if($application->worker->badges && $application->worker->badges->count() > 0)
                                <div class="flex flex-wrap gap-2 mt-3">
                                    @foreach($application->worker->badges->take(3) as $badge)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $badge->badge_name }}
                                        </span>
                                    @endforeach
                                </div>
                                @endif

                                <!-- Application Date -->
                                <p class="text-xs text-gray-400 mt-3">
                                    Applied {{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="ml-6 flex flex-col space-y-2">
                        @if($application->status === 'pending')
                            @php
                                $canAccept = !$shift || ($shift && $shift->assignments->count() < $shift->workers_needed);
                            @endphp
                            @if($canAccept)
                            <form action="{{ route('business.shifts.assignWorker', $application->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm">
                                    Accept & Assign
                                </button>
                            </form>
                            @else
                            <div class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-center text-sm">
                                All positions filled
                            </div>
                            @endif
                            <form action="{{ route('business.applications.reject', $application->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium text-sm">
                                    Decline
                                </button>
                            </form>
                        @elseif($application->status === 'accepted')
                            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg text-center text-sm font-medium">
                                Assigned
                            </span>
                            <form action="{{ route('business.shifts.unassignWorker', $application->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-white border border-red-300 text-red-700 rounded-lg hover:bg-red-50 font-medium text-sm">
                                    Unassign
                                </button>
                            </form>
                        @elseif($application->status === 'rejected')
                            <span class="px-4 py-2 bg-red-100 text-red-800 rounded-lg text-center text-sm font-medium">
                                Declined
                            </span>
                        @endif

                        <a href="{{ route('messages.worker', $application->worker_id) }}" class="block text-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                            Message
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No applications yet</h3>
                <p class="mt-2 text-sm text-gray-500">Workers will see your shift once it's posted.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
