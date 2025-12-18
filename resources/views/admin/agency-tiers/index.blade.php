@extends('admin.layout')

@section('title', 'Agency Tier Management')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Agency Tier System</h1>
            <p class="mt-1 text-sm text-gray-500">Manage tier definitions and view agency distribution</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.agency-tiers.agencies') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                View All Agencies
            </a>
            <a href="{{ route('admin.agency-tiers.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Tier
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $totalAgencies }}</div>
            <div class="text-xs text-gray-500">Total Agencies</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $agenciesWithTier }}</div>
            <div class="text-xs text-gray-500">With Tier Assigned</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-orange-600">{{ $agenciesWithoutTier }}</div>
            <div class="text-xs text-gray-500">Without Tier</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $tiers->count() }}</div>
            <div class="text-xs text-gray-500">Active Tiers</div>
        </div>
    </div>

    <!-- Quick Actions -->
    @if($agenciesWithoutTier > 0)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-medium text-yellow-800">{{ $agenciesWithoutTier }} agencies without tier assignment</h3>
                <p class="mt-1 text-sm text-yellow-700">These agencies have not been assigned a tier yet.</p>
            </div>
            <form method="POST" action="{{ route('admin.agency-tiers.assign-initial') }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg hover:bg-yellow-200">
                    Assign Initial Tiers
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Tier Distribution Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tier Cards -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Tier Definitions</h2>
            @foreach($tiers as $tier)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $tier->badge_color }}">
                                {{ $tier->name }}
                            </span>
                            <span class="text-sm text-gray-500">Level {{ $tier->level }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.agency-tiers.show', $tier) }}" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="{{ route('admin.agency-tiers.edit', $tier) }}" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Min Revenue</span>
                            <p class="font-medium">${{ number_format($tier->min_monthly_revenue, 0) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Min Workers</span>
                            <p class="font-medium">{{ $tier->min_active_workers }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Commission</span>
                            <p class="font-medium text-green-600">{{ number_format($tier->commission_rate, 1) }}%</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Agencies</span>
                            <p class="font-medium">{{ $tier->agency_profiles_count }}</p>
                        </div>
                    </div>
                    @if($tier->dedicated_support || $tier->custom_branding || $tier->api_access || $tier->priority_booking_hours > 0)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($tier->priority_booking_hours > 0)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                            {{ $tier->priority_booking_hours }}h Priority
                        </span>
                        @endif
                        @if($tier->dedicated_support)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded">
                            Dedicated Support
                        </span>
                        @endif
                        @if($tier->custom_branding)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-pink-100 text-pink-700 rounded">
                            Custom Branding
                        </span>
                        @endif
                        @if($tier->api_access)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">
                            API Access
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Distribution Chart -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Tier Distribution</h2>
                <form method="POST" action="{{ route('admin.agency-tiers.review') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Run Tier Review
                    </button>
                </form>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                @if(count($distribution) > 0)
                <div class="space-y-4">
                    @foreach($distribution as $item)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ $item['tier_name'] }}</span>
                            <span class="text-sm text-gray-500">{{ $item['agency_count'] }} ({{ $item['percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $item['badge_color'] }}" style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <p class="mt-2">No agencies with tiers assigned yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Tier Changes -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Tier Changes</h2>
            <a href="{{ route('admin.agency-tiers.history') }}" class="text-sm text-blue-600 hover:text-blue-800">View All History</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentChanges as $change)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $change->agency?->name ?? 'Unknown' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">{{ $change->fromTier?->name ?? 'None' }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $change->toTier?->badge_color ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $change->toTier?->name ?? 'Unknown' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $change->change_type_badge_class }}">
                                {{ ucfirst($change->change_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $change->processedByUser?->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $change->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No tier changes recorded yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
