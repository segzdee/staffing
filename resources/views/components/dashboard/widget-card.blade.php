@props([
    'title' => '',
    'action' => null,
    'actionLabel' => 'View all',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow']) }}>
    @if($title || $action)
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
            @if($icon)
            <div class="p-2 bg-gray-100 rounded-lg">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                </svg>
            </div>
            @endif
            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
        </div>

        @if($action)
        <a href="{{ $action }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            {{ $actionLabel }} &rarr;
        </a>
        @endif
    </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
