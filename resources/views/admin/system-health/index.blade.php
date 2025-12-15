@extends('layouts.app')

@section('title', 'System Health Monitor')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">System Health Monitor</h1>
            <p class="mt-2 text-gray-600">Real-time monitoring of platform health and performance</p>
        </div>
        <div>
            <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium
                @if($dashboardData['overall_health']['status'] === 'healthy') bg-green-100 text-green-800
                @elseif($dashboardData['overall_health']['status'] === 'warning') bg-yellow-100 text-yellow-800
                @elseif($dashboardData['overall_health']['status'] === 'degraded') bg-orange-100 text-orange-800
                @else bg-red-100 text-red-800
                @endif">
                <span class="w-2 h-2 rounded-full mr-2
                    @if($dashboardData['overall_health']['status'] === 'healthy') bg-green-600
                    @elseif($dashboardData['overall_health']['status'] === 'warning') bg-yellow-600
                    @elseif($dashboardData['overall_health']['status'] === 'degraded') bg-orange-600
                    @else bg-red-600
                    @endif"></span>
                {{ ucfirst($dashboardData['overall_health']['status']) }}
            </span>
        </div>
    </div>

    <!-- Overall Health Score -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Health Score</p>
            <p class="mt-2 text-4xl font-bold
                @if($dashboardData['overall_health']['score'] >= 95) text-green-600
                @elseif($dashboardData['overall_health']['score'] >= 85) text-yellow-600
                @else text-red-600
                @endif">
                {{ $dashboardData['overall_health']['score'] }}%
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Open Incidents</p>
            <p class="mt-2 text-4xl font-bold text-gray-900">
                {{ $dashboardData['overall_health']['open_incidents'] }}
            </p>
            <p class="mt-1 text-sm text-red-600">
                {{ $dashboardData['overall_health']['critical_incidents'] }} critical
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Active Users (15min)</p>
            <p class="mt-2 text-4xl font-bold text-gray-900">
                {{ $dashboardData['user_activity']['active_15_minutes'] }}
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Queue Jobs</p>
            <p class="mt-2 text-4xl font-bold text-gray-900">
                {{ $dashboardData['queue_status']['total_jobs'] }}
            </p>
            <p class="mt-1 text-sm text-red-600">
                {{ $dashboardData['queue_status']['failed_jobs'] }} failed
            </p>
        </div>
    </div>

    <!-- API Performance -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">API Performance (Last Hour)</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $dashboardData['api_performance']['p50'] ?? 'N/A' }}<span class="text-lg">ms</span></p>
                <p class="mt-1 text-sm text-gray-600">p50 Response Time</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $dashboardData['api_performance']['p95'] ?? 'N/A' }}<span class="text-lg">ms</span></p>
                <p class="mt-1 text-sm text-gray-600">p95 Response Time</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-blue-600">{{ $dashboardData['api_performance']['p99'] ?? 'N/A' }}<span class="text-lg">ms</span></p>
                <p class="mt-1 text-sm text-gray-600">p99 Response Time</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-gray-900">{{ $dashboardData['api_performance']['average'] ?? 'N/A' }}<span class="text-lg">ms</span></p>
                <p class="mt-1 text-sm text-gray-600">Average</p>
            </div>
        </div>
    </div>

    <!-- Shift & Payment Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Shift Metrics -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift Metrics (24h)</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Fill Rate</span>
                    <span class="text-2xl font-bold {{ $dashboardData['shift_metrics']['fill_rate'] >= 70 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $dashboardData['shift_metrics']['fill_rate'] }}%
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total Shifts</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $dashboardData['shift_metrics']['total_shifts'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Filled Shifts</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $dashboardData['shift_metrics']['filled_shifts'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Pending Shifts</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $dashboardData['shift_metrics']['pending_shifts'] }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Metrics -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Metrics (24h)</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Success Rate</span>
                    <span class="text-2xl font-bold {{ $dashboardData['payment_metrics']['success_rate'] >= 95 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $dashboardData['payment_metrics']['success_rate'] }}%
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total Payments</span>
                    <span class="text-lg font-semibold text-gray-900">{{ $dashboardData['payment_metrics']['total_payments'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Successful</span>
                    <span class="text-lg font-semibold text-green-600">{{ $dashboardData['payment_metrics']['successful_payments'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Failed</span>
                    <span class="text-lg font-semibold text-red-600">{{ $dashboardData['payment_metrics']['failed_payments'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Status -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Queue Status</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($dashboardData['queue_status']['queues'] as $queueName => $jobCount)
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-gray-900">{{ $jobCount }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ ucfirst($queueName) }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Infrastructure Metrics -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Infrastructure</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600">Database Connections</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $dashboardData['infrastructure']['database_connections'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Redis Connections</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $dashboardData['infrastructure']['redis_connections'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Disk Usage</p>
                <p class="mt-1 text-2xl font-bold {{ $dashboardData['infrastructure']['disk_usage']['used_percentage'] > 80 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $dashboardData['infrastructure']['disk_usage']['used_percentage'] }}%
                </p>
            </div>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Recent Incidents</h3>
            <a href="{{ route('admin.system-health.incidents') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                View All
            </a>
        </div>

        @if(count($dashboardData['recent_incidents']) > 0)
        <div class="space-y-3">
            @foreach($dashboardData['recent_incidents'] as $incident)
            <div class="p-4 border rounded-lg">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-1 text-xs font-medium rounded
                                @if($incident->severity === 'critical') bg-red-100 text-red-800
                                @elseif($incident->severity === 'high') bg-orange-100 text-orange-800
                                @elseif($incident->severity === 'medium') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ ucfirst($incident->severity) }}
                            </span>
                            <span class="px-2 py-1 text-xs font-medium rounded
                                @if($incident->status === 'resolved') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($incident->status) }}
                            </span>
                        </div>
                        <h4 class="mt-2 font-medium text-gray-900">{{ $incident->title }}</h4>
                        <p class="mt-1 text-sm text-gray-600">{{ $incident->description }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            Detected: {{ $incident->detected_at->diffForHumans() }}
                            @if($incident->resolved_at)
                            - Resolved in {{ $incident->duration_minutes }} minutes
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-gray-500 text-center py-4">No recent incidents</p>
        @endif
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setInterval(function() {
    fetch('/admin/system-health/realtime-metrics')
        .then(response => response.json())
        .then(data => {
            // Update page with new data
            location.reload();
        });
}, 30000);
</script>
@endsection
