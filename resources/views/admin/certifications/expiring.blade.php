@extends('layouts.dashboard')

@section('title', 'Expiring Certifications')
@section('page-title', 'Expiring Certifications')
@section('page-subtitle', 'Monitor and manage certifications nearing expiration')

@section('content')

    <!-- Breadcrumb -->
    <nav class="mb-4 text-sm">
        <ol class="flex items-center space-x-2">
            <li><a href="{{ route('admin.certifications.index') }}" class="text-gray-500 hover:text-gray-700">Certifications</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900 font-medium">Expiring Soon</li>
        </ol>
    </nav>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-red-800">{{ $expiringIn7Days->count() }}</div>
                    <div class="text-sm text-red-600">Critical (7 days)</div>
                </div>
            </div>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-amber-800">{{ $expiringIn14Days->count() }}</div>
                    <div class="text-sm text-amber-600">Urgent (14 days)</div>
                </div>
            </div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-yellow-800">{{ $expiringIn30Days->count() }}</div>
                    <div class="text-sm text-yellow-600">Warning (30 days)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Reminders Action -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-900">Send Expiry Reminders</h3>
                <p class="text-sm text-gray-500">Notify workers about their expiring certifications</p>
            </div>
            <button type="button" onclick="sendExpiryReminders()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Send Reminders
            </button>
        </div>
    </div>

    <!-- Critical (7 days) -->
    @if($expiringIn7Days->isNotEmpty())
        <x-dashboard.widget-card title="Critical - Expiring in 7 Days" class="mb-6 border-red-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-red-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Worker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certification</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reminder Sent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($expiringIn7Days as $cert)
                            <tr class="bg-red-25">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600">
                                            {{ substr($cert->worker->first_name ?? 'W', 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $cert->worker->name ?? $cert->worker->email }}</div>
                                            <div class="text-xs text-gray-500">{{ $cert->worker->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $cert->safetyCertification->name ?? $cert->certificationType->name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $cert->expiry_date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                        {{ $cert->expiry_date->diffInDays(now()) }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if($cert->last_reminder_sent_at)
                                        {{ $cert->last_reminder_sent_at->diffForHumans() }}
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-dashboard.widget-card>
    @endif

    <!-- Urgent (14 days) -->
    @if($expiringIn14Days->where(fn($c) => $c->expiry_date->diffInDays(now()) > 7)->isNotEmpty())
        <x-dashboard.widget-card title="Urgent - Expiring in 8-14 Days" class="mb-6 border-amber-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Worker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certification</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reminder Sent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($expiringIn14Days->where(fn($c) => $c->expiry_date->diffInDays(now()) > 7) as $cert)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600">
                                            {{ substr($cert->worker->first_name ?? 'W', 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $cert->worker->name ?? $cert->worker->email }}</div>
                                            <div class="text-xs text-gray-500">{{ $cert->worker->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $cert->safetyCertification->name ?? $cert->certificationType->name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $cert->expiry_date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800">
                                        {{ $cert->expiry_date->diffInDays(now()) }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if($cert->last_reminder_sent_at)
                                        {{ $cert->last_reminder_sent_at->diffForHumans() }}
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-dashboard.widget-card>
    @endif

    <!-- Warning (30 days) -->
    @if($expiringIn30Days->where(fn($c) => $c->expiry_date->diffInDays(now()) > 14)->isNotEmpty())
        <x-dashboard.widget-card title="Warning - Expiring in 15-30 Days" class="border-yellow-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-yellow-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Worker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Certification</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reminder Sent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($expiringIn30Days->where(fn($c) => $c->expiry_date->diffInDays(now()) > 14) as $cert)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600">
                                            {{ substr($cert->worker->first_name ?? 'W', 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $cert->worker->name ?? $cert->worker->email }}</div>
                                            <div class="text-xs text-gray-500">{{ $cert->worker->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $cert->safetyCertification->name ?? $cert->certificationType->name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    {{ $cert->expiry_date->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                        {{ $cert->expiry_date->diffInDays(now()) }} days
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if($cert->last_reminder_sent_at)
                                        {{ $cert->last_reminder_sent_at->diffForHumans() }}
                                    @else
                                        <span class="text-gray-400">Never</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-dashboard.widget-card>
    @endif

    @if($expiringIn30Days->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">All Clear</h3>
            <p class="mt-1 text-sm text-gray-500">No certifications expiring in the next 30 days.</p>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    function sendExpiryReminders() {
        if (confirm('Send expiry reminder notifications to all workers with certifications expiring within 30 days?')) {
            fetch('/api/admin/certifications/send-reminders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Failed to send reminders');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    }
</script>
@endpush
