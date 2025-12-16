@props(['class' => '', 'background' => 'white']) {{-- white, gray, dark --}}

@php
$backgrounds = [
    'white' => 'bg-white',
    'gray' => 'bg-gray-50',
    'dark' => 'bg-gray-900',
];

$textColors = [
    'white' => 'text-gray-900',
    'gray' => 'text-gray-900',
    'dark' => 'text-white',
];

$subtextColors = [
    'white' => 'text-gray-500',
    'gray' => 'text-gray-500',
    'dark' => 'text-gray-400',
];
@endphp

<section class="{{ $backgrounds[$background] }} py-16 lg:py-20 {{ $class }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Heading --}}
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold {{ $textColors[$background] }}">
                Trusted worldwide.
            </h2>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 lg:gap-12">
            {{-- Stat 1: Businesses --}}
            <div class="text-center">
                <div class="text-4xl lg:text-5xl font-bold {{ $textColors[$background] }} mb-2">
                    500+
                </div>
                <div class="text-sm {{ $subtextColors[$background] }} font-medium">
                    Businesses
                </div>
            </div>

            {{-- Stat 2: Countries --}}
            <div class="text-center">
                <div class="text-4xl lg:text-5xl font-bold {{ $textColors[$background] }} mb-2">
                    70+
                </div>
                <div class="text-sm {{ $subtextColors[$background] }} font-medium">
                    Countries
                </div>
            </div>

            {{-- Stat 3: Support --}}
            <div class="text-center">
                <div class="text-4xl lg:text-5xl font-bold {{ $textColors[$background] }} mb-2">
                    24/7
                </div>
                <div class="text-sm {{ $subtextColors[$background] }} font-medium">
                    Support
                </div>
            </div>

            {{-- Stat 4: Match Time --}}
            <div class="text-center">
                <div class="text-4xl lg:text-5xl font-bold {{ $textColors[$background] }} mb-2">
                    15<span class="text-2xl lg:text-3xl">min</span>
                </div>
                <div class="text-sm {{ $subtextColors[$background] }} font-medium">
                    Average match time
                </div>
            </div>
        </div>
    </div>
</section>
