@extends('layouts.dashboard')

@section('title', 'Financial Reports')
@section('page-title', 'Financial Reports')
@section('page-subtitle', 'Generate and download financial reports and tax documents')

@section('content')
<div class="space-y-6">
    {{-- Report Generator --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Generate Report</h3>
            <p class="text-sm text-gray-500 mt-1">Select a report type and date range to generate your financial report</p>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('agency.finance.reports') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="report_type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select id="report_type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="earnings" {{ request('type') === 'earnings' ? 'selected' : '' }}>Earnings Summary</option>
                        <option value="commissions" {{ request('type') === 'commissions' ? 'selected' : '' }}>Commission Details</option>
                        <option value="payouts" {{ request('type') === 'payouts' ? 'selected' : '' }}>Payout History</option>
                        <option value="workers" {{ request('type') === 'workers' ? 'selected' : '' }}>Worker Performance</option>
                        <option value="tax" {{ request('type') === 'tax' ? 'selected' : '' }}>Tax Summary</option>
                    </select>
                </div>
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Generate
                    </button>
                    <button type="submit" name="format" value="csv" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Reports --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Monthly Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Monthly Summary</h4>
                    <p class="text-sm text-gray-500">{{ now()->format('F Y') }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Earnings</span>
                    <span class="font-semibold text-gray-900">${{ number_format($monthlySummary['earnings'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Shifts Filled</span>
                    <span class="font-semibold text-gray-900">{{ $monthlySummary['shifts'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Workers Active</span>
                    <span class="font-semibold text-gray-900">{{ $monthlySummary['workers'] ?? 0 }}</span>
                </div>
            </div>
            <a href="{{ route('agency.finance.reports', ['type' => 'earnings', 'start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>

        {{-- Quarterly Report --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Quarterly Report</h4>
                    <p class="text-sm text-gray-500">Q{{ ceil(now()->month / 3) }} {{ now()->year }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Earnings</span>
                    <span class="font-semibold text-gray-900">${{ number_format($quarterlySummary['earnings'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Shifts Filled</span>
                    <span class="font-semibold text-gray-900">{{ $quarterlySummary['shifts'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Growth</span>
                    <span class="font-semibold {{ ($quarterlySummary['growth'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($quarterlySummary['growth'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($quarterlySummary['growth'] ?? 0, 1) }}%
                    </span>
                </div>
            </div>
            <a href="{{ route('agency.finance.reports', ['type' => 'earnings', 'start_date' => now()->startOfQuarter()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>

        {{-- Year-to-Date --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Year-to-Date</h4>
                    <p class="text-sm text-gray-500">{{ now()->year }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Earnings</span>
                    <span class="font-semibold text-gray-900">${{ number_format($ytdSummary['earnings'] ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Shifts Filled</span>
                    <span class="font-semibold text-gray-900">{{ $ytdSummary['shifts'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Avg Monthly</span>
                    <span class="font-semibold text-gray-900">${{ number_format($ytdSummary['avgMonthly'] ?? 0, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('agency.finance.reports', ['type' => 'earnings', 'start_date' => now()->startOfYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>
    </div>

    {{-- Tax Documents --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Tax Documents</h3>
            <p class="text-sm text-gray-500 mt-1">Download your tax documents for filing purposes</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($taxDocuments as $doc)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $doc['name'] }}</p>
                        <p class="text-sm text-gray-500">Tax Year {{ $doc['year'] }} &middot; {{ $doc['type'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $doc['status'] === 'available' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ ucfirst($doc['status']) }}
                    </span>
                    @if($doc['status'] === 'available')
                    <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-gray-500">No tax documents available</p>
                <p class="text-sm text-gray-400 mt-1">Tax documents will be available after January 31st for each tax year</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Report History --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Generated Reports</h3>
            <p class="text-sm text-gray-500 mt-1">Your recently generated reports</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentReports as $report)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $report['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $report['period'] }} &middot; Generated {{ $report['created_at']->diffForHumans() }}</p>
                    </div>
                </div>
                <button type="button" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </button>
            </div>
            @empty
            <div class="p-8 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-gray-500">No reports generated yet</p>
                <p class="text-sm text-gray-400 mt-1">Use the form above to generate your first report</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
