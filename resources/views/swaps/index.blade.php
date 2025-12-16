@extends('layouts.authenticated')

@section('title', 'Browse Shift Swaps')
@section('page-title', 'Browse Shift Swaps')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
<a href="#" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
    </svg>
    <span>Shift Swaps</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Available Shift Swaps</h2>
            <p class="text-sm text-gray-500 mt-1">Browse and accept available shift swap offers from other workers</p>
        </div>
        @if(auth()->user()->user_type === 'worker')
        <a href="{{ route('worker.swaps.my') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
            My Swap Offers
        </a>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                <input type="text" placeholder="City or ZIP" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500">
                    <option value="">All Industries</option>
                    <option value="retail">Retail</option>
                    <option value="hospitality">Hospitality</option>
                    <option value="warehouse">Warehouse</option>
                    <option value="healthcare">Healthcare</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Swap Listings -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6">
            <div class="space-y-4">
                @forelse($swaps ?? [] as $swap)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-brand-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $swap->shift->title ?? 'Shift Title' }}</h3>
                            <p class="text-sm text-gray-600">{{ $swap->shift->business->name ?? 'Business Name' }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $swap->shift->shift_date ?? 'Date' }} |
                                {{ $swap->shift->start_time ?? 'Start' }} - {{ $swap->shift->end_time ?? 'End' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-2">
                                Offered by: {{ $swap->originalWorker->name ?? 'Worker Name' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">
                                ${{ number_format($swap->shift->final_rate ?? 0, 2) }}/hr
                            </p>
                            <a href="{{ route('worker.swaps.show', $swap->id ?? 0) }}" class="mt-2 inline-block px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No swap offers available</h3>
                    <p class="mt-2 text-sm text-gray-500">Check back later for available shift swaps in your area.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($swaps) && $swaps->hasPages())
    <div class="mt-6">
        {{ $swaps->links() }}
    </div>
    @endif
</div>
@endsection
