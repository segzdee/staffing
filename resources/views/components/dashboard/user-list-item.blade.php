@props([
    'name' => 'Unknown User',
    'email' => null, // Optional subtitle
    'subtext' => null, // Bottom left text (e.g. date)
    'avatar' => null, // First letter or image URL
    'status' => null, // Badge text
    'statusColor' => 'gray', // gray, green, blue, yellow, red
    'href' => null,
    'actionLabel' => 'View',
])

@php
    $statusColors = [
        'gray' => 'bg-gray-100 text-gray-700',
        'green' => 'bg-green-100 text-green-800',
        'blue' => 'bg-blue-100 text-blue-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
    ];
    $badgeClass = $statusColors[$statusColor] ?? $statusColors['gray'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-between p-4 bg-muted/30 rounded-lg hover:bg-muted/50 transition-colors gap-3']) }}>
    <div class="flex items-center gap-4 min-w-0">
        <div class="w-10 h-10 rounded-full bg-secondary flex items-center justify-center flex-shrink-0">
            @if($avatar && filter_var($avatar, FILTER_VALIDATE_URL))
                <img src="{{ $avatar }}" alt="{{ $name }}" class="w-full h-full rounded-full object-cover">
            @else
                <span class="text-sm font-semibold text-secondary-foreground">{{ substr($name, 0, 1) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <h4 class="font-medium text-foreground truncate">{{ $name }}</h4>
            @if($email)
                <p class="text-sm text-muted-foreground truncate">{{ $email }}</p>
            @endif
            @if($subtext)
                <p class="text-xs text-muted-foreground/70 mt-0.5">{{ $subtext }}</p>
            @endif
        </div>
    </div>
    <div class="text-right flex-shrink-0">
        @if($status)
            <span class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full {{ $badgeClass }}">
                {{ $status }}
            </span>
        @endif
        
        @if($href)
            <a href="{{ $href }}" class="block text-xs text-muted-foreground hover:text-foreground mt-1 font-medium transition-colors">
                {{ $actionLabel }}
            </a>
        @endif
    </div>
</div>
