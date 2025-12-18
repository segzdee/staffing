<x-layouts.dashboard>
    <x-slot:title>Upcoming Shifts</x-slot:title>

    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Total Upcoming</p>
                <p class="text-2xl font-bold text-foreground">{{ $stats['total_upcoming'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Open</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['open'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Filled</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['filled'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Today</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['today'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">This Week</p>
                <p class="text-2xl font-bold text-foreground">{{ $stats['this_week'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card border border-border rounded-lg p-4">
            <form method="GET" action="{{ route('business.shifts.upcoming') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Status</label>
                    <select name="status" class="rounded-md border-border bg-background text-foreground text-sm px-3 py-2">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="filled" {{ $status === 'filled' ? 'selected' : '' }}>Filled</option>
                        <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Period</label>
                    <select name="period" class="rounded-md border-border bg-background text-foreground text-sm px-3 py-2">
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Upcoming</option>
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="next_week" {{ $period === 'next_week' ? 'selected' : '' }}>Next Week</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm">
                    Apply Filters
                </button>
                <a href="{{ route('business.shifts.upcoming') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground text-sm">
                    Clear
                </a>
            </form>
        </div>

        <!-- Shifts List -->
        <div class="space-y-4">
            @forelse($shifts as $shift)
                <div class="bg-card border border-border rounded-lg p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-foreground">{{ $shift->title ?? 'Shift' }}</h3>
                                @php
                                    $statusClasses = match($shift->status) {
                                        'open' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'filled' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClasses }}">
                                    {{ ucfirst(str_replace('_', ' ', $shift->status)) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-muted-foreground">Date</p>
                                    <p class="font-medium text-foreground">
                                        {{ \Carbon\Carbon::parse($shift->shift_date)->format('D, M j, Y') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Time</p>
                                    <p class="font-medium text-foreground">
                                        {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Location</p>
                                    <p class="font-medium text-foreground">{{ $shift->venue->name ?? $shift->location ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Pay Rate</p>
                                    <p class="font-medium text-foreground">${{ number_format($shift->hourly_rate, 2) }}/hr</p>
                                </div>
                            </div>

                            <!-- Workers Progress -->
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm text-muted-foreground">Workers</p>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $shift->assignments->count() }} / {{ $shift->workers_needed ?? 1 }}
                                    </p>
                                </div>
                                @php
                                    $fillPercent = ($shift->workers_needed ?? 1) > 0
                                        ? min(100, ($shift->assignments->count() / ($shift->workers_needed ?? 1)) * 100)
                                        : 0;
                                @endphp
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: {{ $fillPercent }}%"></div>
                                </div>
                            </div>

                            <!-- Assigned Workers -->
                            @if($shift->assignments->count() > 0)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($shift->assignments as $assignment)
                                        <div class="flex items-center gap-2 bg-muted rounded-full px-3 py-1">
                                            <div class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-medium text-primary">
                                                {{ substr($assignment->worker->name ?? 'W', 0, 1) }}
                                            </div>
                                            <span class="text-sm text-foreground">{{ $assignment->worker->name ?? 'Worker' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            <a href="{{ route('business.shifts.show', $shift->id) }}"
                               class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm text-center">
                                View Details
                            </a>
                            @if($shift->status === 'open')
                                <a href="{{ route('business.shifts.edit', $shift->id) }}"
                                   class="px-4 py-2 border border-border text-foreground rounded-md hover:bg-muted text-sm text-center">
                                    Edit Shift
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-card border border-border rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">No upcoming shifts</h3>
                    <p class="mt-2 text-muted-foreground">Get started by posting a new shift.</p>
                    <a href="{{ route('business.shifts.create') }}"
                       class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Post New Shift
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($shifts->hasPages())
            <div class="mt-6">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>
</x-layouts.dashboard>
