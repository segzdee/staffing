@extends('layouts.dashboard')

@section('title', 'My Roster Memberships')
@section('page-title', 'Roster Memberships')
@section('page-subtitle', 'Your business roster memberships')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Active Memberships -->
    @if($activeMemberships->count() > 0)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Active Memberships ({{ $activeMemberships->count() }})</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($activeMemberships as $member)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                @if($member->roster->business->avatar)
                                <img src="{{ $member->roster->business->avatar }}" alt="{{ $member->roster->business->name }}" class="h-12 w-12 rounded-lg object-cover">
                                @else
                                <span class="text-lg font-medium text-gray-600">
                                    {{ substr($member->roster->business->name ?? 'B', 0, 1) }}
                                </span>
                                @endif
                            </div>
                            <div class="ml-4">
                                <h3 class="text-base font-semibold text-gray-900">{{ $member->roster->business->name ?? 'Unknown Business' }}</h3>
                                <p class="text-sm text-gray-500">{{ $member->roster->name ?? 'Unknown Roster' }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $member->roster->type_badge_color ?? 'gray' }}-100 text-{{ $member->roster->type_badge_color ?? 'gray' }}-800">
                            {{ $member->roster->type_display ?? 'Roster' }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ $member->total_shifts }}</p>
                            <p class="text-xs text-gray-500">Shifts Completed</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                @if($member->last_worked_at)
                                {{ $member->last_worked_at->diffForHumans() }}
                                @else
                                Never
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">Last Worked</p>
                        </div>
                    </div>

                    @if($member->custom_rate)
                    <div class="mt-3 inline-flex items-center px-2 py-1 rounded bg-green-50 text-green-700 text-xs">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Custom Rate: ${{ number_format($member->custom_rate, 2) }}/hr
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 px-5 py-3 flex items-center justify-between">
                    <a href="{{ route('worker.roster-memberships.show', $member) }}" class="text-sm text-gray-600 hover:text-gray-900">
                        View Details
                    </a>
                    <form action="{{ route('worker.roster-memberships.leave', $member) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to leave this roster?')">
                            Leave Roster
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Pending Memberships -->
    @if($pendingMemberships->count() > 0)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pending ({{ $pendingMemberships->count() }})</h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($pendingMemberships as $member)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">
                                {{ substr($member->roster->business->name ?? 'B', 0, 1) }}
                            </span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $member->roster->business->name ?? 'Unknown Business' }}</p>
                            <p class="text-sm text-gray-500">{{ $member->roster->name ?? 'Unknown Roster' }}</p>
                        </div>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Pending Approval
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Inactive Memberships -->
    @if($inactiveMemberships->count() > 0)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Inactive ({{ $inactiveMemberships->count() }})</h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($inactiveMemberships as $member)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">
                                {{ substr($member->roster->business->name ?? 'B', 0, 1) }}
                            </span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $member->roster->business->name ?? 'Unknown Business' }}</p>
                            <p class="text-sm text-gray-500">{{ $member->roster->name ?? 'Unknown Roster' }}</p>
                        </div>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        Inactive
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Empty State -->
    @if($activeMemberships->count() === 0 && $pendingMemberships->count() === 0 && $inactiveMemberships->count() === 0)
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No roster memberships</h3>
        <p class="mt-1 text-sm text-gray-500">You're not a member of any business rosters yet.</p>
        <p class="mt-4 text-sm text-gray-500">When businesses add you to their rosters, they'll appear here.</p>
        <div class="mt-6">
            <a href="{{ route('worker.roster-invitations.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Check Invitations
            </a>
        </div>
    </div>
    @endif

    <!-- Info Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">About Roster Memberships</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Being on a business's roster gives you priority access to their shifts. The higher your roster tier (Preferred > Regular > Backup), the sooner you'll be notified about new opportunities.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
