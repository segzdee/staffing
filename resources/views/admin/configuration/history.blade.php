@extends('layouts.dashboard')

@section('title', 'Configuration History')
@section('page-title', 'Configuration History')
@section('page-subtitle', 'Audit trail of all platform configuration changes')

@section('content')
<div class="space-y-6">
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <a href="{{ route('admin.configuration.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Configuration
        </a>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Changes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="p-3 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['today']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">This Week</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['this_week']) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['this_month']) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="GET" action="{{ route('admin.configuration.history') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Setting Key Filter --}}
            <div>
                <label for="key" class="block text-sm font-medium text-gray-700 mb-1">Setting</label>
                <select name="key" id="key" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <option value="">All Settings</option>
                    @foreach($settingsKeys as $key)
                        <option value="{{ $key }}" {{ $filters['key'] === $key ? 'selected' : '' }}>{{ $key }}</option>
                    @endforeach
                </select>
            </div>

            {{-- User Filter --}}
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Changed By</label>
                <select name="user_id" id="user_id" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <option value="">All Users</option>
                    @foreach($adminUsers as $admin)
                        <option value="{{ $admin->id }}" {{ $filters['user_id'] == $admin->id ? 'selected' : '' }}>
                            {{ $admin->name }} ({{ $admin->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Start Date --}}
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date"
                       name="start_date"
                       id="start_date"
                       value="{{ $filters['start_date'] }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            </div>

            {{-- End Date --}}
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date"
                       name="end_date"
                       id="end_date"
                       value="{{ $filters['end_date'] }}"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            </div>

            {{-- Actions --}}
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('admin.configuration.history') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Audit Log Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Change History</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Setting</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changed By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($audits as $audit)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono font-medium text-gray-900">{{ $audit->key }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-mono text-red-600 bg-red-50 px-2 py-1 rounded">
                                    {{ Str::limit($audit->old_value ?: '(empty)', 30) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-mono text-green-600 bg-green-50 px-2 py-1 rounded">
                                    {{ Str::limit($audit->new_value, 30) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-sm font-semibold">
                                        {{ strtoupper(substr($audit->changedBy->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $audit->changedBy->name ?? 'System' }}</p>
                                        @if($audit->changedBy)
                                            <p class="text-xs text-gray-500">{{ $audit->changedBy->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-900">{{ $audit->created_at->format('M j, Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $audit->created_at->format('g:i A') }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500 font-mono">{{ $audit->ip_address ?: 'N/A' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-gray-900">No changes found</h3>
                                <p class="mt-2 text-gray-500">No configuration changes match your filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($audits->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $audits->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- Top Changers & Most Changed --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Changers --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Top Changers</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($stats['top_changers'] as $changer)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-sm font-semibold">
                                {{ strtoupper(substr($changer->changedBy->name ?? 'S', 0, 1)) }}
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-900">{{ $changer->changedBy->name ?? 'System' }}</span>
                        </div>
                        <span class="text-sm text-gray-500">{{ number_format($changer->changes_count) }} changes</span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <p class="text-sm">No data available</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Most Changed Settings --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Most Changed Settings</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($stats['most_changed_settings'] as $setting)
                    <div class="px-6 py-3 flex items-center justify-between">
                        <span class="text-sm font-mono font-medium text-gray-900">{{ $setting->key }}</span>
                        <span class="text-sm text-gray-500">{{ number_format($setting->changes_count) }} changes</span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <p class="text-sm">No data available</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
