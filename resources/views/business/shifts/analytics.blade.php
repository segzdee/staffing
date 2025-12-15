@extends('layouts.authenticated')

@section('title', 'Shift Analytics')
@section('page-title', 'Shift Analytics & Reports')

@section('content')
<div class="container pb-5">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900">Shift Analytics</h2>
        <p class="text-gray-500">Track your shift performance and metrics</p>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('business.analytics') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    Update
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Shifts</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $analytics['total_shifts'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Completed Shifts</h3>
            <p class="text-3xl font-bold text-green-600">{{ $analytics['completed_shifts'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Spent</h3>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($analytics['total_spent'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Average Fill Rate</h3>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($analytics['avg_fill_rate'] ?? 0, 1) }}%</p>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Performance Metrics</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Average Fill Time</span>
                    <span class="font-semibold text-gray-900">{{ $analytics['avg_fill_time'] ?? 0 }} hours</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">No-Show Rate</span>
                    <span class="font-semibold text-gray-900">{{ number_format($analytics['no_show_rate'] ?? 0, 1) }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Average Rating</span>
                    <span class="font-semibold text-gray-900">{{ number_format($analytics['avg_rating'] ?? 0, 1) }}/5.0</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Shift Status Breakdown</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Open</span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        {{ $analytics['open_shifts'] ?? 0 }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Assigned</span>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                        {{ $analytics['assigned_shifts'] ?? 0 }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">In Progress</span>
                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-semibold">
                        {{ $analytics['in_progress_shifts'] ?? 0 }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Completed</span>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                        {{ $analytics['completed_shifts'] ?? 0 }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section (Placeholder) -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Performance Trends</h3>
        <div class="text-center py-12 text-gray-500">
            <p>Chart visualization coming soon</p>
            <p class="text-sm mt-2">This section will display shift trends over time</p>
        </div>
    </div>
</div>
@endsection
