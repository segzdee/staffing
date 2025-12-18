@props([
    'tierProgress' => null,
    'showHistory' => false,
])

@php
    $currentTier = $tierProgress['current_tier'] ?? null;
    $nextTier = $tierProgress['next_tier'] ?? null;
    $metrics = $tierProgress['metrics'] ?? [];
    $overallProgress = $tierProgress['overall_progress'] ?? 0;

    // Tier badge icon mapping
    $tierIcons = [
        'seedling' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>',
        'user-check' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'award' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>',
        'star' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
        'crown' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l3.5 7L12 4l3.5 6L19 3v14a2 2 0 01-2 2H7a2 2 0 01-2-2V3z"/></svg>',
        'badge' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-card text-card-foreground border border-border rounded-xl shadow-sm']) }}>
    <!-- Header with Current Tier Badge -->
    <div class="p-6 border-b border-border">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($currentTier)
                    <div class="p-2 rounded-lg" style="background-color: {{ $currentTier['badge_color'] }}20;">
                        <span style="color: {{ $currentTier['badge_color'] }};">
                            {!! $tierIcons[$currentTier['badge_icon'] ?? 'badge'] ?? $tierIcons['badge'] !!}
                        </span>
                    </div>
                @else
                    <div class="p-2 bg-muted rounded-lg">
                        <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-semibold text-card-foreground">Career Tier</h3>
                    @if($currentTier)
                        <p class="text-sm text-muted-foreground">Level {{ $currentTier['level'] }} of 5</p>
                    @endif
                </div>
            </div>

            @if($currentTier)
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-semibold"
                     style="background-color: {{ $currentTier['badge_color'] }}20; color: {{ $currentTier['badge_color'] }};">
                    {!! $tierIcons[$currentTier['badge_icon'] ?? 'badge'] ?? '' !!}
                    <span>{{ $currentTier['name'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="p-6">
        @if($currentTier)
            <!-- Current Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="p-3 bg-muted/50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-foreground">{{ $metrics['shifts_completed'] ?? 0 }}</p>
                    <p class="text-xs text-muted-foreground">Shifts</p>
                </div>
                <div class="p-3 bg-muted/50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-foreground">{{ number_format($metrics['rating'] ?? 0, 1) }}</p>
                    <p class="text-xs text-muted-foreground">Rating</p>
                </div>
                <div class="p-3 bg-muted/50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-foreground">{{ number_format($metrics['hours_worked'] ?? 0, 0) }}</p>
                    <p class="text-xs text-muted-foreground">Hours</p>
                </div>
                <div class="p-3 bg-muted/50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-foreground">{{ $metrics['months_active'] ?? 0 }}</p>
                    <p class="text-xs text-muted-foreground">Months</p>
                </div>
            </div>

            <!-- Tier Benefits -->
            @if(!empty($currentTier['benefits']))
                <div class="mb-6 p-4 bg-muted/30 rounded-lg border border-border">
                    <p class="text-sm font-medium text-foreground mb-3">Your {{ $currentTier['name'] }} Benefits</p>
                    <div class="space-y-2">
                        @foreach($currentTier['benefits'] as $benefit)
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <svg class="w-4 h-4 flex-shrink-0" style="color: {{ $currentTier['badge_color'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $benefit }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Progress to Next Tier -->
            @if($nextTier)
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-foreground">Progress to {{ $nextTier['name'] }}</span>
                        <span class="text-sm text-muted-foreground">{{ $overallProgress }}%</span>
                    </div>

                    <!-- Overall Progress Bar -->
                    <div class="w-full bg-secondary rounded-full h-2.5">
                        <div class="h-2.5 rounded-full transition-all duration-500"
                             style="width: {{ $overallProgress }}%; background-color: {{ $nextTier['badge_color'] ?? '#3B82F6' }};">
                        </div>
                    </div>

                    <!-- Detailed Progress -->
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        @if(isset($nextTier['progress']['shifts']))
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Shifts</span>
                                    <span class="text-foreground font-medium">
                                        {{ $nextTier['progress']['shifts']['current'] }}/{{ $nextTier['progress']['shifts']['required'] }}
                                    </span>
                                </div>
                                <div class="w-full bg-secondary rounded-full h-1.5">
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $nextTier['progress']['shifts']['percent'] }}%;"></div>
                                </div>
                            </div>
                        @endif

                        @if(isset($nextTier['progress']['rating']))
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Rating</span>
                                    <span class="text-foreground font-medium">
                                        {{ number_format($nextTier['progress']['rating']['current'], 2) }}/{{ number_format($nextTier['progress']['rating']['required'], 2) }}
                                    </span>
                                </div>
                                <div class="w-full bg-secondary rounded-full h-1.5">
                                    <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ $nextTier['progress']['rating']['percent'] }}%;"></div>
                                </div>
                            </div>
                        @endif

                        @if(isset($nextTier['progress']['hours']))
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Hours</span>
                                    <span class="text-foreground font-medium">
                                        {{ number_format($nextTier['progress']['hours']['current'], 0) }}/{{ $nextTier['progress']['hours']['required'] }}
                                    </span>
                                </div>
                                <div class="w-full bg-secondary rounded-full h-1.5">
                                    <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $nextTier['progress']['hours']['percent'] }}%;"></div>
                                </div>
                            </div>
                        @endif

                        @if(isset($nextTier['progress']['months']))
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Months Active</span>
                                    <span class="text-foreground font-medium">
                                        {{ $nextTier['progress']['months']['current'] }}/{{ $nextTier['progress']['months']['required'] }}
                                    </span>
                                </div>
                                <div class="w-full bg-secondary rounded-full h-1.5">
                                    <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ $nextTier['progress']['months']['percent'] }}%;"></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Next Tier Benefits Preview -->
                    @if(!empty($nextTier['benefits']))
                        <div class="mt-4 pt-4 border-t border-border">
                            <p class="text-xs font-medium text-muted-foreground mb-2">Unlock with {{ $nextTier['name'] }}:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(array_slice($nextTier['benefits'], 0, 3) as $benefit)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-muted rounded text-xs text-muted-foreground">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        {{ $benefit }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <!-- At Max Tier -->
                <div class="flex items-center gap-3 p-4 rounded-lg border"
                     style="background-color: {{ $currentTier['badge_color'] }}10; border-color: {{ $currentTier['badge_color'] }}40;">
                    <svg class="w-6 h-6 flex-shrink-0" style="color: {{ $currentTier['badge_color'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l3.5 7L12 4l3.5 6L19 3v14a2 2 0 01-2 2H7a2 2 0 01-2-2V3z"/>
                    </svg>
                    <div>
                        <p class="font-medium" style="color: {{ $currentTier['badge_color'] }};">
                            You've reached the highest tier!
                        </p>
                        <p class="text-sm text-muted-foreground">
                            You're a {{ $currentTier['name'] }} - enjoying all maximum benefits.
                        </p>
                    </div>
                </div>
            @endif
        @else
            <!-- No Tier / New Worker -->
            <div class="text-center py-6">
                <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h4 class="font-medium text-foreground mb-2">Start Your Career Journey</h4>
                <p class="text-sm text-muted-foreground mb-4">
                    Complete shifts to earn your first tier badge and unlock exclusive benefits.
                </p>
                <a href="{{ route('shifts.index') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-sm font-medium">
                    Browse Available Shifts
                </a>
            </div>
        @endif
    </div>

    <!-- View Tier History Link -->
    @if($showHistory && $currentTier)
        <div class="px-6 py-4 bg-muted/30 border-t border-border rounded-b-xl">
            <a href="{{ route('worker.profile') }}#tier-history" class="text-sm font-medium text-primary hover:text-primary/80 flex items-center justify-center gap-1">
                View Tier History
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif
</div>
