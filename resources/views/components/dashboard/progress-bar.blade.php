@props([
    'label' => '',
    'value' => 0,
    'max' => 100,
    'showPercentage' => true,
    'color' => 'gray',
    'size' => 'md', // sm, md, lg
])
@php
    $percentage = $max > 0 ? min(100, ($value / $max) * 100) : 0;

    $barColors = [
        'gray' => 'bg-foreground',
        'green' => 'bg-green-600',
        'blue' => 'bg-blue-600',
        'yellow' => 'bg-yellow-500',
        'red' => 'bg-red-600',
    ];

    $heights = [
        'sm' => 'h-1.5',
        'md' => 'h-2',
        'lg' => 'h-3',
    ];

    $barColor = $barColors[$color] ?? $barColors['gray'];
    $height = $heights[$size] ?? $heights['md'];
@endphp

<div {{ $attributes }}>
    @if($label || $showPercentage)
        <div class="flex items-center justify-between text-sm mb-2">
            @if($label)
                <span class="text-muted-foreground">{{ $label }}</span>
            @endif

                       @if($showPercentage)
                        <span class="font-semibold text-foreground">{{ round($percentage) }}%</span>
                    @endif
        </div>
    @endif
    <div class="w-full bg-secondary rounded-full {{ $height }}">
        <div class="{{ $barColor }} {{ $height }} rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
    </div>
</div>
