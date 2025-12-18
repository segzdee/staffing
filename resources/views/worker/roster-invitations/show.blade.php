@extends('layouts.dashboard')

@section('title', 'Roster Invitation')
@section('page-title', 'Roster Invitation')
@section('page-subtitle', 'From {{ $invitation->roster->business->name }}')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('worker.roster-invitations.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Invitations
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <!-- Business Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16 rounded-lg bg-gray-100 flex items-center justify-center">
                    @if($invitation->roster->business->avatar)
                    <img src="{{ $invitation->roster->business->avatar }}" alt="{{ $invitation->roster->business->name }}" class="h-16 w-16 rounded-lg object-cover">
                    @else
                    <span class="text-2xl font-medium text-gray-600">
                        {{ substr($invitation->roster->business->name, 0, 1) }}
                    </span>
                    @endif
                </div>
                <div class="ml-5">
                    <h2 class="text-xl font-bold text-gray-900">{{ $invitation->roster->business->name }}</h2>
                    @if($invitation->roster->business->businessProfile)
                    <p class="text-sm text-gray-500">{{ $invitation->roster->business->businessProfile->industry ?? 'Business' }}</p>
                    @endif
                    @if($businessStats['rating'] > 0)
                    <div class="flex items-center mt-1">
                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="ml-1 text-sm text-gray-600">{{ number_format($businessStats['rating'], 1) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Roster Details -->
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Roster Details</h3>

            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-lg font-semibold text-gray-900">{{ $invitation->roster->name }}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 mt-1 rounded-full text-xs font-medium bg-{{ $invitation->roster->type_badge_color }}-100 text-{{ $invitation->roster->type_badge_color }}-800">
                        {{ $invitation->roster->type_display }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Current Members</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $businessStats['active_roster_members'] }}</p>
                </div>
            </div>

            @if($invitation->roster->description)
            <p class="text-sm text-gray-600">{{ $invitation->roster->description }}</p>
            @endif

            <div class="mt-4 bg-gray-50 rounded-lg p-4">
                <p class="text-sm font-medium text-gray-700 mb-2">What this means for you:</p>
                <ul class="text-sm text-gray-600 space-y-1">
                    @if($invitation->roster->type === 'preferred')
                    <li>- You'll be first in line for shifts from this business</li>
                    <li>- You may receive early notifications about new shifts</li>
                    <li>- Higher priority in shift matching</li>
                    @elseif($invitation->roster->type === 'regular')
                    <li>- You'll be part of their trusted worker pool</li>
                    <li>- Priority access to shifts over non-roster workers</li>
                    @elseif($invitation->roster->type === 'backup')
                    <li>- You'll be contacted when regular workers are unavailable</li>
                    <li>- Great for picking up extra shifts</li>
                    @endif
                </ul>
            </div>
        </div>

        <!-- Invitation Message -->
        @if($invitation->message)
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Message</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 italic">"{{ $invitation->message }}"</p>
                <p class="text-xs text-gray-500 mt-2">- {{ $invitation->invitedByUser->name ?? 'Business Owner' }}</p>
            </div>
        </div>
        @endif

        <!-- Invitation Status -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Invitation Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 mt-1 rounded-full text-sm font-medium bg-{{ $invitation->status_badge_color }}-100 text-{{ $invitation->status_badge_color }}-800">
                        {{ $invitation->status_display }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Expires</p>
                    <p class="text-sm font-medium {{ $invitation->isExpired() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $invitation->expires_at->format('M j, Y \a\t g:i A') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        @if($invitation->canRespond())
        <div class="p-6 bg-gray-50 flex items-center justify-end space-x-4">
            <form action="{{ route('worker.roster-invitations.decline', $invitation) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-6 py-3 text-sm font-medium text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-100">
                    Decline Invitation
                </button>
            </form>
            <form action="{{ route('worker.roster-invitations.accept', $invitation) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-6 py-3 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Accept Invitation
                </button>
            </form>
        </div>
        @else
        <div class="p-6 bg-gray-50 text-center">
            <p class="text-sm text-gray-500">This invitation {{ $invitation->isExpired() ? 'has expired' : 'has already been responded to' }}.</p>
        </div>
        @endif
    </div>
</div>
@endsection
