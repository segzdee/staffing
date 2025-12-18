@extends('admin.layout')

@section('title', 'Agency Tier History')

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
                <h1 class="text-2xl font-bold text-gray-900">Tier Change History</h1>
                <p class="mt-1 text-sm text-gray-500">Complete history of all agency tier changes</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.agency-tiers.history') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Change Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Change Type</label>
                    <select name="type" id="type"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        <option value="upgrade" {{ request('type') === 'upgrade' ? 'selected' : '' }}>Upgrade</option>
                        <option value="downgrade" {{ request('type') === 'downgrade' ? 'selected' : '' }}>Downgrade</option>
                        <option value="initial" {{ request('type') === 'initial' ? 'selected' : '' }}>Initial</option>
                    </select>
                </div>

                <!-- Tier Filter -->
                <div>
                    <label for="tier" class="block text-sm font-medium text-gray-700 mb-1">Tier</label>
                    <select name="tier" id="tier"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Tiers</option>
                        @foreach($tiers as $tier)
                        <option value="{{ $tier->id }}" {{ request('tier') == $tier->id ? 'selected' : '' }}>
                            {{ $tier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- From Date -->
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- To Date -->
                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.agency-tiers.history') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Change</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($history as $record)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $record->created_at->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $record->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $record->agency?->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">{{ $record->agency?->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full {{ $record->change_type_badge_class }}">
                                {{ ucfirst($record->change_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if($record->fromTier)
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $record->fromTier->badge_color }}">
                                    {{ $record->fromTier->name }}
                                </span>
                                @else
                                <span class="text-sm text-gray-400">None</span>
                                @endif
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $record->toTier?->badge_color ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $record->toTier?->name ?? 'Unknown' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($record->processedByUser)
                            <div>{{ $record->processedByUser->name }}</div>
                            <div class="text-xs text-gray-400">Manual</div>
                            @else
                            <span class="text-gray-400">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            {{ $record->notes ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No tier changes found matching your criteria
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $history->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
