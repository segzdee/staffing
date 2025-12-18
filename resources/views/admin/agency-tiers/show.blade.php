@extends('admin.layout')

@section('title', $agencyTier->name . ' Tier')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.agency-tiers.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $agencyTier->badge_color }}">
                        {{ $agencyTier->name }}
                    </span>
                    <span class="text-sm text-gray-500">Level {{ $agencyTier->level }}</span>
                    @if(!$agencyTier->is_active)
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded">
                        Inactive
                    </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $agencyTier->agency_profiles_count }} agencies at this tier</p>
            </div>
        </div>
        <a href="{{ route('admin.agency-tiers.edit', $agencyTier) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Tier
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tier Details -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Requirements -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Requirements</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Min Monthly Revenue</span>
                        <span class="text-sm font-medium text-gray-900">${{ number_format($agencyTier->min_monthly_revenue, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Min Active Workers</span>
                        <span class="text-sm font-medium text-gray-900">{{ $agencyTier->min_active_workers }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Min Fill Rate</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($agencyTier->min_fill_rate, 1) }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Min Rating</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($agencyTier->min_rating, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Benefits -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Benefits</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Commission Rate</span>
                        <span class="text-sm font-medium text-green-600">{{ number_format($agencyTier->commission_rate, 1) }}%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Priority Booking</span>
                        <span class="text-sm font-medium text-gray-900">{{ $agencyTier->priority_booking_hours }}h head start</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Dedicated Support</span>
                        @if($agencyTier->dedicated_support)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">Yes</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">No</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Custom Branding</span>
                        @if($agencyTier->custom_branding)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">Yes</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">No</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">API Access</span>
                        @if($agencyTier->api_access)
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded">Yes</span>
                        @else
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">No</span>
                        @endif
                    </div>
                </div>
            </div>

            @if($agencyTier->additional_benefits && count($agencyTier->additional_benefits) > 0)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Additional Benefits</h2>
                </div>
                <div class="p-6">
                    <ul class="space-y-2">
                        @foreach($agencyTier->additional_benefits as $benefit)
                        <li class="flex items-start gap-2 text-sm">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-gray-700">{{ $benefit }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <!-- Agencies at this tier -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Agencies at {{ $agencyTier->name }} Tier</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier Since</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Review</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($agencies as $agency)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $agency->agency_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $agency->user?->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $agency->tier_achieved_at?->format('M d, Y') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $agency->tier_review_at?->format('M d, Y') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.agency-tiers.adjust-form', $agency) }}" class="text-blue-600 hover:text-blue-900">
                                        Adjust Tier
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No agencies at this tier
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($agencies->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $agencies->links() }}
                </div>
                @endif
            </div>

            <!-- Recent Tier History -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Tier Changes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($tierHistory as $history)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $history->agency?->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $history->change_type_badge_class }}">
                                        {{ ucfirst($history->change_type) }}
                                    </span>
                                    <span class="text-sm text-gray-500 ml-2">
                                        {{ $history->fromTier?->name ?? 'None' }} -> {{ $history->toTier?->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $history->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                    No tier changes recorded for this tier
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
