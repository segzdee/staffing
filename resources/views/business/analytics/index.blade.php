@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Analytics Dashboard</h1>
        <p class="mt-2 text-gray-600">Monitor your spending, budgets, and cancellation patterns</p>
    </div>

    <!-- Time Range Selector -->
    <div class="mb-6 flex items-center justify-between">
        <div class="flex space-x-2">
            <button onclick="changeTimeRange('week')" class="px-4 py-2 rounded-lg {{ $timeRange === 'week' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
                Week
            </button>
            <button onclick="changeTimeRange('month')" class="px-4 py-2 rounded-lg {{ $timeRange === 'month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
                Month
            </button>
            <button onclick="changeTimeRange('quarter')" class="px-4 py-2 rounded-lg {{ $timeRange === 'quarter' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
                Quarter
            </button>
            <button onclick="changeTimeRange('year')" class="px-4 py-2 rounded-lg {{ $timeRange === 'year' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border' }}">
                Year
            </button>
        </div>

        <div class="flex space-x-2">
            <button onclick="exportData('pdf')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Export PDF
            </button>
            <button onclick="exportData('csv')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Export CSV
            </button>
            <button onclick="exportData('excel')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Export Excel
            </button>
        </div>
    </div>

    <!-- Budget Alerts -->
    @if(count($analytics['budget_alerts']) > 0)
    <div class="mb-6 space-y-3">
        @foreach($analytics['budget_alerts'] as $alert)
        <div class="p-4 rounded-lg border-l-4
            @if($alert['level'] === 'critical') bg-red-50 border-red-500
            @elseif($alert['level'] === 'warning') bg-yellow-50 border-yellow-500
            @else bg-blue-50 border-blue-500
            @endif">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    @if($alert['level'] === 'critical')
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    @elseif($alert['level'] === 'warning')
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium @if($alert['level'] === 'critical') text-red-800 @elseif($alert['level'] === 'warning') text-yellow-800 @else text-blue-800 @endif">
                        {{ $alert['message'] }}
                    </h3>
                    <p class="mt-1 text-sm @if($alert['level'] === 'critical') text-red-700 @elseif($alert['level'] === 'warning') text-yellow-700 @else text-blue-700 @endif">
                        @if($alert['entity'] === 'venue')
                            Venue: {{ $alert['entity_name'] }} -
                        @endif
                        Utilization: {{ $alert['utilization'] }}%
                    </p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Budget Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Monthly Budget</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ${{ number_format($analytics['budget_overview']['monthly_budget_dollars'], 0) }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Current Spend</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ${{ number_format($analytics['budget_overview']['current_spend_dollars'], 0) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ $analytics['budget_overview']['utilization_percentage'] }}% utilized
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Remaining Budget</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ${{ number_format($analytics['budget_overview']['remaining_budget_dollars'], 0) }}
                    </p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">YTD Spend</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ${{ number_format($analytics['budget_overview']['ytd_spend_dollars'], 0) }}
                    </p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Trend Analysis Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">12-Week Spending Trend</h3>
            <canvas id="trendChart" height="300"></canvas>
        </div>

        <!-- Spend by Role Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Spend by Role Type</h3>
            <canvas id="roleChart" height="300"></canvas>
        </div>
    </div>

    <!-- Venue Comparison -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Venue Comparison</h3>
        <canvas id="venueChart" height="150"></canvas>
    </div>

    <!-- Cancellation Stats -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancellation Metrics (Last 30 Days)</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $cancellationStats['metrics']['total_cancellations'] }}</p>
                <p class="mt-1 text-sm text-gray-600">Total Cancellations</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-orange-600">{{ $cancellationStats['metrics']['late_cancellations_30_days'] }}</p>
                <p class="mt-1 text-sm text-gray-600">Late Cancellations</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold {{ $cancellationStats['metrics']['cancellation_rate'] > 15 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $cancellationStats['metrics']['cancellation_rate'] }}%
                </p>
                <p class="mt-1 text-sm text-gray-600">Cancellation Rate</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ count($cancellationStats['warnings']) }}</p>
                <p class="mt-1 text-sm text-gray-600">Active Warnings</p>
            </div>
        </div>

        @if(count($cancellationStats['warnings']) > 0)
        <div class="mt-6 space-y-2">
            <h4 class="font-medium text-gray-900">Active Warnings:</h4>
            @foreach($cancellationStats['warnings'] as $warning)
            <div class="p-3 rounded-lg border-l-4
                @if($warning['severity'] === 'critical') bg-red-50 border-red-500
                @elseif($warning['severity'] === 'high') bg-orange-50 border-orange-500
                @else bg-yellow-50 border-yellow-500
                @endif">
                <p class="text-sm font-medium text-gray-900">{{ $warning['message'] }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
// Trend Analysis Chart
fetch('/business/analytics/trend-data?weeks=12')
    .then(response => response.json())
    .then(data => {
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Spend ($)' }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: { display: true, text: 'Shift Count' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    });

// Spend by Role Chart
fetch('/business/analytics/spend-by-role?range={{ $timeRange }}')
    .then(response => response.json())
    .then(data => {
        new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: data.chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });

// Venue Comparison Chart
fetch('/business/analytics/venue-comparison?range={{ $timeRange }}')
    .then(response => response.json())
    .then(data => {
        new Chart(document.getElementById('venueChart'), {
            type: 'bar',
            data: data.chart,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });

function changeTimeRange(range) {
    window.location.href = '?range=' + range;
}

function exportData(format) {
    window.location.href = '/business/analytics/export-' + format + '?range={{ $timeRange }}';
}
</script>
@endsection
