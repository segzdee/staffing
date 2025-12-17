@extends('layouts.authenticated')

@section('title', 'Browse Shifts')
@section('page-title', 'Browse Shifts')

@section('sidebar-nav')
    <x-dashboard.sidebar-nav />
@endsection

@section('content')
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Available Shifts</h2>
                <p class="text-sm text-gray-500 mt-1">Find and apply to shifts matching your skills</p>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="document.getElementById('filterPanel').classList.toggle('hidden')"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filters</span>
                </button>
            </div>
        </div>

        <div id="filterPanel" class="hidden bg-white border border-gray-200 rounded-lg p-4">
            <form method="GET" action="{{ route('shifts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                    <input type="text" name="location" placeholder="City or ZIP"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                    <input type="date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                    <select name="industry" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Industries</option>
                        <option value="hospitality">Hospitality</option>
                        <option value="retail">Retail</option>
                        <option value="warehouse">Warehouse</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Apply
                        Filters</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($shifts as $shift)
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $shift->title }}</h3>
                            <p class="text-sm text-gray-600 mt-1">{{ $shift->business->name ?? 'Business Name' }}</p>
                            <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $shift->location_city }}, {{ $shift->location_state }}
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ \Carbon\Carbon::parse($shift->shift_date)->format('M d, Y') }},
                                    {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} -
                                    {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 mt-3">
                                <span
                                    class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">{{ ucfirst($shift->industry) }}</span>
                                <span
                                    class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">{{ $shift->duration_hours }}
                                    hours</span>
                                @if($shift->urgency_level === 'urgent')
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded">Urgent</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($shift->final_rate, 2) }}/hr</p>
                            <a href="{{ route('shifts.show', $shift->id) }}"
                                class="mt-3 inline-block px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">View
                                Details</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border border-gray-200 rounded-lg">
                    <x-dashboard.empty-state icon="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" title="No shifts found"
                        description="Try adjusting your filters or check back later." />
                </div>
            @endforelse

            <!-- Pagination -->
            <div class="mt-4">
                {{ $shifts->links() }}
            </div>
        </div>
    </div>
@endsection