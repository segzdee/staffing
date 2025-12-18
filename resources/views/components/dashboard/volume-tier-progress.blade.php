@props([
    'volumeStats' => null,
    'showHistory' => false,
])

@php
    $currentTier = $volumeStats['current_tier'] ?? null;
    $nextTierInfo = $volumeStats['next_tier'] ?? null;
    $currentMonth = $volumeStats['current_month'] ?? [];
    $customPricing = $volumeStats['custom_pricing'] ?? false;

    // Tier badge colors
    $tierColors = [
        'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
        'blue' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'gold' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    ];

    $badgeColor = $tierColors[$currentTier['badge_color'] ?? 'gray'] ?? $tierColors['gray'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-card text-card-foreground border border-border rounded-xl shadow-sm']) }}>
    <!-- Header -->
    <div class="p-6 border-b border-border">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-muted rounded-lg">
                    <svg class="w-5 h-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-card-foreground">Volume Tier Status</h3>
            </div>

            @if($currentTier)
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $badgeColor }}">
                    {{ $currentTier['name'] }} Tier
                </span>
            @endif
        </div>
    </div>

    <div class="p-6">
        @if($customPricing)
            <!-- Custom Pricing Notice -->
            <div class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 mb-4">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <div>
                    <p class="font-medium text-purple-800 dark:text-purple-200">Custom Enterprise Pricing</p>
                    <p class="text-sm text-purple-600 dark:text-purple-400">
                        Your account has custom pricing: {{ $volumeStats['custom_fee_percent'] }}% platform fee
                    </p>
                </div>
            </div>
        @elseif($currentTier)
            <!-- Current Tier Stats -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 bg-muted/50 rounded-lg">
                    <p class="text-sm text-muted-foreground mb-1">Platform Fee</p>
                    <p class="text-2xl font-bold text-foreground">{{ $currentTier['platform_fee_percent'] }}%</p>
                    @if($currentTier['discount_percentage'] > 0)
                        <p class="text-xs text-green-600 dark:text-green-400 font-medium">
                            {{ $currentTier['discount_percentage'] }}% off standard
                        </p>
                    @endif
                </div>
                <div class="p-4 bg-muted/50 rounded-lg">
                    <p class="text-sm text-muted-foreground mb-1">Shifts This Month</p>
                    <p class="text-2xl font-bold text-foreground">{{ $currentMonth['shifts_posted'] ?? 0 }}</p>
                    <p class="text-xs text-muted-foreground">{{ $currentTier['shift_range'] }}</p>
                </div>
            </div>

            <!-- Monthly Savings -->
            @if(($currentMonth['savings'] ?? 0) > 0)
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 mb-6">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-green-800 dark:text-green-200">
                            ${{ number_format($currentMonth['savings'], 2) }} saved this month
                        </p>
                        <p class="text-sm text-green-600 dark:text-green-400">
                            Thanks to your {{ $currentTier['name'] }} tier discount
                        </p>
                    </div>
                </div>
            @endif

            <!-- Progress to Next Tier -->
            @if($nextTierInfo)
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Progress to {{ $nextTierInfo['name'] }}</span>
                        <span class="font-medium text-foreground">
                            {{ $currentMonth['shifts_posted'] ?? 0 }} / {{ $nextTierInfo['min_shifts_required'] }} shifts
                        </span>
                    </div>

                    @php
                        $progressPercent = $nextTierInfo['min_shifts_required'] > 0
                            ? min(100, (($currentMonth['shifts_posted'] ?? 0) / $nextTierInfo['min_shifts_required']) * 100)
                            : 0;
                    @endphp

                    <div class="w-full bg-secondary rounded-full h-2.5">
                        <div class="bg-primary h-2.5 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ $nextTierInfo['shifts_needed'] }} more shifts to unlock
                        </span>
                        <span class="font-medium text-green-600 dark:text-green-400">
                            {{ $nextTierInfo['platform_fee_percent'] }}% fee
                        </span>
                    </div>

                    @if(($nextTierInfo['potential_savings_percent'] ?? 0) > 0)
                        <p class="text-xs text-muted-foreground">
                            Save an additional {{ $nextTierInfo['potential_savings_percent'] }}% on platform fees
                        </p>
                    @endif
                </div>
            @else
                <!-- At Max Tier -->
                <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-amber-800 dark:text-amber-200">
                            You're at our highest tier!
                        </p>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Enjoy our best rates at {{ $currentTier['platform_fee_percent'] }}% platform fee
                        </p>
                    </div>
                </div>
            @endif

            <!-- Benefits Preview -->
            @if(!empty($currentTier['benefits']))
                <div class="mt-6 pt-6 border-t border-border">
                    <p class="text-sm font-medium text-foreground mb-3">Your Tier Benefits</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach(array_slice($currentTier['benefits'], 0, 4) as $benefit)
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $benefit }}
                            </div>
                        @endforeach
                    </div>
                    @if(count($currentTier['benefits']) > 4)
                        <p class="text-xs text-muted-foreground mt-2">
                            +{{ count($currentTier['benefits']) - 4 }} more benefits
                        </p>
                    @endif
                </div>
            @endif
        @else
            <!-- No Tier / New Business -->
            <div class="text-center py-6">
                <div class="w-12 h-12 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <h4 class="font-medium text-foreground mb-2">Start Posting Shifts</h4>
                <p class="text-sm text-muted-foreground mb-4">
                    Post shifts to qualify for volume discounts and save on platform fees.
                </p>
                <a href="{{ route('shifts.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 text-sm font-medium">
                    Post Your First Shift
                </a>
            </div>
        @endif
    </div>

    <!-- View All Tiers Link -->
    @if($currentTier && !$customPricing)
        <div class="px-6 py-4 bg-muted/30 border-t border-border rounded-b-xl">
            <a href="{{ route('business.analytics') }}" class="text-sm font-medium text-primary hover:text-primary/80 flex items-center justify-center gap-1">
                View All Pricing Tiers
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif
</div>
