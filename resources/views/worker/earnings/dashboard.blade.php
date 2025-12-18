@extends('layouts.dashboard')

@section('title', 'Earnings Dashboard')
@section('page-title', 'Earnings Dashboard')
@section('page-subtitle', 'Track your income and payment history')

@section('content')
<div class="space-y-6">
    <!-- Period Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-foreground">Financial Overview</h2>
            <p class="text-sm text-muted-foreground">Monitor your earnings and trends</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="GET" action="{{ route('worker.earnings.dashboard') }}" class="flex gap-3">
                <select name="period" onchange="this.form.submit()" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                    <option value="this_week" {{ ($period ?? 'this_month') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ ($period ?? 'this_month') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ ($period ?? '') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_quarter" {{ ($period ?? '') == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                    <option value="this_year" {{ ($period ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>
            <a href="{{ route('worker.earnings.export', ['format' => 'csv']) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Earnings -->
        <div class="bg-card border border-border rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-muted-foreground">Year to Date</p>
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-foreground">${{ number_format($totalEarnings ?? 0, 2) }}</p>
            <p class="text-sm text-muted-foreground mt-2">{{ $shiftsCompleted ?? 0 }} shifts completed</p>
        </div>

        <!-- Period Earnings -->
        <div class="bg-card border border-border rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-muted-foreground">Period Earnings</p>
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-foreground">${{ number_format($periodEarnings ?? 0, 2) }}</p>
            @if(($earningsGrowth ?? 0) != 0)
                <p class="text-sm {{ ($earningsGrowth ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2 flex items-center">
                    @if(($earningsGrowth ?? 0) >= 0)
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                    @endif
                    {{ abs($earningsGrowth ?? 0) }}% vs previous
                </p>
            @else
                <p class="text-sm text-muted-foreground mt-2">Selected period</p>
            @endif
        </div>

        <!-- Pending Payment -->
        <div class="bg-card border border-border rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-muted-foreground">Pending Payment</p>
                <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-foreground">${{ number_format($pendingPayment ?? 0, 2) }}</p>
            <p class="text-sm text-muted-foreground mt-2">Processing</p>
        </div>

        <!-- Average Hourly Rate -->
        <div class="bg-card border border-border rounded-xl p-6">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm text-muted-foreground">Avg Hourly Rate</p>
                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-foreground">${{ number_format($avgHourlyRate ?? 0, 2) }}</p>
            <p class="text-sm text-muted-foreground mt-2">Last 30 days</p>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="bg-card border border-border rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-foreground">Earnings Over Time</h3>
            <div class="text-sm text-muted-foreground">Last 6 months</div>
        </div>
        <div class="h-64">
            @if(($monthlyEarnings ?? collect())->sum('amount') > 0)
                <div class="flex items-end justify-between h-full gap-2">
                    @foreach($monthlyEarnings ?? [] as $month)
                        @php
                            $maxAmount = $monthlyEarnings->max('amount') ?: 1;
                            $heightPercent = ($month['amount'] / $maxAmount) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="text-xs font-medium text-foreground">${{ number_format($month['amount'], 0) }}</div>
                            <div class="w-full bg-muted rounded-t-lg relative flex-1 min-h-[20px]" style="height: {{ max($heightPercent, 5) }}%">
                                <div class="absolute inset-0 bg-primary rounded-t-lg transition-all duration-300 hover:bg-primary/80"></div>
                            </div>
                            <span class="text-xs text-muted-foreground font-medium">{{ $month['month'] }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-full flex items-center justify-center bg-muted/50 rounded-lg">
                    <div class="text-center text-muted-foreground">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                        <p>Complete shifts to see your earnings chart</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Period Comparison -->
    @if(isset($comparison) && $comparison)
    <div class="bg-card border border-border rounded-xl p-6">
        <h3 class="text-lg font-semibold text-foreground mb-4">Period Comparison</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Current Period -->
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-primary/5 rounded-lg">
                    <span class="text-sm font-medium text-foreground">Current Period</span>
                    <span class="text-xs text-muted-foreground">
                        {{ $comparison['current']['period_start']->format('M j') }} - {{ $comparison['current']['period_end']->format('M j, Y') }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-muted/50 rounded-lg">
                        <p class="text-xs text-muted-foreground">Gross Earnings</p>
                        <p class="text-lg font-semibold text-foreground">${{ number_format($comparison['current']['gross_earnings'], 2) }}</p>
                    </div>
                    <div class="p-3 bg-muted/50 rounded-lg">
                        <p class="text-xs text-muted-foreground">Net Earnings</p>
                        <p class="text-lg font-semibold text-foreground">${{ number_format($comparison['current']['net_earnings'], 2) }}</p>
                    </div>
                    <div class="p-3 bg-muted/50 rounded-lg">
                        <p class="text-xs text-muted-foreground">Shifts</p>
                        <p class="text-lg font-semibold text-foreground">{{ $comparison['current']['shifts_completed'] }}</p>
                    </div>
                    <div class="p-3 bg-muted/50 rounded-lg">
                        <p class="text-xs text-muted-foreground">Hours</p>
                        <p class="text-lg font-semibold text-foreground">{{ number_format($comparison['current']['total_hours'], 1) }}</p>
                    </div>
                </div>
            </div>

            <!-- Previous Period -->
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
                    <span class="text-sm font-medium text-foreground">Previous Period</span>
                    <span class="text-xs text-muted-foreground">
                        {{ $comparison['previous']['period_start']->format('M j') }} - {{ $comparison['previous']['period_end']->format('M j, Y') }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-muted/30 rounded-lg">
                        <p class="text-xs text-muted-foreground">Gross Earnings</p>
                        <p class="text-lg font-semibold text-muted-foreground">${{ number_format($comparison['previous']['gross_earnings'], 2) }}</p>
                        @if($comparison['changes']['gross_earnings'] != 0)
                            <p class="text-xs {{ $comparison['changes']['gross_earnings'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $comparison['changes']['gross_earnings'] >= 0 ? '+' : '' }}{{ $comparison['changes']['gross_earnings'] }}%
                            </p>
                        @endif
                    </div>
                    <div class="p-3 bg-muted/30 rounded-lg">
                        <p class="text-xs text-muted-foreground">Net Earnings</p>
                        <p class="text-lg font-semibold text-muted-foreground">${{ number_format($comparison['previous']['net_earnings'], 2) }}</p>
                        @if($comparison['changes']['net_earnings'] != 0)
                            <p class="text-xs {{ $comparison['changes']['net_earnings'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $comparison['changes']['net_earnings'] >= 0 ? '+' : '' }}{{ $comparison['changes']['net_earnings'] }}%
                            </p>
                        @endif
                    </div>
                    <div class="p-3 bg-muted/30 rounded-lg">
                        <p class="text-xs text-muted-foreground">Shifts</p>
                        <p class="text-lg font-semibold text-muted-foreground">{{ $comparison['previous']['shifts_completed'] }}</p>
                        @if($comparison['changes']['shifts_completed'] != 0)
                            <p class="text-xs {{ $comparison['changes']['shifts_completed'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $comparison['changes']['shifts_completed'] >= 0 ? '+' : '' }}{{ $comparison['changes']['shifts_completed'] }}%
                            </p>
                        @endif
                    </div>
                    <div class="p-3 bg-muted/30 rounded-lg">
                        <p class="text-xs text-muted-foreground">Hours</p>
                        <p class="text-lg font-semibold text-muted-foreground">{{ number_format($comparison['previous']['total_hours'], 1) }}</p>
                        @if($comparison['changes']['total_hours'] != 0)
                            <p class="text-xs {{ $comparison['changes']['total_hours'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $comparison['changes']['total_hours'] >= 0 ? '+' : '' }}{{ $comparison['changes']['total_hours'] }}%
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-foreground">Recent Transactions</h3>
                <a href="{{ route('worker.earnings.history') }}" class="text-sm text-primary hover:underline font-medium">View all</a>
            </div>
        </div>
        <div class="divide-y divide-border">
            @forelse($recentTransactions ?? [] as $transaction)
                <div class="p-4 flex items-center justify-between hover:bg-muted/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                            @if($transaction->status === 'paid')
                                bg-green-100 dark:bg-green-900/30
                            @elseif($transaction->status === 'approved')
                                bg-blue-100 dark:bg-blue-900/30
                            @elseif($transaction->status === 'disputed')
                                bg-red-100 dark:bg-red-900/30
                            @else
                                bg-yellow-100 dark:bg-yellow-900/30
                            @endif
                        ">
                            @if($transaction->status === 'paid')
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @elseif($transaction->status === 'disputed')
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-foreground">{{ $transaction->type_label }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ $transaction->description ?? ($transaction->shift?->title ?? 'Earning') }}
                                @if($transaction->shift?->business)
                                    <span class="mx-1">|</span>
                                    {{ $transaction->shift->business->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600 dark:text-green-400">+${{ number_format($transaction->net_amount, 2) }}</p>
                        <p class="text-sm text-muted-foreground">{{ $transaction->earned_date->format('M j, Y') }}</p>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No earnings yet</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Complete shifts to start earning!</p>
                    <a href="{{ route('shifts.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        Browse Shifts
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('worker.earnings.history') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-foreground">Full History</p>
                <p class="text-sm text-muted-foreground">View all transactions</p>
            </div>
        </a>

        <a href="{{ route('worker.earnings.tax-summary') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-foreground">Tax Summary</p>
                <p class="text-sm text-muted-foreground">View tax documents</p>
            </div>
        </a>

        <a href="{{ route('worker.withdraw') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-medium text-foreground">Withdraw Funds</p>
                <p class="text-sm text-muted-foreground">Transfer to your bank</p>
            </div>
        </a>
    </div>
</div>
@endsection
