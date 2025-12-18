<x-layouts.dashboard title="Shift History">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Shift History</h1>
                <p class="text-sm text-muted-foreground mt-1">View all your past shifts and performance</p>
            </div>
            <form method="GET" action="{{ route('worker.shift-history') }}" class="flex flex-wrap gap-3">
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                    <option value="all" {{ ($status ?? 'all') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="completed" {{ ($status ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ ($status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="no_show" {{ ($status ?? '') == 'no_show' ? 'selected' : '' }}>No Show</option>
                </select>
                <select name="period" onchange="this.form.submit()" class="px-4 py-2 bg-background border border-border rounded-lg focus:ring-2 focus:ring-primary text-foreground text-sm">
                    <option value="all" {{ ($period ?? 'all') == 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="this_week" {{ ($period ?? '') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ ($period ?? '') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ ($period ?? '') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ ($period ?? '') == 'this_year' ? 'selected' : '' }}>This Year</option>
                </select>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['total_shifts'] ?? 0 }}</p>
                        <p class="text-sm text-muted-foreground">Completed</p>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ number_format($stats['total_hours'] ?? 0, 1) }}h</p>
                        <p class="text-sm text-muted-foreground">Total Hours</p>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['cancelled'] ?? 0 }}</p>
                        <p class="text-sm text-muted-foreground">Cancelled</p>
                    </div>
                </div>
            </div>

            <div class="bg-card border border-border rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['no_shows'] ?? 0 }}</p>
                        <p class="text-sm text-muted-foreground">No Shows</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shift History List -->
        <div class="bg-card border border-border rounded-xl overflow-hidden">
            <div class="p-4 border-b border-border">
                <h3 class="font-semibold text-foreground">Past Shifts</h3>
            </div>

            @if(($assignments ?? collect())->count() > 0)
                <div class="divide-y divide-border">
                    @foreach($assignments as $assignment)
                        <div class="p-4 hover:bg-muted/50 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <!-- Status Icon -->
                                    <div class="flex-shrink-0">
                                        @if($assignment->status === 'completed')
                                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        @elseif($assignment->status === 'cancelled')
                                            <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Shift Details -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-foreground truncate">{{ $assignment->shift->title ?? 'Untitled Shift' }}</h4>
                                        <p class="text-sm text-muted-foreground">{{ $assignment->shift->business->name ?? 'Unknown Business' }}</p>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-muted-foreground">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                {{ $assignment->shift->shift_date ? \Carbon\Carbon::parse($assignment->shift->shift_date)->format('M j, Y') : 'N/A' }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ $assignment->shift->start_time ? \Carbon\Carbon::parse($assignment->shift->start_time)->format('g:ia') : '' }}
                                                -
                                                {{ $assignment->shift->end_time ? \Carbon\Carbon::parse($assignment->shift->end_time)->format('g:ia') : '' }}
                                            </span>
                                            @if($assignment->shift->venue)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    {{ $assignment->shift->venue->name ?? $assignment->shift->location_city }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Side Info -->
                                <div class="flex items-center gap-4 sm:flex-col sm:items-end">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        {{ $assignment->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $assignment->status === 'cancelled' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $assignment->status === 'no_show' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                    </span>
                                    @if($assignment->hours_worked)
                                        <span class="text-sm font-medium text-foreground">{{ number_format($assignment->hours_worked, 1) }} hours</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($assignments->hasPages())
                    <div class="p-4 border-t border-border">
                        {{ $assignments->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No shift history</h3>
                    <p class="mt-2 text-sm text-muted-foreground">You haven't completed any shifts yet.</p>
                    <a href="{{ route('shifts.index') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                        Browse Available Shifts
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-layouts.dashboard>
