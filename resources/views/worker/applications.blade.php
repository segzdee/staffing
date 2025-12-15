@extends('layouts.authenticated')

@section('title', 'My Applications')
@section('page-title', 'My Applications')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
<a href="{{ route('worker.applications') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <span>Applications</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Applications</h2>
            <p class="text-sm text-gray-500 mt-1">Track the status of your shift applications</p>
        </div>
        <a href="{{ route('shifts.index') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
            Browse More Shifts
        </a>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Applications</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ ($applications ?? collect())->count() }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ ($applications ?? collect())->where('status', 'pending')->count() }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Accepted</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ ($applications ?? collect())->where('status', 'accepted')->count() }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ ($applications ?? collect())->where('status', 'rejected')->count() }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <a href="?status=all" class="px-6 py-3 border-b-2 {{ request('status', 'all') === 'all' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    All Applications
                </a>
                <a href="?status=pending" class="px-6 py-3 border-b-2 {{ request('status') === 'pending' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Pending
                </a>
                <a href="?status=accepted" class="px-6 py-3 border-b-2 {{ request('status') === 'accepted' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Accepted
                </a>
                <a href="?status=rejected" class="px-6 py-3 border-b-2 {{ request('status') === 'rejected' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Rejected
                </a>
            </nav>
        </div>

        <!-- Applications List -->
        <div class="p-6">
            <div class="space-y-4">
                @forelse($applications ?? [] as $application)
                <div class="border border-gray-200 rounded-lg p-5 hover:border-brand-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-start">
                                <div class="p-3 bg-brand-100 rounded-lg mr-4">
                                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $application->shift->title ?? 'Untitled Shift' }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $application->shift->business->name ?? 'Unknown Business' }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ $application->shift?->shift_date ? \Carbon\Carbon::parse($application->shift->shift_date)->format('M j, Y') : 'Date TBD' }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $application->shift?->start_time ? \Carbon\Carbon::parse($application->shift->start_time)->format('g:i A') : '--:--' }} - {{ $application->shift?->end_time ? \Carbon\Carbon::parse($application->shift->end_time)->format('g:i A') : '--:--' }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            </svg>
                                            {{ $application->shift->location_city ?? 'City' }}, {{ $application->shift->location_state ?? 'State' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2">
                                        Applied {{ \Carbon\Carbon::parse($application->created_at)->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-2xl font-bold text-gray-900 mb-2">${{ number_format($application->shift->final_rate ?? 0, 2) }}/hr</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $application->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $application->status === 'accepted' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $application->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $application->status === 'withdrawn' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($application->status) }}
                            </span>
                            <div class="mt-3 space-y-2">
                                <a href="{{ route('shifts.show', $application->shift->id) }}" class="block text-center px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                    View Shift
                                </a>
                                @if($application->status === 'pending')
                                <form action="{{ route('worker.applications.withdraw', $application->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full px-4 py-2 text-sm bg-red-50 text-red-700 rounded-lg hover:bg-red-100">
                                        Withdraw
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No applications yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Start applying to shifts to see them here.</p>
                    <a href="{{ route('shifts.index') }}" class="mt-6 inline-block px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Browse Available Shifts
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
