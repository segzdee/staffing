@extends('layouts.dashboard')

@section('title', 'Earnings History')
@section('page-title', 'Earnings History')
@section('page-subtitle', 'Detailed transaction history')

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-card border border-border rounded-xl p-6">
        <form method="GET" action="{{ route('worker.earnings.history') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Period Filter -->
                <div>
                    <label for="period" class="block text-sm font-medium text-foreground mb-1">Period</label>
                    <select name="period" id="period" class="w-full px-3 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                        <option value="all" {{ ($filters['period'] ?? '') == 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ ($filters['period'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ ($filters['period'] ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($filters['period'] ?? '') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($filters['period'] ?? '') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_quarter" {{ ($filters['period'] ?? '') == 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="this_year" {{ ($filters['period'] ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ ($filters['start_date'] ?? '') ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label for="type" class="block text-sm font-medium text-foreground mb-1">Type</label>
                    <select name="type" id="type" class="w-full px-3 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                        <option value="">All Types</option>
                        @foreach($types ?? [] as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}" {{ ($filters['type'] ?? '') == $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-foreground mb-1">Status</label>
                    <select name="status" id="status" class="w-full px-3 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                        <option value="">All Statuses</option>
                        @foreach($statuses ?? [] as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ ($filters['status'] ?? '') == $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range (shown when custom) -->
                <div id="date-range-start">
                    <label for="start_date" class="block text-sm font-medium text-foreground mb-1">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" class="w-full px-3 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                </div>

                <div id="date-range-end">
                    <label for="end_date" class="block text-sm font-medium text-foreground mb-1">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" class="w-full px-3 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-border">
                <a href="{{ route('worker.earnings.history') }}" class="text-sm text-muted-foreground hover:text-foreground">Clear filters</a>
                <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-card border border-border rounded-xl p-4">
            <p class="text-sm text-muted-foreground">Total Gross</p>
            <p class="text-2xl font-bold text-foreground">${{ number_format($summary['total_gross'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-card border border-border rounded-xl p-4">
            <p class="text-sm text-muted-foreground">Total Net</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($summary['total_net'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-card border border-border rounded-xl p-4">
            <p class="text-sm text-muted-foreground">Platform Fees</p>
            <p class="text-2xl font-bold text-foreground">${{ number_format($summary['total_fees'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-card border border-border rounded-xl p-4">
            <p class="text-sm text-muted-foreground">Transactions</p>
            <p class="text-2xl font-bold text-foreground">{{ $summary['count'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-card border border-border rounded-xl overflow-hidden">
        <div class="p-6 border-b border-border flex items-center justify-between">
            <h3 class="text-lg font-semibold text-foreground">Transactions</h3>
            <div class="flex gap-2">
                <a href="{{ route('worker.earnings.export', array_merge(['format' => 'csv'], $filters ?? [])) }}" class="inline-flex items-center px-3 py-1.5 bg-muted text-foreground rounded-lg hover:bg-muted/80 transition-colors text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Gross</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Fees</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Net</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($earnings ?? [] as $earning)
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                                {{ $earning->earned_date->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($earning->type === 'shift_pay')
                                        bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($earning->type === 'bonus')
                                        bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                    @elseif($earning->type === 'tip')
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($earning->type === 'referral')
                                        bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                    @else
                                        bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                                    @endif
                                ">
                                    {{ $earning->type_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground max-w-xs truncate">
                                {{ $earning->description ?? ($earning->shift?->title ?? '-') }}
                                @if($earning->shift?->business)
                                    <span class="text-muted-foreground block text-xs">{{ $earning->shift->business->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground text-right">
                                ${{ number_format($earning->gross_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-muted-foreground text-right">
                                -${{ number_format($earning->platform_fee + $earning->tax_withheld, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400 text-right">
                                ${{ number_format($earning->net_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($earning->status === 'paid')
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($earning->status === 'approved')
                                        bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    @elseif($earning->status === 'disputed')
                                        bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @else
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @endif
                                ">
                                    {{ $earning->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <h3 class="mt-4 text-lg font-medium text-foreground">No transactions found</h3>
                                <p class="mt-2 text-sm text-muted-foreground">Try adjusting your filters or complete more shifts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="md:hidden divide-y divide-border">
            @forelse($earnings ?? [] as $earning)
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-foreground">{{ $earning->earned_date->format('M j, Y') }}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($earning->status === 'paid')
                                bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                            @elseif($earning->status === 'approved')
                                bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                            @elseif($earning->status === 'disputed')
                                bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                            @else
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                            @endif
                        ">
                            {{ $earning->status_label }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-foreground">{{ $earning->description ?? ($earning->shift?->title ?? 'Earning') }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-muted text-muted-foreground mt-1">
                            {{ $earning->type_label }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Gross: ${{ number_format($earning->gross_amount, 2) }}</span>
                        <span class="font-bold text-green-600 dark:text-green-400">Net: ${{ number_format($earning->net_amount, 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No transactions found</h3>
                    <p class="mt-2 text-sm text-muted-foreground">Try adjusting your filters.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if(isset($earnings) && $earnings->hasPages())
            <div class="px-6 py-4 border-t border-border">
                {{ $earnings->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    // Show/hide custom date range based on period selection
    document.getElementById('period').addEventListener('change', function() {
        const isCustom = this.value === 'custom';
        document.getElementById('date-range-start').style.display = isCustom ? 'block' : 'none';
        document.getElementById('date-range-end').style.display = isCustom ? 'block' : 'none';
    });

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        const isCustom = document.getElementById('period').value === 'custom';
        document.getElementById('date-range-start').style.display = isCustom || '{{ $filters['start_date'] ?? '' }}' ? 'block' : 'none';
        document.getElementById('date-range-end').style.display = isCustom || '{{ $filters['end_date'] ?? '' }}' ? 'block' : 'none';
    });
</script>
@endsection
