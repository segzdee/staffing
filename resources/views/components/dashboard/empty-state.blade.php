@props([
    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'title' => 'No items found',
    'description' => '',
    'actionUrl' => null,
    'actionLabel' => 'Get started',
])

<div class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
    </svg>
    <h3 class="mt-2 text-sm font-medium text-foreground">{{ $title }}</h3>
    @if($description)
    <p class="mt-1 text-sm text-muted-foreground">{{ $description }}</p>
    @endif
    @if($actionUrl)
    <div class="mt-6">
        <a href="{{ $actionUrl }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-primary-foreground bg-primary hover:bg-primary/90 transition-colors">
            {{ $actionLabel }}
        </a>
    </div>
    @endif
</div>
