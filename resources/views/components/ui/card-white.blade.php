@props([
    'padding' => 'md', // sm, md, lg, xl
    'shadow' => 'md', // none, sm, md, lg
    'rounded' => 'xl' // md, lg, xl, 2xl
])

@php
$paddings = [
    'none' => '',
    'sm' => 'p-4',
    'md' => 'p-6',
    'lg' => 'p-8',
    'xl' => 'p-10',
];

$shadows = [
    'none' => '',
    'sm' => 'shadow-sm',
    'md' => 'shadow-md',
    'lg' => 'shadow-lg',
];

$roundeds = [
    'md' => 'rounded-md',
    'lg' => 'rounded-lg',
    'xl' => 'rounded-xl',
    '2xl' => 'rounded-2xl',
];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 ' . $paddings[$padding] . ' ' . $shadows[$shadow] . ' ' . $roundeds[$rounded]]) }}>
    {{ $slot }}
</div>
