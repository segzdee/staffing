@extends('admin.layout')

@section('title', 'Agency Tier Assignments')

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
                <h1 class="text-2xl font-bold text-gray-900">Agency Tier Assignments</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage agency tier assignments</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.agency-tiers.agencies') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Agency name, email...">
                </div>

                <!-- Tier Filter -->
                <div>
                    <label for="tier" class="block text-sm font-medium text-gray-700 mb-1">Tier</label>
                    <select name="tier" id="tier"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Tiers</option>
                        <option value="none" {{ request('tier') === 'none' ? 'selected' : '' }}>No Tier</option>
                        @foreach($tiers as $tier)
                        <option value="{{ $tier->id }}" {{ request('tier') == $tier->id ? 'selected' : '' }}>
                            {{ $tier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.agency-tiers.agencies') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Agencies Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Tier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Workers</th>
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
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($agency->tier)
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full {{ $agency->tier->badge_color }}">
                                {{ $agency->tier->name }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
                                No Tier
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $agency->active_workers_count ?? 0 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $agency->tier_achieved_at?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $agency->tier_review_at?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.agency-tiers.adjust-form', $agency) }}"
                               class="text-blue-600 hover:text-blue-900">
                                Adjust Tier
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No agencies found matching your criteria
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agencies->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $agencies->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
