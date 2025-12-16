@props([
    'color' => 'green', // green, blue, gray, orange, purple
    'dot' => true,
    'pillSize' => 'md' // sm, md, lg
])

@php
$colorClasses = [
    'green' => 'bg-green-50 text-green-700 border-green-200',
    'blue' => 'bg-blue-50 text-blue-700 border-blue-200',
    'gray' => 'bg-gray-50 text-gray-700 border-gray-200',
    'orange' => 'bg-orange-50 text-orange-700 border-orange-200',
    'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
];

$dotColorClasses = [
    'green' => 'bg-green-500',
    'blue' => 'bg-blue-500',
    'gray' => 'bg-gray-500',
    'orange' => 'bg-orange-500',
    'purple' => 'bg-purple-500',
];

$sizeClasses = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-3 py-1 text-sm',
    'lg' => 'px-4 py-1.5 text-base',
];

// Validate inputs with fallbacks
$colorClass = $colorClasses[$color] ?? $colorClasses['gray'];
$dotClass = $dotColorClasses[$color] ?? $dotColorClasses['gray'];
$sizeClass = $sizeClasses[$pillSize] ?? $sizeClasses['md'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border font-medium ' . $colorClass . ' ' . $sizeClass]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
    @endif
    {{ $slot }}
</span>
