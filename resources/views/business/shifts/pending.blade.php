<x-layouts.dashboard>
    <x-slot:title>Pending Shifts</x-slot:title>

    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Pending Review</p>
                <p class="text-2xl font-bold text-foreground">{{ $stats['total_pending'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Total Applications</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total_applications'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Needs Action</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['needs_action'] ?? 0 }}</p>
            </div>
            <div class="bg-card border border-border rounded-lg p-4">
                <p class="text-sm text-muted-foreground">Starting Soon</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['starting_soon'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Review Applications</p>
                    <p class="text-sm text-blue-700 dark:text-blue-300">These shifts have workers who have applied and are waiting for your approval.</p>
                </div>
            </div>
        </div>

        <!-- Pending Shifts List -->
        <div class="space-y-4">
            @forelse($shifts as $shift)
                <div class="bg-card border border-border rounded-lg overflow-hidden">
                    <!-- Shift Header -->
                    <div class="p-6 border-b border-border">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-foreground">{{ $shift->title ?? 'Shift' }}</h3>
                                    @if(\Carbon\Carbon::parse($shift->shift_date)->diffInDays(now()) <= 2)
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full">
                                            Starting Soon
                                        </span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ \Carbon\Carbon::parse($shift->shift_date)->format('D, M j, Y') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $shift->venue->name ?? $shift->location ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground">
                                    {{ $shift->applications->count() }} application(s)
                                </span>
                                <a href="{{ route('business.shifts.show', $shift->id) }}"
                                   class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm">
                                    View Shift
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Applications -->
                    <div class="divide-y divide-border">
                        @forelse($shift->applications as $application)
                            <div class="p-4 hover:bg-muted/50 transition-colors">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center">
                                            <span class="text-lg font-medium text-primary">
                                                {{ substr($application->worker->name ?? 'W', 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-foreground">{{ $application->worker->name ?? 'Worker' }}</p>
                                            <div class="flex items-center gap-3 text-sm text-muted-foreground">
                                                @if($application->worker->workerProfile?->rating)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                        </svg>
                                                        {{ number_format($application->worker->workerProfile->rating, 1) }}
                                                    </span>
                                                @endif
                                                @if($application->worker->workerProfile?->shifts_completed)
                                                    <span>{{ $application->worker->workerProfile->shifts_completed }} shifts</span>
                                                @endif
                                                <span>Applied {{ $application->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('business.workers.show', $application->worker_id) }}"
                                           class="px-3 py-1.5 text-sm text-muted-foreground hover:text-foreground border border-border rounded-md hover:bg-muted">
                                            View Profile
                                        </a>
                                        <form action="{{ route('business.applications.approve', $application->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('business.applications.reject', $application->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center text-muted-foreground">
                                <p>No applications yet for this shift.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="bg-card border border-border rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-foreground">All caught up!</h3>
                    <p class="mt-2 text-muted-foreground">No shifts with pending applications right now.</p>
                    <a href="{{ route('business.shifts.upcoming') }}"
                       class="mt-4 inline-flex items-center px-4 py-2 text-primary hover:text-primary/80">
                        View Upcoming Shifts
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
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
