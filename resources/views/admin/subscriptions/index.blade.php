@extends('admin.layouts.app')

@section('title', 'Subscription Dashboard')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscription Dashboard</h1>
            <p class="text-gray-600 mt-1">Monitor subscription metrics and revenue</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.subscriptions.plans') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Manage Plans
            </a>
            <a href="{{ route('admin.subscriptions.grant') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Grant Subscription
            </a>
        </div>
    </div>

    {{-- Metrics Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- MRR --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Monthly Recurring Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($metrics['mrr'], 2) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">ARR: ${{ number_format($metrics['arr'], 2) }}</p>
        </div>

        {{-- Active Subscriptions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Subscriptions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($metrics['total_active']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">
                +{{ $metrics['total_trialing'] }} on trial
            </p>
        </div>

        {{-- Monthly Revenue --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">30-Day Revenue</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">${{ number_format($metrics['monthly_revenue'], 2) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm {{ $metrics['revenue_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                {{ $metrics['revenue_growth'] >= 0 ? '+' : '' }}{{ $metrics['revenue_growth'] }}% from last month
            </p>
        </div>

        {{-- Churn Rate --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Churn Rate (30d)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $metrics['churn_rate'] }}%</p>
                </div>
                <div class="p-3 bg-{{ $metrics['churn_rate'] <= 5 ? 'green' : ($metrics['churn_rate'] <= 10 ? 'yellow' : 'red') }}-100 rounded-full">
                    <svg class="h-6 w-6 text-{{ $metrics['churn_rate'] <= 5 ? 'green' : ($metrics['churn_rate'] <= 10 ? 'yellow' : 'red') }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">
                {{ $metrics['churned_subscriptions'] }} churned, {{ $metrics['new_subscriptions'] }} new
            </p>
        </div>
    </div>

    {{-- Secondary Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- By Plan Type --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Active by Plan Type</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Workers</span>
                    <span class="text-sm font-medium text-gray-900">{{ $metrics['by_plan_type']['worker'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Businesses</span>
                    <span class="text-sm font-medium text-gray-900">{{ $metrics['by_plan_type']['business'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Agencies</span>
                    <span class="text-sm font-medium text-gray-900">{{ $metrics['by_plan_type']['agency'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Status Distribution</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Active</span>
                    <span class="text-sm font-medium text-green-600">{{ $metrics['status_counts']['active'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Trialing</span>
                    <span class="text-sm font-medium text-blue-600">{{ $metrics['status_counts']['trialing'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Past Due</span>
                    <span class="text-sm font-medium text-yellow-600">{{ $metrics['status_counts']['past_due'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Canceled</span>
                    <span class="text-sm font-medium text-red-600">{{ $metrics['status_counts']['canceled'] ?? 0 }}</span>
                </div>
            </div>
        </div>

        {{-- Key Stats --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Key Statistics</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Avg. Subscription Value</span>
                    <span class="text-sm font-medium text-gray-900">${{ number_format($metrics['avg_subscription_value'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">Trials Ending (7 days)</span>
                    <span class="text-sm font-medium text-orange-600">{{ $metrics['trials_ending_soon'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Subscriptions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Subscriptions</h3>
                <a href="{{ route('admin.subscriptions.list') }}" class="text-sm text-indigo-600 hover:text-indigo-700">View all</a>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($recentSubscriptions as $subscription)
                <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $subscription->user?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $subscription->plan?->name }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $subscription->status_color }}-100 text-{{ $subscription->status_color }}-800">
                            {{ $subscription->status_label }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ $subscription->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">No subscriptions yet</div>
                @endforelse
            </div>
        </div>

        {{-- Expiring Soon --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Expiring Soon</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($expiringSoon as $subscription)
                <div class="p-4 flex items-center justify-between hover:bg-gray-50">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $subscription->user?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $subscription->plan?->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-orange-600 font-medium">
                            {{ $subscription->current_period_end?->diffForHumans() }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $subscription->current_period_end?->format('M j, Y') }}</p>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">No subscriptions expiring soon</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
