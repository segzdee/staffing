@props([
    'title' => 'Stat',
    'value' => '0',
    'change' => null,
    'changeType' => 'positive', // 'positive' or 'negative'
    'icon' => null,
    'href' => null
])

<div class="rounded-lg border bg-card text-card-foreground shadow-sm p-6 {{ $href ? 'cursor-pointer hover:bg-accent/50 transition-colors' : '' }}"
     style="background: white; border-color: hsl(240 5.9% 90%);"
     @if($href) onclick="window.location='{{ $href }}'" @endif>
    <div class="flex items-start justify-between">
        <div class="flex-1 space-y-2">
            <!-- Icon -->
            @if($icon)
            <div class="w-10 h-10 rounded-md flex items-center justify-center mb-4"
                 style="background: hsl(240 4.8% 95.9%);">
                <div class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);">
                    {!! $icon !!}
                </div>
            </div>
            @endif

            <!-- Title -->
            <p class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                {{ $title }}
            </p>

            <!-- Value -->
            <p class="text-3xl font-bold" style="color: hsl(240 10% 3.9%);">
                {{ $value }}
            </p>

            <!-- Change Indicator -->
            @if($change)
            <p class="text-sm {{ $changeType === 'positive' ? 'text-green-600' : 'text-red-600' }} flex items-center gap-1">
                @if($changeType === 'positive')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                </svg>
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
                @endif
                {{ $change }}
            </p>
            @endif
        </div>

        <!-- Optional slot for extra content -->
        @if(isset($slot) && !empty(trim($slot)))
        <div class="flex-shrink-0">
            {{ $slot }}
        </div>
        @endif
    </div>

    <!-- Hover Arrow Indicator for clickable cards -->
    @if($href)
    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
        <svg class="w-5 h-5" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
    @endif
</div>
