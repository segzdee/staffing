@props([
    'stats' => [],
    'dividers' => true,
])

<div {{ $attributes->merge(['class' => $dividers ? 'divide-y divide-border' : 'space-y-3']) }}>
    @foreach($stats as $stat)
        <div class="flex items-center justify-between {{ $dividers ? 'py-3 first:pt-0 last:pb-0' : '' }}">
            <span class="text-sm text-muted-foreground">{{ $stat['label'] }}</span>
            <span class="text-sm font-semibold text-foreground">{{ $stat['value'] }}</span>
        </div>
    @endforeach
</div>
