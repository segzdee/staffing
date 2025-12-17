@props([
    'title' => 'Untitled Shift',
    'subtitle' => '',
    'date' => null,
    'startTime' => null,
    'endTime' => null,
    'rate' => null,
    'status' => null,
    'statusColor' => 'gray',
    'href' => null,
    'actionLabel' => 'View',
    'filled' => null,
    'required' => null,
])

@php
    $statusColors = [
        'green' => 'bg-green-100 text-green-800', // Keep specific status colors for now, or move to semantic 'positive' type later if needed
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'gray' => 'bg-muted text-muted-foreground',
    ];
    $statusClass = $statusColors[$statusColor] ?? $statusColors['gray'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 bg-card border border-border/50 rounded-lg hover:bg-accent/50 transition-colors gap-3']) }}>
    <div class="flex items-center space-x-4 min-w-0">
        <div class="p-3 bg-muted rounded-lg flex-shrink-0">
            <svg class="w-6 h-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <h4 class="font-medium text-foreground truncate">{{ $title }}</h4>
            @if($subtitle)
            <p class="text-sm text-muted-foreground truncate">{{ $subtitle }}</p>
            @endif
            @if($date || $startTime || $endTime)
            <p class="text-xs text-muted-foreground/70 mt-1">
                @if($date){{ \Carbon\Carbon::parse($date)->format('M j, Y') }}@endif
                @if($startTime && $endTime)
                 • {{ \Carbon\Carbon::parse($startTime)->format('g:i A') }} - {{ \Carbon\Carbon::parse($endTime)->format('g:i A') }}
                @endif
            </p>
            @endif
            @if($filled !== null && $required !== null)
            <p class="text-xs text-muted-foreground/70 mt-1">{{ $filled }}/{{ $required }} workers assigned</p>
            @endif
        </div>
    </div>
    <div class="text-left sm:text-right flex-shrink-0">
        @if($rate !== null)
        <p class="font-semibold text-foreground">${{ number_format($rate, 2) }}/hr</p>
        @endif
        @if($status)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }} mt-1">
            {{ $status }}
        </span>
        @endif
        @if($href)
        <a href="{{ $href }}" class="text-sm text-muted-foreground hover:text-foreground block mt-1">{{ $actionLabel }}</a>
        @endif
    </div>
</div>
