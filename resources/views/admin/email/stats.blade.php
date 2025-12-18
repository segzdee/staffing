@extends('layouts.dashboard')

@section('title', 'Email Statistics')
@section('page-title', 'Email Statistics')
@section('page-subtitle', 'Detailed email analytics and performance metrics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.email.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Email Statistics</h1>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Update
            </button>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-500">Total Sent</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-500">Delivery Rate</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ $stats['delivery_rate'] ?? 0 }}%</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-500">Open Rate</div>
            <div class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['open_rate'] ?? 0 }}%</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-500">Click Rate</div>
            <div class="text-2xl font-bold text-purple-600 mt-1">{{ $stats['click_rate'] ?? 0 }}%</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="text-sm text-gray-500">Bounce Rate</div>
            <div class="text-2xl font-bold {{ ($stats['bounce_rate'] ?? 0) > 5 ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $stats['bounce_rate'] ?? 0 }}%</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Status Breakdown -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Status Breakdown</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @php
                        $statusColors = [
                            'queued' => 'bg-gray-500',
                            'sent' => 'bg-blue-500',
                            'delivered' => 'bg-green-500',
                            'opened' => 'bg-purple-500',
                            'clicked' => 'bg-indigo-500',
                            'bounced' => 'bg-red-500',
                            'failed' => 'bg-red-600',
                        ];
                        $total = max(array_sum($stats['by_status'] ?? []), 1);
                    @endphp
                    @foreach(['delivered', 'opened', 'clicked', 'sent', 'queued', 'bounced', 'failed'] as $status)
                        @php
                            $count = $stats['by_status'][$status] ?? 0;
                            $percentage = ($count / $total) * 100;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ ucfirst($status) }}</span>
                                <span class="text-sm text-gray-500">{{ number_format($count) }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="{{ $statusColors[$status] ?? 'bg-gray-500' }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Template Breakdown -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">By Template</h2>
            </div>
            <div class="p-6">
                @if(!empty($stats['by_template']))
                <div class="space-y-4">
                    @foreach($stats['by_template'] as $slug => $count)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <span class="text-sm font-mono text-gray-700">{{ $slug }}</span>
                        <span class="text-sm font-semibold text-gray-900">{{ number_format($count) }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-sm text-gray-500 text-center py-4">No template data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Daily Trend -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Daily Trend</h2>
        </div>
        <div class="p-6">
            @if($dailyStats->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 uppercase">
                            <th class="pb-3">Date</th>
                            <th class="pb-3">Sent</th>
                            <th class="pb-3">Delivered</th>
                            <th class="pb-3">Opened</th>
                            <th class="pb-3">Clicked</th>
                            <th class="pb-3">Bounced</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($dailyStats as $date => $dayStats)
                        <tr class="text-sm">
                            <td class="py-2 font-medium text-gray-900">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                            <td class="py-2 text-gray-600">{{ $dayStats['sent'] ?? 0 }}</td>
                            <td class="py-2 text-green-600">{{ $dayStats['delivered'] ?? 0 }}</td>
                            <td class="py-2 text-purple-600">{{ $dayStats['opened'] ?? 0 }}</td>
                            <td class="py-2 text-indigo-600">{{ $dayStats['clicked'] ?? 0 }}</td>
                            <td class="py-2 text-red-600">{{ $dayStats['bounced'] ?? 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-sm text-gray-500 text-center py-4">No daily data available for selected period</p>
            @endif
        </div>
    </div>
</div>
@endsection
