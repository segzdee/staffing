@props([
    'title' => 'Coming Soon',
    'description' => 'This page is under development.',
    'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
])

<x-layouts.dashboard :title="$title">
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center max-w-md">
            <div class="mx-auto w-16 h-16 bg-muted rounded-full flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-foreground mb-2">{{ $title }}</h1>
            <p class="text-muted-foreground mb-6">{{ $description }}</p>
            {{ $slot }}
            @if(!$slot->isEmpty())
            @else
            <a href="{{ url()->previous() }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Go Back
            </a>
            @endif
        </div>
    </div>
</x-layouts.dashboard>
