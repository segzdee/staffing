@props([
    'title' => 'Quick Actions',
])

<div {{ $attributes->merge(['class' => 'bg-card rounded-xl border border-border p-6']) }}>
    <h3 class="text-lg font-semibold text-foreground mb-4">{{ $title }}</h3>
    <div class="space-y-3">
        {{ $slot }}
    </div>
</div>
