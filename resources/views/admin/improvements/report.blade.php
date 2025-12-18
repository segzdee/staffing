@extends('layouts.dashboard')

@section('title', 'Improvement Report')
@section('page-title', 'Improvement Report')
@section('page-subtitle', 'Comprehensive platform improvement analysis')

@section('content')

<!-- Report Header -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Platform Improvement Report</h2>
            <p class="text-sm text-gray-500 mt-1">
                Period: {{ $report['period']['start'] }} to {{ $report['period']['end'] }}
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.improvements.report.export') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Platform Health -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Health Score</h3>
    <div class="flex items-center gap-8 mb-6">
        <div class="text-center">
            <div class="text-5xl font-bold {{ $report['platform_health']['grade'] === 'A' ? 'text-green-600' : ($report['platform_health']['grade'] === 'B' ? 'text-blue-600' : ($report['platform_health']['grade'] === 'C' ? 'text-yellow-600' : 'text-red-600')) }}">
                {{ $report['platform_health']['grade'] }}
            </div>
            <div class="text-sm text-gray-500 mt-1">Grade</div>
        </div>
        <div class="text-center">
            <div class="text-5xl font-bold text-gray-900">{{ number_format($report['platform_health']['overall_score'], 1) }}</div>
            <div class="text-sm text-gray-500 mt-1">Overall Score</div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($report['platform_health']['components'] as $key => $score)
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <div class="text-xl font-semibold {{ $score >= 80 ? 'text-green-600' : ($score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ number_format($score, 0) }}%
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
            </div>
        @endforeach
    </div>
</div>

<!-- Suggestions Summary -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- By Status -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Suggestions by Status</h3>
        <div class="space-y-3">
            @foreach($report['suggestions']['by_status'] as $status => $count)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-primary"
                                 style="width: {{ $report['suggestions']['total'] > 0 ? ($count / $report['suggestions']['total']) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ $count }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <span class="font-medium text-gray-900">Total</span>
                <span class="font-bold text-gray-900">{{ $report['suggestions']['total'] }}</span>
            </div>
        </div>
    </div>

    <!-- By Category -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Suggestions by Category</h3>
        <div class="space-y-3">
            @foreach($report['suggestions']['by_category'] as $category => $count)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 capitalize">{{ $category }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-blue-500"
                                 style="width: {{ $report['suggestions']['total'] > 0 ? ($count / $report['suggestions']['total']) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ $count }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Key Metrics Summary</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Metric</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trend</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($report['metrics']['key_metrics'] as $metric)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $metric['name'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $metric['formatted_value'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $metric['target'] ? number_format($metric['target'], 2) : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $metric['trend'] === 'up' ? 'text-green-600' : ($metric['trend'] === 'down' ? 'text-red-600' : 'text-gray-500') }}">
                                @if($metric['trend'] === 'up')
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                    </svg>
                                @elseif($metric['trend'] === 'down')
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                                    </svg>
                                @endif
                                {{ ucfirst($metric['trend']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($metric['on_target'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    On Target
                                </span>
                            @elseif($metric['target'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Below Target
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Completions -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Completions</h3>
    @if(count($report['recent_completions']) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days to Complete</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($report['recent_completions'] as $completion)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $completion['title'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ $completion['category'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $completion['completed_at'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $completion['days_to_complete'] }} days</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $completion['submitted_by'] ?? 'Unknown' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 text-center py-8">No suggestions completed in the last 30 days.</p>
    @endif
</div>

<!-- Top Priorities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Critical Suggestions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Critical Suggestions</h3>
        @if(count($report['top_priorities']['critical_suggestions']) > 0)
            <div class="space-y-3">
                @foreach($report['top_priorities']['critical_suggestions'] as $suggestion)
                    <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $suggestion['title'] }}</p>
                            <p class="text-sm text-gray-500 capitalize">{{ $suggestion['category'] }} &middot; {{ $suggestion['votes'] }} votes</p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white text-gray-800 capitalize">
                            {{ str_replace('_', ' ', $suggestion['status']) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No critical suggestions pending.</p>
        @endif
    </div>

    <!-- Declining Metrics -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Metrics Needing Attention</h3>
        @if(count($report['top_priorities']['declining_metrics']) > 0)
            <div class="space-y-3">
                @foreach($report['top_priorities']['declining_metrics'] as $metric)
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $metric['name'] }}</p>
                            <p class="text-sm text-gray-500">Current: {{ number_format($metric['current_value'], 2) }}</p>
                        </div>
                        @if($metric['gap'])
                            <span class="text-sm font-medium text-red-600">
                                {{ number_format($metric['gap'], 2) }} below target
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-green-600 text-center py-4">All metrics are on track!</p>
        @endif
    </div>
</div>

@endsection
