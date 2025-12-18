@extends('layouts.dashboard')

@section('title', 'Roster Membership')
@section('page-title', 'Roster Membership')
@section('page-subtitle', '{{ $member->roster->business->name ?? "Business" }} - {{ $member->roster->name ?? "Roster" }}')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('worker.roster-memberships.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Memberships
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Business Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16 rounded-lg bg-gray-100 flex items-center justify-center">
                    @if($member->roster->business->avatar ?? false)
                    <img src="{{ $member->roster->business->avatar }}" alt="{{ $member->roster->business->name }}" class="h-16 w-16 rounded-lg object-cover">
                    @else
                    <span class="text-2xl font-medium text-gray-600">
                        {{ substr($member->roster->business->name ?? 'B', 0, 1) }}
                    </span>
                    @endif
                </div>
                <div class="ml-5">
                    <h2 class="text-xl font-bold text-gray-900">{{ $member->roster->business->name ?? 'Unknown Business' }}</h2>
                    <div class="flex items-center mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $member->roster->type_badge_color ?? 'gray' }}-100 text-{{ $member->roster->type_badge_color ?? 'gray' }}-800">
                            {{ $member->roster->type_display ?? 'Roster' }}
                        </span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $member->status_badge_color }}-100 text-{{ $member->status_badge_color }}-800">
                            {{ $member->status_display }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Membership Stats -->
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Your Stats</h3>
            <div class="grid grid-cols-3 gap-6 text-center">
                <div>
                    <p class="text-3xl font-bold text-gray-900">{{ $member->total_shifts }}</p>
                    <p class="text-sm text-gray-500">Shifts Completed</p>
                </div>
                <div>
                    <p class="text-lg font-semibold text-gray-900">{{ $member->priority }}</p>
                    <p class="text-sm text-gray-500">Priority Score</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">
                        @if($member->last_worked_at)
                        {{ $member->last_worked_at->format('M j, Y') }}
                        @else
                        Never
                        @endif
                    </p>
                    <p class="text-sm text-gray-500">Last Worked</p>
                </div>
            </div>
        </div>

        <!-- Custom Rate -->
        @if($member->custom_rate)
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Custom Rate</h3>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-lg font-semibold text-gray-900">${{ number_format($member->custom_rate, 2) }}/hr</span>
            </div>
            <p class="mt-1 text-sm text-gray-500">This business has set a custom hourly rate for you on their shifts.</p>
        </div>
        @endif

        <!-- Membership Details -->
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Membership Details</h3>

            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Roster</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $member->roster->name ?? 'Unknown' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Type</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $member->roster->type_display ?? 'Regular' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Added by</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $member->addedByUser->name ?? 'Business Owner' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Member since</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $member->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Recent Shifts with Business -->
        @if($recentShifts->count() > 0)
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Recent Shifts</h3>
            <ul class="divide-y divide-gray-200">
                @foreach($recentShifts as $shift)
                <li class="py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $shift->title ?? 'Shift' }}</p>
                        <p class="text-xs text-gray-500">{{ $shift->shift_date ? $shift->shift_date->format('M j, Y') : 'Unknown date' }}</p>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        {{ ucfirst($shift->pivot->status ?? 'completed') }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Actions -->
        <div class="p-6 bg-gray-50 flex items-center justify-end">
            <form action="{{ route('worker.roster-memberships.leave', $member) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 border border-red-300 rounded-lg hover:bg-red-50" onclick="return confirm('Are you sure you want to leave this roster? You may lose your priority status and custom rate.')">
                    Leave Roster
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
