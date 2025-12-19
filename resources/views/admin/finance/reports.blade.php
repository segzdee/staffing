@extends('layouts.dashboard')

@section('title', 'Financial Reports')
@section('page-title', 'Financial Reports')
@section('page-subtitle', 'Generate and download financial reports')

@section('content')
<div class="space-y-6">
    {{-- Report Generator --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Generate Report</h3>
            <p class="text-sm text-gray-500 mt-1">Select a report type and date range to generate your financial report</p>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('admin.finance.reports.generate') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Report Type --}}
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select id="report_type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                            <option value="transactions" {{ request('type') === 'transactions' ? 'selected' : '' }}>Transaction Summary</option>
                            <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>Revenue Report</option>
                            <option value="payouts" {{ request('type') === 'payouts' ? 'selected' : '' }}>Payout Report</option>
                            <option value="refunds" {{ request('type') === 'refunds' ? 'selected' : '' }}>Refund Report</option>
                            <option value="commissions" {{ request('type') === 'commissions' ? 'selected' : '' }}>Commission Report</option>
                            <option value="disputes" {{ request('type') === 'disputes' ? 'selected' : '' }}>Dispute Report</option>
                        </select>
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900">
                    </div>

                    {{-- Generate Button --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            Generate Report
                        </button>
                    </div>
                </div>

                {{-- Export Options --}}
                <div class="flex items-center gap-4 pt-4 border-t border-gray-100">
                    <span class="text-sm text-gray-600">Export as:</span>
                    <button type="submit" name="format" value="csv" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        CSV
                    </button>
                    <button type="submit" name="format" value="pdf" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Reports --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Daily Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Daily Summary</h4>
                    <p class="text-sm text-gray-500">{{ now()->format('F d, Y') }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Transactions</span>
                    <span class="font-semibold text-gray-900">{{ $dailySummary['transactions'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Revenue</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($dailySummary['revenue'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Payouts</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($dailySummary['payouts'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('admin.finance.reports.generate', ['type' => 'transactions', 'start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>

        {{-- Weekly Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Weekly Summary</h4>
                    <p class="text-sm text-gray-500">{{ now()->startOfWeek()->format('M d') }} - {{ now()->endOfWeek()->format('M d, Y') }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Transactions</span>
                    <span class="font-semibold text-gray-900">{{ $weeklySummary['transactions'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Revenue</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($weeklySummary['revenue'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Payouts</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($weeklySummary['payouts'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('admin.finance.reports.generate', ['type' => 'transactions', 'start_date' => now()->startOfWeek()->format('Y-m-d'), 'end_date' => now()->endOfWeek()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>

        {{-- Monthly Summary --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">Monthly Summary</h4>
                    <p class="text-sm text-gray-500">{{ now()->format('F Y') }}</p>
                </div>
            </div>
            <div class="space-y-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Transactions</span>
                    <span class="font-semibold text-gray-900">{{ $monthlySummary['transactions'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Revenue</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($monthlySummary['revenue'] ?? 0) / 100, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Payouts</span>
                    <span class="font-semibold text-gray-900">${{ number_format(($monthlySummary['payouts'] ?? 0) / 100, 2) }}</span>
                </div>
            </div>
            <a href="{{ route('admin.finance.reports.generate', ['type' => 'transactions', 'start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}"
                class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                View Details
            </a>
        </div>
    </div>

    {{-- Recent Reports --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Reports</h3>
            <p class="text-sm text-gray-500 mt-1">Previously generated reports</p>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentReports ?? [] as $report)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        @if(($report['format'] ?? 'csv') === 'pdf')
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $report['name'] ?? 'Report' }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $report['type'] ?? 'Transaction' }} Report &middot;
                            {{ $report['period'] ?? 'Custom Period' }} &middot;
                            Generated {{ isset($report['created_at']) ? $report['created_at']->diffForHumans() : 'recently' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($report['format'] ?? 'csv') === 'pdf' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                        {{ strtoupper($report['format'] ?? 'CSV') }}
                    </span>
                    @if(isset($report['download_url']))
                    <a href="{{ $report['download_url'] }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download
                    </a>
                    @endif
                </div>
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

    {{-- Scheduled Reports --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-gray-900">Scheduled Reports</h3>
                <p class="text-sm text-gray-500 mt-1">Automatically generated reports sent to your email</p>
            </div>
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Schedule New Report
            </button>
        </div>
        <div class="space-y-3">
            @forelse($scheduledReports ?? [] as $scheduled)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center border border-gray-200">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $scheduled['name'] ?? 'Scheduled Report' }}</p>
                        <p class="text-sm text-gray-500">{{ ucfirst($scheduled['frequency'] ?? 'Weekly') }} &middot; {{ $scheduled['type'] ?? 'Transaction' }} Report</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($scheduled['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($scheduled['status'] ?? 'Active') }}
                    </span>
                    <button type="button" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
            @empty
            <div class="text-center py-8 bg-gray-50 rounded-lg">
                <p class="text-gray-500">No scheduled reports</p>
                <p class="text-sm text-gray-400 mt-1">Set up automatic report generation</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
