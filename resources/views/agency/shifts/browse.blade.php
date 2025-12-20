@extends('layouts.authenticated')

@section('title', 'Browse Shifts')
@section('page-title', 'Browse Shifts')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.shifts.browse') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Shifts</span>
</a>
<a href="{{ route('agency.workers.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>My Workers</span>
</a>
<a href="{{ route('agency.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>Assignments</span>
</a>
<a href="{{ route('agency.commissions') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Commissions</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Shift Marketplace</h2>
            <p class="text-sm text-gray-500 mt-1">Browse available shifts and assign your workers</p>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <form action="{{ route('agency.shifts.browse') }}" method="GET" class="grid md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500"
                       placeholder="Search shifts, businesses...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                <select name="industry" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="">All Industries</option>
                    <option value="retail" {{ request('industry') === 'retail' ? 'selected' : '' }}>Retail</option>
                    <option value="hospitality" {{ request('industry') === 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                    <option value="warehouse" {{ request('industry') === 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                    <option value="healthcare" {{ request('industry') === 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                    <option value="events" {{ request('industry') === 'events' ? 'selected' : '' }}>Events</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Available Workers Quick Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-800">
                    You have <strong>{{ $availableWorkers ?? 0 }}</strong> workers available for assignment
                </span>
            </div>
            <a href="{{ route('agency.workers.index') }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                Manage Workers
            </a>
        </div>
    </div>

    <!-- Shifts Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($shifts ?? [] as $shift)
        <div class="bg-white border border-gray-200 rounded-xl p-4 hover:border-brand-300 transition-colors">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $shift->title ?? 'Shift Title' }}</h3>
                    <p class="text-sm text-gray-600">{{ $shift->business->name ?? 'Business Name' }}</p>
                </div>
                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                    {{ $shift->workers_needed ?? 1 }} needed
                </span>
            </div>

            <div class="space-y-2 text-sm text-gray-600 mb-4">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ $shift->shift_date ?? 'Date TBD' }}
                </div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $shift->start_time ?? '00:00' }} - {{ $shift->end_time ?? '00:00' }}
                </div>
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $shift->location ?? 'Location TBD' }}
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                <div>
                    <p class="text-lg font-bold text-green-600">@money($shift->final_rate)/hr</p>
                    @if($shift->commission_rate ?? false)
                    <p class="text-xs text-gray-500">{{ $shift->commission_rate }}% commission</p>
                    @endif
                </div>
                <a href="{{ route('agency.shifts.view', $shift->id ?? 0) }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium">
                    View & Assign
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 bg-white border border-gray-200 rounded-xl">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No shifts found</h3>
            <p class="mt-2 text-sm text-gray-500">Try adjusting your search filters or check back later for new opportunities.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($shifts) && method_exists($shifts, 'hasPages') && $shifts->hasPages())
    <div class="mt-6">
        {{ $shifts->links() }}
    </div>
    @endif
</div>
@endsection
