@props([
    'type' => 'button',
    'href' => null,
    'btnSize' => 'md', // sm, md, lg
    'fullWidth' => false,
    'variant' => 'primary', // primary, secondary, outline, ghost, white
    'disabled' => false,
    'loading' => false
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$variantClasses = [
    'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
    'secondary' => 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-500',
    'outline' => 'border-2 border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500',
    'outline-white' => 'border-2 border-white text-white bg-transparent hover:bg-white/10 focus:ring-white',
    'outline-dark' => 'border-2 border-gray-900 text-gray-900 bg-transparent hover:bg-gray-900 hover:text-white focus:ring-gray-500',
    'ghost' => 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
    'white' => 'bg-white text-gray-900 hover:bg-gray-50 focus:ring-gray-500 border border-gray-200',
];

$sizeClasses = [
    'sm' => 'px-4 py-2 text-sm rounded-lg gap-1.5',
    'md' => 'px-6 py-3 text-base rounded-lg gap-2',
    'lg' => 'px-8 py-4 text-lg rounded-xl gap-2.5',
];

$width = $fullWidth ? 'w-full' : '';

// Validate inputs with fallbacks
$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$sizeClass = $sizeClasses[$btnSize] ?? $sizeClasses['md'];

$classes = $baseClasses . ' ' . $variantClass . ' ' . $sizeClass . ' ' . $width;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
