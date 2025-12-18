@extends('layouts.dashboard')

@section('title', 'Roster Management')
@section('page-title', 'Roster Management')
@section('page-subtitle', 'Manage your worker rosters and preferences')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-lg bg-green-100">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Preferred</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['by_type']['preferred']['active_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-lg bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Regular</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['by_type']['regular']['active_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-lg bg-yellow-100">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Backup</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['by_type']['backup']['active_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 rounded-lg bg-gray-100">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Pending Invites</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_invitations'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Worker Rosters</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $stats['total_rosters'] }} roster(s), {{ $stats['total_active_workers'] }} active workers
            </p>
        </div>
        <a href="{{ route('business.rosters.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Roster
        </a>
    </div>

    <!-- Rosters Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($rosters as $roster)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
            <div class="p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg
                            @if($roster->type === 'preferred') bg-green-100 text-green-600
                            @elseif($roster->type === 'regular') bg-blue-100 text-blue-600
                            @elseif($roster->type === 'backup') bg-yellow-100 text-yellow-600
                            @else bg-red-100 text-red-600
                            @endif">
                            @if($roster->type === 'preferred')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            @elseif($roster->type === 'blacklist')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            @endif
                        </span>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $roster->name }}</h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($roster->type === 'preferred') bg-green-100 text-green-800
                                @elseif($roster->type === 'regular') bg-blue-100 text-blue-800
                                @elseif($roster->type === 'backup') bg-yellow-100 text-yellow-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ $roster->type_display }}
                            </span>
                        </div>
                    </div>
                    @if($roster->is_default)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        Default
                    </span>
                    @endif
                </div>

                @if($roster->description)
                <p class="mt-3 text-sm text-gray-500 line-clamp-2">{{ $roster->description }}</p>
                @endif

                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $roster->active_members_count }}</p>
                        <p class="text-xs text-gray-500">Active</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $roster->members_count }}</p>
                        <p class="text-xs text-gray-500">Total</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $roster->pending_invitations_count }}</p>
                        <p class="text-xs text-gray-500">Pending</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-5 py-3 flex items-center justify-between">
                <a href="{{ route('business.rosters.show', $roster) }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    View Members
                </a>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('business.rosters.edit', $roster) }}" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('business.rosters.destroy', $roster) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600" onclick="return confirm('Are you sure you want to delete this roster? All members will be removed.')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No rosters yet</h3>
                <p class="mt-1 text-sm text-gray-500">Create rosters to organize and prioritize your workers.</p>
                <div class="mt-6">
                    <a href="{{ route('business.rosters.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create First Roster
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Info Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">About Roster Types</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Preferred:</strong> Top priority workers who get first access to your shifts</li>
                        <li><strong>Regular:</strong> Workers you've worked with before and trust</li>
                        <li><strong>Backup:</strong> Workers to contact when preferred/regular are unavailable</li>
                        <li><strong>Blacklist:</strong> Workers blocked from applying to your shifts</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
