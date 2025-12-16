@extends('layouts.authenticated')

@section('title', 'Manage Shift Swaps')
@section('page-title', 'Manage Shift Swaps')

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
<a href="{{ route('business.swaps.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
    </svg>
    <span>Shift Swaps</span>
</a>
<a href="{{ route('business.templates.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
    </svg>
    <span>Templates</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Shift Swap Requests</h2>
            <p class="text-sm text-gray-500 mt-1">Review and approve shift swap requests from your workers</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Pending Approval</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Approved Today</p>
            <p class="text-2xl font-bold text-green-600">{{ $approvedToday ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Rejected</p>
            <p class="text-2xl font-bold text-red-600">{{ $rejectedCount ?? 0 }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-500">Total This Month</p>
            <p class="text-2xl font-bold text-gray-900">{{ $totalMonth ?? 0 }}</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <a href="?status=pending" class="py-4 px-1 border-b-2 border-brand-500 text-brand-600 font-medium text-sm">
                    Pending Approval
                </a>
                <a href="?status=approved" class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                    Approved
                </a>
                <a href="?status=rejected" class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                    Rejected
                </a>
                <a href="?status=all" class="py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-medium text-sm">
                    All
                </a>
            </nav>
        </div>

        <!-- Swap Requests List -->
        <div class="p-6">
            <div class="space-y-4">
                @forelse($swaps ?? [] as $swap)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="font-semibold text-gray-900">{{ $swap->shift->title ?? 'Shift Title' }}</h3>
                                @php
                                    $status = $swap->status ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'accepted' => 'bg-blue-100 text-blue-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $swap->shift->shift_date ?? 'Date' }} |
                                {{ $swap->shift->start_time ?? 'Start' }} - {{ $swap->shift->end_time ?? 'End' }}
                            </p>

                            <div class="mt-3 flex items-center text-sm">
                                <div class="flex items-center">
                                    <img src="{{ $swap->originalWorker->avatar ?? 'https://ui-avatars.com/api/?name=O' }}"
                                         alt="Original" class="w-6 h-6 rounded-full">
                                    <span class="ml-2 text-gray-700">{{ $swap->originalWorker->name ?? 'Original Worker' }}</span>
                                </div>
                                <svg class="w-5 h-5 mx-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                                <div class="flex items-center">
                                    <img src="{{ $swap->acceptingWorker->avatar ?? 'https://ui-avatars.com/api/?name=N' }}"
                                         alt="New" class="w-6 h-6 rounded-full">
                                    <span class="ml-2 text-gray-700">{{ $swap->acceptingWorker->name ?? 'New Worker' }}</span>
                                </div>
                            </div>

                            @if($swap->reason ?? false)
                            <p class="text-sm text-gray-500 mt-2 italic">"{{ $swap->reason }}"</p>
                            @endif
                        </div>

                        @if(($swap->status ?? 'pending') === 'accepted')
                        <div class="flex space-x-2">
                            <form action="{{ route('business.swaps.approve', $swap->id ?? 0) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                    Approve
                                </button>
                            </form>
                            <form action="{{ route('business.swaps.reject', $swap->id ?? 0) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                    Reject
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No swap requests</h3>
                    <p class="mt-2 text-sm text-gray-500">Workers haven't submitted any shift swap requests yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if(isset($swaps) && method_exists($swaps, 'hasPages') && $swaps->hasPages())
    <div class="mt-6">
        {{ $swaps->links() }}
    </div>
    @endif
</div>
@endsection
