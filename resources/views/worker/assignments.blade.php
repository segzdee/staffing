@extends('layouts.authenticated')

@section('title', 'My Shifts')
@section('page-title', 'My Shifts')

@section('sidebar-nav')
<a href="{{ route('worker.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
<a href="{{ route('worker.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Shifts</h2>
            <p class="text-sm text-gray-500 mt-1">View and manage your assigned shifts</p>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6">
            <div class="space-y-4">
                @forelse($assignments ?? [] as $assignment)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $assignment->shift->title }}</h3>
                            <p class="text-sm text-gray-600">{{ $assignment->shift->business->name }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $assignment->shift->shift_date }} •
                                {{ $assignment->shift->start_time }} - {{ $assignment->shift->end_time }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                {{ ucfirst($assignment->status) }}
                            </span>
                            <p class="text-lg font-bold text-gray-900 mt-2">
                                ${{ number_format($assignment->shift->final_rate, 2) }}/hr
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No shifts assigned yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Start applying to shifts to see them here.</p>
                    <a href="{{ route('shifts.index') }}" class="mt-6 inline-block px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                        Browse Available Shifts
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
