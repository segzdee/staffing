@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'animate-pulse bg-gray-200 rounded ' . $class]) }}></div>