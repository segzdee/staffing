@props([
    'href' => '#',
    'icon' => null,
    'variant' => 'primary', // primary, secondary
])


@php
    $classes = $variant === 'primary'
        ? 'flex items-center justify-center w-full px-4 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 font-medium transition-colors'
        : 'flex items-center justify-center w-full px-4 py-3 border border-input text-foreground rounded-lg hover:bg-accent hover:text-accent-foreground font-medium transition-colors';
@endphp

 <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
        </svg>
    @endif
    {{ $slot }}
</a>
