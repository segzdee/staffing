@extends('layouts.dashboard')

@section('title', 'Feature Flags')
@section('page-title', 'Feature Flags')
@section('page-subtitle', 'Manage platform feature flags and rollouts')

@section('content')
<div class="space-y-6">
    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Flags</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Enabled</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['enabled'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Disabled</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['disabled'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-emerald-100 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Now</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Rolling Out</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['rolling_out'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            {{-- Search --}}
            <form method="GET" action="{{ route('admin.feature-flags.index') }}" class="flex items-center gap-2">
                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search flags..."
                           class="w-64 h-10 pl-10 pr-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.feature-flags.index', ['status' => $status]) }}" class="text-sm text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="flex items-center gap-2">
            {{-- Clear Cache --}}
            <form method="POST" action="{{ route('admin.feature-flags.clear-cache') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Clear Cache
                </button>
            </form>

            {{-- Create Button --}}
            <a href="{{ route('admin.feature-flags.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Flag
            </a>
        </div>
    </div>

    {{-- Status Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-4 overflow-x-auto" aria-label="Status tabs">
            <a href="{{ route('admin.feature-flags.index', ['search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ !$status ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                All
            </a>
            <a href="{{ route('admin.feature-flags.index', ['status' => 'enabled', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $status === 'enabled' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Enabled
            </a>
            <a href="{{ route('admin.feature-flags.index', ['status' => 'disabled', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $status === 'disabled' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Disabled
            </a>
            <a href="{{ route('admin.feature-flags.index', ['status' => 'active', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $status === 'active' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Active
            </a>
            <a href="{{ route('admin.feature-flags.index', ['status' => 'scheduled', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors {{ $status === 'scheduled' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Scheduled
            </a>
        </nav>
    </div>

    {{-- Feature Flags Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($featureFlags->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No feature flags</h3>
                <p class="mt-2 text-gray-500">Get started by creating a new feature flag.</p>
                <a href="{{ route('admin.feature-flags.create') }}" class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Feature Flag
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Flag
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rollout
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Schedule
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Targeting
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($featureFlags as $flag)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.feature-flags.show', $flag) }}" class="text-sm font-medium text-gray-900 hover:text-gray-700">
                                            {{ $flag->name }}
                                        </a>
                                        <span class="text-xs font-mono text-gray-500">{{ $flag->key }}</span>
                                        @if($flag->description)
                                            <span class="mt-1 text-xs text-gray-400 line-clamp-1">{{ Str::limit($flag->description, 50) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusLabel = $flag->getStatusLabel();
                                        $statusColor = $flag->getStatusColor();
                                        $colorClasses = match($statusColor) {
                                            'green' => 'bg-green-100 text-green-700',
                                            'red' => 'bg-red-100 text-red-700',
                                            'yellow' => 'bg-yellow-100 text-yellow-700',
                                            'blue' => 'bg-blue-100 text-blue-700',
                                            'purple' => 'bg-purple-100 text-purple-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-gray-900 h-2 rounded-full" style="width: {{ $flag->rollout_percentage }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ $flag->rollout_percentage }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($flag->starts_at || $flag->ends_at)
                                        <div class="flex flex-col text-xs">
                                            @if($flag->starts_at)
                                                <span>From: {{ $flag->starts_at->format('M j, Y') }}</span>
                                            @endif
                                            @if($flag->ends_at)
                                                <span>Until: {{ $flag->ends_at->format('M j, Y') }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">No schedule</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1">
                                        @if(!empty($flag->enabled_for_users))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                                {{ count($flag->enabled_for_users) }} users
                                            </span>
                                        @endif
                                        @if(!empty($flag->enabled_for_roles))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                                {{ count($flag->enabled_for_roles) }} roles
                                            </span>
                                        @endif
                                        @if(!empty($flag->enabled_for_tiers))
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-50 text-amber-700">
                                                {{ count($flag->enabled_for_tiers) }} tiers
                                            </span>
                                        @endif
                                        @if(empty($flag->enabled_for_users) && empty($flag->enabled_for_roles) && empty($flag->enabled_for_tiers))
                                            <span class="text-xs text-gray-400">All users</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Toggle Button --}}
                                        <form method="POST" action="{{ route('admin.feature-flags.toggle', $flag) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="p-2 rounded-lg transition-colors {{ $flag->is_enabled ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}"
                                                    title="{{ $flag->is_enabled ? 'Disable' : 'Enable' }}">
                                                @if($flag->is_enabled)
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 7H7a5 5 0 000 10h10a5 5 0 000-10zm0 8a3 3 0 110-6 3 3 0 010 6z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M7 7h10a5 5 0 010 10H7A5 5 0 017 7zm0 8a3 3 0 100-6 3 3 0 000 6z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>

                                        {{-- Edit Button --}}
                                        <a href="{{ route('admin.feature-flags.edit', $flag) }}"
                                           class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                                           title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('admin.feature-flags.destroy', $flag) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this feature flag?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50"
                                                    title="Delete">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($featureFlags->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $featureFlags->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Recent Activity --}}
    @if($recentActivity->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($recentActivity as $activity)
                    <div class="px-6 py-3 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    @if($activity->featureFlag)
                                        {{ $activity->featureFlag->name }}
                                    @else
                                        <span class="text-gray-400">[Deleted Flag]</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $activity->user->name ?? 'System' }} - {{ $activity->action_description }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
