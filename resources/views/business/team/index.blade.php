@extends('layouts.dashboard')

@section('title', 'Team Management')
@section('page-title', 'Team Management')
@section('page-subtitle', 'Manage your team members and their permissions')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Team Members</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $activeCount }} active, {{ $pendingCount }} pending invitations
            </p>
        </div>
        <a href="{{ route('business.team.create') }}" class="inline-flex items-center min-h-[40px] px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Invite Team Member
        </a>
    </div>

    <!-- Team Members Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Member
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Activity
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($teamMembers as $member)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-600">
                                        {{ $member->user ? substr($member->user->name, 0, 1) : 'P' }}
                                    </span>
                                </div>
                                <div class="ml-4 min-w-0 max-w-[200px]">
                                    <div class="text-sm font-medium text-gray-900 truncate" title="{{ $member->user->name ?? 'Pending' }}">
                                        {{ $member->user->name ?? 'Pending' }}
                                    </div>
                                    <div class="text-sm text-gray-500 truncate" title="{{ $member->user->email ?? 'Invitation sent' }}">
                                        {{ $member->user->email ?? 'Invitation sent' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $member->role_name }}</div>
                            @if($member->venue_access && count($member->venue_access) > 0)
                            <div class="text-xs text-gray-500">
                                {{ count($member->venue_access) }} venue(s)
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $member->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $member->status === 'suspended' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $member->status === 'revoked' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($member->last_active_at)
                                {{ $member->last_active_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('business.team.show', $member->id) }}" class="min-h-[40px] px-3 py-2 inline-flex items-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
                                    View
                                </a>
                                @if($member->role !== 'owner')
                                <a href="{{ route('business.team.edit', $member->id) }}" class="min-h-[40px] px-3 py-2 inline-flex items-center text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
                                    Edit
                                </a>
                                @if($member->status === 'pending')
                                <form action="{{ route('business.team.resend', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="min-h-[40px] px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
                                        Resend
                                    </button>
                                </form>
                                @endif
                                @if($member->status === 'active')
                                <form action="{{ route('business.team.suspend', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="min-h-[40px] px-3 py-2 text-orange-600 hover:text-orange-900 hover:bg-orange-50 rounded-lg" onclick="return confirm('Are you sure you want to suspend this team member?')">
                                        Suspend
                                    </button>
                                </form>
                                @endif
                                @if($member->status === 'suspended')
                                <form action="{{ route('business.team.reactivate', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="min-h-[40px] px-3 py-2 text-green-600 hover:text-green-900 hover:bg-green-50 rounded-lg">
                                        Reactivate
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('business.team.destroy', $member->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-[40px] px-3 py-2 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg" onclick="return confirm('Are you sure you want to remove this team member? This action cannot be undone.')">
                                        Remove
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No team members</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by inviting a team member.</p>
                            <div class="mt-6">
                                <a href="{{ route('business.team.create') }}" class="inline-flex items-center min-h-[40px] px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-900 hover:bg-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Invite Team Member
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                <h3 class="text-sm font-medium text-blue-800">About Team Roles</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li><strong>Administrator:</strong> Full access except billing and payment settings</li>
                        <li><strong>Location Manager:</strong> Manage shifts and workers for specific venues</li>
                        <li><strong>Scheduler:</strong> Create and manage shifts only</li>
                        <li><strong>Viewer:</strong> Read-only access to shifts and workers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
