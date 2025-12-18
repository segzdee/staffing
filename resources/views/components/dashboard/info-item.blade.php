@props([
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'meta' => null, // Array of text items or single string
    'status' => null,
    'statusColor' => 'gray',
    'href' => null,
    'actionLabel' => 'View',
    'sideText' => null, // e.g. Rate or Date
])

@php
    $statusColors = [
        'gray' => 'bg-muted text-muted-foreground',
        'green' => 'bg-green-100 text-green-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
        'blue' => 'bg-blue-100 text-blue-800',
    ];
    $badgeClass = $statusColors[$statusColor] ?? $statusColors['gray'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors gap-3']) }}>
    <div class="flex items-start sm:items-center gap-4 min-w-0">
        @if($icon)
        <div class="p-2 bg-background rounded-lg text-muted-foreground flex-shrink-0 border border-border/50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
            </svg>
        </div>
        @endif
        
        <div class="min-w-0 flex-1">
            <h4 class="font-medium text-foreground truncate">
                @if($href)
                <a href="{{ $href }}" class="hover:underline">{{ $title }}</a>
                @else
                {{ $title }}
                @endif
            </h4>
            
            @if($subtitle)
            <p class="text-sm text-muted-foreground truncate">{{ $subtitle }}</p>
            @endif
            
            @if($meta)
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-muted-foreground/80">
                @if(is_array($meta))
                    @foreach($meta as $item)
                        <span class="flex items-center gap-1">
                            {{ $item }}
                        </span>
                    @endforeach
                @else
                    {{ $meta }}
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2 flex-shrink-0 pl-11 sm:pl-0">
        @if($sideText)
        <p class="font-medium text-foreground text-sm">{{ $sideText }}</p>
        @endif

        @if($status)
        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full {{ $badgeClass }}">
            {{ $status }}
        </span>
        @endif
        
        @if($href && !$sideText && !$status)
        <a href="{{ $href }}" class="text-sm font-medium text-primary hover:text-primary/80 transition-colors">
            {{ $actionLabel }} &rarr;
        </a>
        @endif
    </div>
</div>
