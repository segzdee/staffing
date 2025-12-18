@extends('layouts.dashboard')

@section('title', 'Roster Invitations')
@section('page-title', 'Roster Invitations')
@section('page-subtitle', 'Manage invitations from businesses')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Pending Invitations -->
    @if($pendingInvitations->count() > 0)
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Pending Invitations</h2>
        <div class="space-y-4">
            @foreach($pendingInvitations as $invitation)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                @if($invitation->roster->business->avatar)
                                <img src="{{ $invitation->roster->business->avatar }}" alt="{{ $invitation->roster->business->name }}" class="h-12 w-12 rounded-lg object-cover">
                                @else
                                <span class="text-lg font-medium text-gray-600">
                                    {{ substr($invitation->roster->business->name, 0, 1) }}
                                </span>
                                @endif
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $invitation->roster->business->name }}</h3>
                                <p class="text-sm text-gray-500">
                                    Inviting you to join their <span class="font-medium">{{ $invitation->roster->name }}</span> roster
                                </p>
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-xs font-medium bg-{{ $invitation->roster->type_badge_color }}-100 text-{{ $invitation->roster->type_badge_color }}-800">
                                    {{ $invitation->roster->type_display }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-500">Expires {{ $invitation->expires_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    @if($invitation->message)
                    <div class="mt-4 bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-700 italic">"{{ $invitation->message }}"</p>
                        <p class="text-xs text-gray-500 mt-2">- {{ $invitation->invitedByUser->name ?? 'Business Owner' }}</p>
                    </div>
                    @endif
                </div>

                <div class="bg-gray-50 px-5 py-3 flex items-center justify-between">
                    <a href="{{ route('worker.roster-invitations.show', $invitation) }}" class="text-sm text-gray-600 hover:text-gray-900">
                        View Details
                    </a>
                    <div class="flex items-center space-x-3">
                        <form action="{{ route('worker.roster-invitations.decline', $invitation) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-100">
                                Decline
                            </button>
                        </form>
                        <form action="{{ route('worker.roster-invitations.accept', $invitation) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                                Accept
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center mb-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending invitations</h3>
        <p class="mt-1 text-sm text-gray-500">When businesses invite you to join their roster, invitations will appear here.</p>
    </div>
    @endif

    <!-- Historical Invitations -->
    @if($historicalInvitations->count() > 0)
    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Past Invitations</h2>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <ul class="divide-y divide-gray-200">
                @foreach($historicalInvitations as $invitation)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">
                                {{ substr($invitation->roster->business->name ?? 'B', 0, 1) }}
                            </span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">{{ $invitation->roster->business->name ?? 'Unknown Business' }}</p>
                            <p class="text-sm text-gray-500">{{ $invitation->roster->name ?? 'Unknown Roster' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $invitation->status_badge_color }}-100 text-{{ $invitation->status_badge_color }}-800">
                            {{ $invitation->status_display }}
                        </span>
                        <span class="ml-4 text-sm text-gray-500">{{ $invitation->updated_at->diffForHumans() }}</span>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Link to Memberships -->
    <div class="mt-8 text-center">
        <a href="{{ route('worker.roster-memberships.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">
            View your roster memberships &rarr;
        </a>
    </div>
</div>
@endsection
