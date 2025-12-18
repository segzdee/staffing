<x-layouts.dashboard title="Earnings Overview">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">My Earnings</h1>
                <p class="text-sm text-muted-foreground mt-1">Track your income and payment history</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <form method="GET" action="{{ route('worker.earnings') }}" class="flex gap-3">
                    <select name="period" onchange="this.form.submit()" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground">
                        <option value="this_month" {{ ($period ?? 'this_month') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ ($period ?? '') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ ($period ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="all_time" {{ ($period ?? '') == 'all_time' ? 'selected' : '' }}>All Time</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Earnings Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Earnings -->
            <div class="bg-card border border-border rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-muted-foreground">Total Earnings</p>
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-foreground">${{ number_format($totalEarnings ?? 0, 2) }}</p>
                <p class="text-sm text-muted-foreground mt-2">All time</p>
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
                    <p class="text-sm {{ ($earningsGrowth ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                        {{ ($earningsGrowth ?? 0) >= 0 ? '+' : '' }}{{ $earningsGrowth ?? 0 }}% from previous period
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

            <!-- Hours & Shifts -->
            <div class="bg-card border border-border rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm text-muted-foreground">Work Summary</p>
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-foreground">{{ number_format($hoursWorked ?? 0, 1) }}h</p>
                <p class="text-sm text-muted-foreground mt-2">{{ $shiftsCompleted ?? 0 }} shifts completed</p>
            </div>
        </div>

        <!-- Earnings Chart -->
        <div class="bg-card border border-border rounded-xl p-6">
            <h3 class="text-lg font-semibold text-foreground mb-4">Earnings Over Time</h3>
            <div class="h-64">
                @if(($monthlyEarnings ?? collect())->sum('amount') > 0)
                    <div class="flex items-end justify-between h-full gap-2">
                        @foreach($monthlyEarnings ?? [] as $month)
                            @php
                                $maxAmount = $monthlyEarnings->max('amount') ?: 1;
                                $heightPercent = ($month['amount'] / $maxAmount) * 100;
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-2">
                                <div class="w-full bg-muted rounded-t-lg relative" style="height: {{ max($heightPercent, 5) }}%">
                                    <div class="absolute inset-0 bg-primary rounded-t-lg"></div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ $month['month'] }}</span>
                                <span class="text-xs font-medium text-foreground">${{ number_format($month['amount'], 0) }}</span>
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

        <!-- Recent Payments -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="p-6 border-b border-border">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">Payment History</h3>
                    <a href="{{ route('worker.earnings.history') }}" class="text-sm text-primary hover:underline">View all</a>
                </div>
            </div>
            <div class="divide-y divide-border">
                @forelse($payments ?? [] as $payment)
                    <div class="p-4 flex items-center justify-between hover:bg-muted/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center
                                {{ $payment->status === 'paid_out' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30' }}">
                                @if($payment->status === 'paid_out')
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-foreground">{{ $payment->shift->title ?? 'Shift Payment' }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ $payment->shift->business->name ?? 'Business' }}
                                    @if($payment->created_at)
                                        <span class="mx-1">|</span>
                                        {{ \Carbon\Carbon::parse($payment->created_at)->format('M j, Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600 dark:text-green-400">+${{ number_format($payment->amount ?? 0, 2) }}</p>
                            <p class="text-sm text-muted-foreground">{{ number_format($payment->hours ?? 0, 1) }} hours</p>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-foreground">No payments yet</h3>
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
            <a href="{{ route('worker.earnings.pending') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Pending Payments</p>
                    <p class="text-sm text-muted-foreground">View processing payments</p>
                </div>
            </a>

            <a href="{{ route('worker.tax-documents') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-foreground">Tax Documents</p>
                    <p class="text-sm text-muted-foreground">Download tax forms</p>
                </div>
            </a>

            <a href="{{ route('worker.withdraw') }}" class="bg-card border border-border rounded-xl p-4 hover:border-primary/50 transition-colors flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
</x-layouts.dashboard>
