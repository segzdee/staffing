@props([
    'title' => '',
    'action' => null,
    'actionLabel' => 'View all',
])

<div {{ $attributes->merge(['class' => 'bg-card rounded-xl border border-border p-6']) }}>
    @if($title || $action)
        <div class="flex items-center justify-between mb-4">
            @if($title)

                   <h3 class="text-lg font-semibold text-foreground">{{ $title }}</h3>
            @endif
            @if($action)
                <a href="{{ $action }}" class="text-sm text-muted-foreground hover:text-foreground font-medium">{{ $actionLabel }} &rarr;</a>
            @endif
        </div>
    @endif
    {{ $slot }}
</div>
