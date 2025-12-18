@extends('admin.layout')

@section('title', 'Adjust Agency Tier')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.agency-tiers.agencies') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Adjust Agency Tier</h1>
            <p class="mt-1 text-sm text-gray-500">Manually adjust tier for {{ $agencyProfile->agency_name }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Agency Info & Current Metrics -->
        <div class="space-y-6">
            <!-- Current Status -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Current Status</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $agencyProfile->agency_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $agencyProfile->user?->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Current Tier:</span>
                            @if($agencyProfile->tier)
                            <span class="ml-2 inline-flex items-center px-3 py-1 text-sm font-medium rounded-full {{ $agencyProfile->tier->badge_color }}">
                                {{ $agencyProfile->tier->name }}
                            </span>
                            @else
                            <span class="ml-2 inline-flex items-center px-3 py-1 text-sm font-medium bg-gray-100 text-gray-600 rounded-full">
                                No Tier Assigned
                            </span>
                            @endif
                        </div>
                    </div>
                    @if($agencyProfile->tier_achieved_at)
                    <p class="mt-2 text-sm text-gray-500">
                        At current tier since: {{ $agencyProfile->tier_achieved_at->format('M d, Y') }}
                    </p>
                    @endif
                </div>
            </div>

            <!-- Current Metrics -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Current Metrics</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900">${{ number_format($metrics['monthly_revenue'], 0) }}</div>
                            <div class="text-sm text-gray-500">Monthly Revenue</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900">{{ $metrics['active_workers'] }}</div>
                            <div class="text-sm text-gray-500">Active Workers</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($metrics['fill_rate'], 1) }}%</div>
                            <div class="text-sm text-gray-500">Fill Rate</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-gray-900">{{ number_format($metrics['rating'], 2) }}</div>
                            <div class="text-sm text-gray-500">Average Rating</div>
                        </div>
                    </div>
                    @if($eligibleTier)
                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium text-blue-800">
                                Based on metrics, this agency qualifies for:
                                <span class="inline-flex items-center ml-1 px-2 py-0.5 text-xs font-medium rounded-full {{ $eligibleTier->badge_color }}">
                                    {{ $eligibleTier->name }}
                                </span>
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tier History -->
            @if($agencyProfile->tierHistory->count() > 0)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Tier History</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($agencyProfile->tierHistory->take(5) as $history)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $history->change_type_badge_class }}">
                                    {{ ucfirst($history->change_type) }}
                                </span>
                                <span class="text-sm text-gray-700">{{ $history->description }}</span>
                            </div>
                            <span class="text-sm text-gray-500">{{ $history->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Adjustment Form -->
        <div>
            <form method="POST" action="{{ route('admin.agency-tiers.adjust', $agencyProfile) }}" class="space-y-6">
                @csrf

                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">New Tier Assignment</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="tier_id" class="block text-sm font-medium text-gray-700 mb-1">Select Tier *</label>
                            <select name="tier_id" id="tier_id" required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Choose a tier...</option>
                                @foreach($tiers as $tier)
                                <option value="{{ $tier->id }}"
                                        {{ $agencyProfile->agency_tier_id == $tier->id ? 'selected' : '' }}>
                                    {{ $tier->name }} (Level {{ $tier->level }}) - {{ $tier->commission_rate }}% commission
                                </option>
                                @endforeach
                            </select>
                            @error('tier_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Adjustment *</label>
                            <textarea name="reason" id="reason" rows="4" required
                                      class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Explain why this tier adjustment is being made...">{{ old('reason') }}</textarea>
                            @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h4 class="text-sm font-medium text-yellow-800">Important</h4>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        This will immediately change the agency's tier and adjust their commission rate accordingly.
                                        This action will be logged and the agency will be notified.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tier Comparison -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Tier Requirements Reference</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Workers</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fill Rate</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commission</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($tiers as $tier)
                                <tr class="{{ $agencyProfile->agency_tier_id == $tier->id ? 'bg-blue-50' : '' }}">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $tier->badge_color }}">
                                            {{ $tier->name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm {{ $metrics['monthly_revenue'] >= $tier->min_monthly_revenue ? 'text-green-600' : 'text-gray-500' }}">
                                        ${{ number_format($tier->min_monthly_revenue, 0) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm {{ $metrics['active_workers'] >= $tier->min_active_workers ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ $tier->min_active_workers }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm {{ $metrics['fill_rate'] >= $tier->min_fill_rate ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ number_format($tier->min_fill_rate, 0) }}%
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-green-600">
                                        {{ number_format($tier->commission_rate, 1) }}%
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.agency-tiers.agencies') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Save Tier Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
