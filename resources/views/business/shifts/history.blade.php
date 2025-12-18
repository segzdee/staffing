<x-layouts.dashboard>
    <x-slot:title>Shift History</x-slot:title>

    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Total Shifts</p>
                <p class="text-2xl font-bold text-foreground">{{ ($stats['total_completed'] ?? 0) + ($stats['total_cancelled'] ?? 0) }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Completed</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['total_completed'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Cancelled</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['total_cancelled'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Total Spent</p>
                <p class="text-2xl font-bold text-foreground">${{ number_format($stats['total_spent'] ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card border border-border rounded-lg p-4">
            <form method="GET" action="{{ route('business.shifts.history') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Status</label>
                    <select name="status" class="rounded-md border-border bg-background text-foreground text-sm px-3 py-2">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-foreground mb-1">Period</label>
                    <select name="period" class="rounded-md border-border bg-background text-foreground text-sm px-3 py-2">
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm">
                    Apply Filters
                </button>
                <a href="{{ route('business.shifts.history') }}" class="px-4 py-2 text-muted-foreground hover:text-foreground text-sm">
                    Clear
                </a>
            </form>
        </div>

        <!-- History Table -->
        <div class="bg-card border border-border rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Shift</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Workers</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Cost</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($shifts as $shift)
                            <tr class="hover:bg-muted/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-foreground">{{ $shift->title ?? 'Shift #'.$shift->id }}</p>
                                        <p class="text-sm text-muted-foreground">{{ $shift->venue->name ?? $shift->location ?? 'N/A' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-foreground">{{ \Carbon\Carbon::parse($shift->shift_date)->format('M j, Y') }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($shift->assignments->count() > 0)
                                        <div class="flex -space-x-2">
                                            @foreach($shift->assignments->take(3) as $assignment)
                                                <div class="w-8 h-8 rounded-full bg-primary/20 border-2 border-card flex items-center justify-center" title="{{ $assignment->worker->name ?? 'Worker' }}">
                                                    <span class="text-xs font-medium text-primary">
                                                        {{ substr($assignment->worker->name ?? 'W', 0, 1) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($shift->assignments->count() > 3)
                                                <div class="w-8 h-8 rounded-full bg-muted border-2 border-card flex items-center justify-center">
                                                    <span class="text-xs font-medium text-muted-foreground">
                                                        +{{ $shift->assignments->count() - 3 }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-muted-foreground">No workers</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = match($shift->status) {
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClasses }}">
                                        {{ ucfirst($shift->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($shift->status === 'completed')
                                        <p class="font-medium text-foreground">${{ number_format($shift->total_cost ?? 0, 2) }}</p>
                                    @else
                                        <span class="text-muted-foreground">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('business.shifts.show', $shift->id) }}"
                                       class="text-primary hover:text-primary/80 text-sm font-medium">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="mt-4 text-lg font-medium text-foreground">No shift history</h3>
                                    <p class="mt-2 text-muted-foreground">Your completed and cancelled shifts will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($shifts->hasPages())
            <div class="mt-6">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>
</x-layouts.dashboard>
