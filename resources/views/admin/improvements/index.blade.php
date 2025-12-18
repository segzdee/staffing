@extends('layouts.dashboard')

@section('title', 'Continuous Improvement Dashboard')
@section('page-title', 'Continuous Improvement')
@section('page-subtitle', 'Monitor platform health and manage improvement suggestions')

@section('content')

<!-- Platform Health Score -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-6">
            <div class="relative">
                <svg class="w-24 h-24 transform -rotate-90">
                    <circle cx="48" cy="48" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                    <circle cx="48" cy="48" r="40" stroke="{{ $healthScore['grade'] === 'A' ? '#10b981' : ($healthScore['grade'] === 'B' ? '#3b82f6' : ($healthScore['grade'] === 'C' ? '#f59e0b' : '#ef4444')) }}"
                            stroke-width="8" fill="none"
                            stroke-dasharray="{{ 2 * 3.14159 * 40 * ($healthScore['overall_score'] / 100) }} {{ 2 * 3.14159 * 40 }}"
                            stroke-linecap="round"></circle>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold text-gray-900">{{ $healthScore['grade'] }}</span>
                </div>
            </div>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Platform Health Score</h2>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($healthScore['overall_score'], 1) }}/100</p>
            </div>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('admin.improvements.report') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                View Full Report
            </a>
        </div>
    </div>

    <!-- Health Components -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-6 pt-6 border-t border-gray-200">
        @foreach($healthScore['components'] as $key => $score)
            <div class="text-center">
                <div class="text-lg font-semibold {{ $score >= 80 ? 'text-green-600' : ($score >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ number_format($score, 0) }}%
                </div>
                <div class="text-xs text-gray-500 mt-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
            </div>
        @endforeach
    </div>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Pending Review</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</p>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">In Progress</p>
                <p class="text-2xl font-bold text-blue-600">{{ $inProgressCount }}</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Completed This Month</p>
                <p class="text-2xl font-bold text-green-600">{{ $completedThisMonth }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Declining Metrics</p>
                <p class="text-2xl font-bold text-red-600">{{ $decliningMetrics->count() }}</p>
            </div>
            <div class="p-3 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Top Voted Suggestions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Top Voted Suggestions</h3>
            <a href="{{ route('admin.improvements.suggestions') }}" class="text-sm text-primary hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($topSuggestions as $suggestion)
                <a href="{{ route('admin.improvements.suggestion', $suggestion) }}"
                   class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors">
                    <div class="text-center min-w-[50px]">
                        <span class="text-lg font-bold text-gray-700">{{ $suggestion->votes }}</span>
                        <span class="block text-xs text-gray-500">votes</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ $suggestion->title }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $suggestion->status_color }}">
                                {{ $suggestion->status_label }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $suggestion->category_label }}</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            @empty
                <div class="p-8 text-center text-gray-500">
                    No suggestions yet
                </div>
            @endforelse
        </div>
    </div>

    <!-- Declining Metrics -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Declining Metrics</h3>
            <a href="{{ route('admin.improvements.metrics') }}" class="text-sm text-primary hover:underline">View All Metrics</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($decliningMetrics as $metric)
                <div class="flex items-center gap-4 p-4">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900">{{ $metric->name }}</p>
                        <p class="text-sm text-gray-500">{{ $metric->formatted_value }}</p>
                    </div>
                    @if($metric->target_value)
                        <div class="text-right">
                            <p class="text-sm font-medium text-red-600">
                                {{ number_format($metric->target_value - $metric->current_value, 2) }} {{ $metric->unit }} below target
                            </p>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-green-600">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    All metrics are stable or improving
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
    </div>
    <div class="divide-y divide-gray-200">
        @forelse($recentActivity as $item)
            <a href="{{ route('admin.improvements.suggestion', $item) }}"
               class="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors">
                <div class="p-2 rounded-full {{ $item->status === 'completed' ? 'bg-green-100' : ($item->status === 'rejected' ? 'bg-red-100' : 'bg-gray-100') }}">
                    @if($item->status === 'completed')
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @elseif($item->status === 'rejected')
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">{{ $item->title }}</p>
                    <p class="text-sm text-gray-500">
                        by {{ $item->submitter->name ?? 'Unknown' }} &middot; {{ $item->updated_at->diffForHumans() }}
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->status_color }}">
                    {{ $item->status_label }}
                </span>
            </a>
        @empty
            <div class="p-8 text-center text-gray-500">
                No recent activity
            </div>
        @endforelse
    </div>
</div>

@endsection
