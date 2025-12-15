@extends('layouts.app')

@section('title', 'Alert History')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Page Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Alert History</h1>
            <p class="mt-2 text-gray-600">View all sent alerts and their delivery status</p>
        </div>
        <a href="{{ route('admin.alerting.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Configuration
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.alerting.history') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alert Type</label>
                <select name="type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach($alertTypes as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                <select name="severity" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">All Severities</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>Info</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">All Statuses</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="suppressed" {{ request('status') === 'suppressed' ? 'selected' : '' }}>Suppressed</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Metric</label>
                <select name="metric" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="">All Metrics</option>
                    @foreach($metrics as $metric)
                    <option value="{{ $metric }}" {{ request('metric') === $metric ? 'selected' : '' }}>{{ $metric }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Alert History Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($alerts->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alert</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent At</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($alerts as $alert)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $alert->title }}</div>
                            <div class="text-sm text-gray-500">{{ $alert->metric_name }}</div>
                            @if($alert->incident)
                            <a href="{{ route('admin.system-health.incidents.show', $alert->incident_id) }}" class="text-xs text-blue-600 hover:text-blue-700">
                                Incident #{{ $alert->incident_id }}
                            </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded
                                @if($alert->alert_type === 'slack') bg-purple-100 text-purple-800
                                @elseif($alert->alert_type === 'pagerduty') bg-green-100 text-green-800
                                @elseif($alert->alert_type === 'email') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($alert->alert_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($alert->severity === 'critical') bg-red-100 text-red-800
                                @elseif($alert->severity === 'warning') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ ucfirst($alert->severity) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $alert->getStatusBadgeClass() }}
                                @if($alert->status === 'sent') bg-green-100 text-green-800
                                @elseif($alert->status === 'failed') bg-red-100 text-red-800
                                @elseif($alert->status === 'pending') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($alert->status) }}
                            </span>
                            @if($alert->error_message && $alert->status === 'failed')
                            <div class="text-xs text-red-600 mt-1" title="{{ $alert->error_message }}">
                                {{ Str::limit($alert->error_message, 30) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $alert->channel ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $alert->sent_at?->format('M j, g:i A') ?? $alert->created_at->format('M j, g:i A') }}</div>
                            <div class="text-xs text-gray-500">{{ $alert->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($alert->status === 'failed' && $alert->canRetry())
                            <button type="button" onclick="retryAlert({{ $alert->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                Retry
                            </button>
                            @endif
                            @if(!$alert->acknowledged_at)
                            <button type="button" onclick="acknowledgeAlert({{ $alert->id }})" class="text-green-600 hover:text-green-900 mr-3">
                                Acknowledge
                            </button>
                            @else
                            <span class="text-xs text-gray-500" title="Acknowledged by {{ $alert->acknowledgedBy?->name ?? 'Unknown' }}">
                                Acknowledged
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $alerts->withQueryString()->links() }}
        </div>
        @else
        <div class="p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No alerts found</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if(request()->hasAny(['type', 'severity', 'status', 'metric']))
                Try adjusting your filters.
                @else
                No alerts have been sent yet.
                @endif
            </p>
        </div>
        @endif
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

function retryAlert(id) {
    fetch(`/panel/admin/alerting/history/${id}/retry`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    });
}

function acknowledgeAlert(id) {
    fetch(`/panel/admin/alerting/history/${id}/acknowledge`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
@endsection
