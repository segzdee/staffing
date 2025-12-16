@props([
    'title' => '',
    'action' => null,
    'actionLabel' => 'View all',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-gray-200 p-6']) }}>
    @if($title || $action)
    <div class="flex items-center justify-between mb-4">
        @if($title)
        <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        @endif
        @if($action)
        <a href="{{ $action }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">{{ $actionLabel }} &rarr;</a>
        @endif
    </div>
    @endif
    {{ $slot }}
</div>
