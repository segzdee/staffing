@extends('layouts.dashboard')

@section('title', 'Commission Tracking')
@section('page-title', 'Commission Tracking')
@section('page-subtitle', 'Monitor platform fees and agency commissions')

@section('content')
<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Commissions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">All Time</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($totalCommissions ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Total Commissions</p>
        </div>

        {{-- Platform Revenue --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">Platform</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($platformRevenue ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Platform Revenue</p>
        </div>

        {{-- Agency Commissions --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-1 rounded">Agencies</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($agencyCommissions ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">Agency Commissions</p>
        </div>

        {{-- This Month --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded">{{ now()->format('M Y') }}</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">${{ number_format(($thisMonthCommissions ?? 0) / 100, 2) }}</p>
            <p class="text-sm text-gray-500 mt-1">This Month</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            {{-- Agency Filter --}}
            <div class="min-w-[200px]">
                <label for="agency" class="block text-sm font-medium text-gray-700 mb-1">Agency</label>
                <select id="agency" name="agency_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    <option value="">All Agencies</option>
                    @foreach($agencies ?? [] as $agency)
                    <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Date Range --}}
            <div class="min-w-[140px]">
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>
            <div class="min-w-[140px]">
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['agency_id', 'date_from', 'date_to']))
                <a href="{{ route('admin.finance.commissions') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium">
                    Clear
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Commission Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Platform vs Agency Split --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Commission Split</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Platform Fees</span>
                        <span class="text-sm font-semibold text-gray-900">${{ number_format(($platformRevenue ?? 0) / 100, 2) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full" style="width: {{ $platformPercentage ?? 60 }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $platformPercentage ?? 60 }}% of total</p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Agency Commissions</span>
                        <span class="text-sm font-semibold text-gray-900">${{ number_format(($agencyCommissions ?? 0) / 100, 2) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-purple-600 h-3 rounded-full" style="width: {{ $agencyPercentage ?? 40 }}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $agencyPercentage ?? 40 }}% of total</p>
                </div>
            </div>
        </div>

        {{-- Top Agencies --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Top Agencies by Commission</h3>
            <div class="space-y-3">
                @forelse($topAgencies ?? [] as $index => $agency)
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-500 w-6">{{ $index + 1 }}.</span>
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <span class="text-xs font-medium text-purple-600">{{ substr($agency['name'] ?? 'A', 0, 1) }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-900">{{ $agency['name'] ?? 'Agency' }}</span>
                            <span class="text-sm font-semibold text-gray-900">${{ number_format(($agency['commission'] ?? 0) / 100, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $agency['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No agency commission data available</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Commissions Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Commission Details</h3>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ $commissions->total() ?? 0 }} records</span>
                <a href="{{ route('admin.finance.commissions', array_merge(request()->query(), ['export' => 'csv'])) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Payment ID</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Shift</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Worker Amount</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Platform Fee</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Agency Commission</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Agency</th>
                        <th class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-6 py-3">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($commissions ?? [] as $commission)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-gray-900">{{ $commission->payment_id ?? 'PAY-' . str_pad($commission->id ?? 0, 8, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $commission->shift->title ?? 'Shift' }}</p>
                                <p class="text-xs text-gray-500">{{ $commission->shift->business->name ?? 'Business' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">${{ number_format(($commission->worker_amount ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-blue-600">${{ number_format(($commission->platform_fee ?? 0) / 100, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($commission->agency_commission ?? 0) > 0)
                            <span class="text-sm font-semibold text-purple-600">${{ number_format(($commission->agency_commission ?? 0) / 100, 2) }}</span>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($commission->agency ?? null)
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-purple-600">{{ substr($commission->agency->name ?? 'A', 0, 1) }}</span>
                                </div>
                                <span class="text-sm text-gray-900">{{ $commission->agency->name ?? 'Agency' }}</span>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">Direct hire</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ isset($commission->created_at) ? $commission->created_at->format('M d, Y') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No commission data found</h3>
                            <p class="text-gray-500">Commissions will appear here when shifts are completed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($commissions) && $commissions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $commissions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
