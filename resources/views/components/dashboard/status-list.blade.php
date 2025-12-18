@props([
    'items' => [] // Array of ['label' => 'Database', 'status' => 'Connected', 'color' => 'green']
])

<div class="space-y-3">
    @foreach($items as $item)
    <div class="flex items-center justify-between">
        <span class="text-sm text-muted-foreground">{{ $item['label'] }}</span>
        <span class="inline-flex items-center gap-1.5 text-sm font-medium text-foreground">
            @php
                $colorClass = match($item['color'] ?? 'gray') {
                    'green' => 'bg-green-500',
                    'yellow' => 'bg-yellow-500',
                    'red' => 'bg-red-500',
                    'blue' => 'bg-blue-500',
                    default => 'bg-gray-500',
                };
            @endphp
            <span class="w-2 h-2 {{ $colorClass }} rounded-full"></span>
            {{ $item['status'] }}
        </span>
    </div>
    @endforeach
</div>
