@extends('layouts.authenticated')

@section('title', 'My Shifts')
@section('page-title', 'My Shifts')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('shifts.create') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Post Shift</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Shifts</h2>
            <p class="text-sm text-gray-500 mt-1">Manage your posted shifts and applications</p>
        </div>
        <a href="{{ route('shifts.create') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Post New Shift
        </a>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Shifts</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $shifts->count() }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Open Shifts</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $shifts->where('status', 'open')->count() }}</p>
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
                    <p class="text-sm text-gray-600">In Progress</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $shifts->where('status', 'in_progress')->count() }}</p>
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
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $shifts->where('status', 'completed')->count() }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
                    All Shifts
                </a>
                <a href="?status=open" class="px-6 py-3 border-b-2 {{ request('status') === 'open' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Open
                </a>
                <a href="?status=filled" class="px-6 py-3 border-b-2 {{ request('status') === 'filled' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Filled
                </a>
                <a href="?status=in_progress" class="px-6 py-3 border-b-2 {{ request('status') === 'in_progress' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    In Progress
                </a>
                <a href="?status=completed" class="px-6 py-3 border-b-2 {{ request('status') === 'completed' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }} font-medium text-sm">
                    Completed
                </a>
            </nav>
        </div>

        <!-- Shifts List -->
        <div class="p-6">
            <div class="space-y-4">
                @forelse($shifts ?? [] as $shift)
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
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $shift->title }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ ucfirst($shift->industry) }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($shift->shift_date)->format('M j, Y') }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                        </span>
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            {{ $shift->assignments->count() }}/{{ $shift->workers_needed }} filled
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2 mt-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $shift->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                                            {{ $shift->status === 'open' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $shift->status === 'filled' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $shift->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $shift->status === 'completed' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $shift->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst(str_replace('_', ' ', $shift->status)) }}
                                        </span>
                                        @if($shift->applications_count > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800">
                                            {{ $shift->applications_count }} applications
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-2xl font-bold text-gray-900 mb-2">@money($shift->final_rate)/hr</p>
                            <div class="space-y-2">
                                <a href="{{ route('business.shifts.show', $shift->id) }}" class="block text-center px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                    View Details
                                </a>
                                @if($shift->status === 'open' || $shift->status === 'draft')
                                <a href="{{ route('business.shifts.edit', $shift->id) }}" class="block text-center px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                                    Edit
                                </a>
                                @endif
                                @if($shift->applications_count > 0)
                                <a href="{{ route('business.shifts.applications', $shift->id) }}" class="block text-center px-4 py-2 text-sm bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                                    Review Applications
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No shifts posted yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Get started by posting your first shift.</p>
                    <a href="{{ route('shifts.create') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Post Your First Shift
                    </a>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if(isset($shifts) && method_exists($shifts, 'hasPages') && $shifts->hasPages())
            <div class="mt-6">
                {{ $shifts->appends(['status' => $status ?? 'all'])->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
