@props([
    'label' => '',
    'value' => '0',
    'icon' => null,
    'trend' => null,
    'trendLabel' => '',
    'href' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow']) }}>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-medium text-gray-600">{{ $label }}</h3>
        @if($icon)
        <div class="p-2 bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
            </svg>
        </div>
        @endif
    </div>
    <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>
    @if($trend !== null)
    <div class="mt-2 flex items-center gap-1 text-sm {{ $trend >= 0 ? 'text-green-600' : 'text-red-600' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            @if($trend >= 0)
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            @else
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
            @endif
        </svg>
        <span class="font-medium">{{ abs($trend) }}%</span>
        @if($trendLabel)
        <span class="text-gray-500">{{ $trendLabel }}</span>
        @endif
    </div>
    @endif
    @if($href)
    <a href="{{ $href }}" class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
        View details
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endif
</div>
