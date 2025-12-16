@props([
    'stats' => [],
    'dividers' => true,
])

<div {{ $attributes->merge(['class' => $dividers ? 'divide-y divide-gray-100' : 'space-y-3']) }}>
    @foreach($stats as $stat)
    <div class="flex items-center justify-between {{ $dividers ? 'py-3 first:pt-0 last:pb-0' : '' }}">
        <span class="text-sm text-gray-600">{{ $stat['label'] }}</span>
        <span class="text-sm font-semibold text-gray-900">{{ $stat['value'] }}</span>
    </div>
    @endforeach
</div>
