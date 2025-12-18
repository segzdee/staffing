<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, mobileMenuOpen: false }"
    x-init="sidebarOpen = window.innerWidth >= 1024">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- CSP Nonce: All inline styles and scripts must include the nonce attribute --}}
    {{-- Example: <style nonce="{{ $cspNonce ?? '' }}"> or <script nonce="{{ $cspNonce ?? '' }}"> --}}

    <style nonce="{{ $cspNonce ?? '' }}">
        [x-cloak] {
            display: none !important;
        }

        /* Prevent layout shift during load */
        body {
            visibility: hidden;
        }

        body.alpine-loaded {
            visibility: visible;
        }

        /* Custom scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Form Input Unified Styling - using plain CSS instead of @apply */
        .form-input {
            height: 3rem;
            padding-left: 1rem;
            padding-right: 1rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #111827;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            transition: color 0.2s, background-color 0.2s, border-color 0.2s;
        }

        .form-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #111827;
            border-color: transparent;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-error {
            margin-top: 0.25rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #dc2626;
        }
    </style>

    @stack('styles')
</head>

<body class="font-sans antialiased bg-muted/30">
    <div class="min-h-screen">
        <!-- Sidebar -->
        @include('partials.dashboard.sidebar')

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 lg:hidden"></div>

        <!-- Main Content -->
        <div class="lg:pl-64 min-h-screen flex flex-col">
            <!-- Header -->
            @include('partials.dashboard.header')

            <!-- Main Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <!-- Welcome Section -->
                @if(!isset($hideWelcome) || !$hideWelcome)
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        @hasSection('page-title')
                        @yield('page-title')
                        @else
                        Welcome back, {{ auth()->user()->name ?? 'User' }}!
                        @endif
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        @hasSection('page-subtitle')
                        @yield('page-subtitle')
                        @else
                        {{ config('dashboard.roles.' . auth()->user()->user_type . '.badge', '') }}
                        @endif
                    </p>

                    <!-- Onboarding Progress (if provided) -->
                    @if(isset($onboardingProgress) && $onboardingProgress < 100) <div
                        class="mt-4 p-4 bg-white border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-gray-900">Complete your profile</p>
                            <span class="text-sm font-semibold text-gray-900">{{ $onboardingProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-900 h-2 rounded-full transition-all duration-300"
                                style="width: {{ $onboardingProgress }}%"></div>
                        </div>
                        @php
                        $userType = auth()->user()->user_type ?? 'worker';
                        $profileEditRoute = match ($userType) {
                        'worker' => 'worker.profile',
                        'business' => 'settings.index',
                        'agency' => 'settings.index',
                        default => 'settings.index'
                        };
                        @endphp
                        @if(Route::has($profileEditRoute))
                        <a href="{{ route($profileEditRoute) }}"
                            class="inline-flex items-center gap-1 mt-2 text-sm font-medium text-gray-900 hover:text-gray-700">
                            Complete now
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        @endif
                </div>
                @endif
        </div>
        @endif

        <!-- Metrics Grid -->
        @if(isset($metrics) && count($metrics) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach($metrics as $metric)
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-600">{{ $metric['label'] }}</h3>
                    <div class="p-2 bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $metric['icon'] }}" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ $metric['value'] }}</p>
                @if(isset($metric['subtitle']) && $metric['subtitle'])
                <p class="text-sm text-gray-500 mt-2">{{ $metric['subtitle'] }}</p>
                @endif
                @if(isset($metric['trend']) && $metric['trend'])
                <div
                    class="mt-2 flex items-center gap-1 text-sm {{ $metric['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($metric['trend'] > 0)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        @endif
                    </svg>
                    <span class="font-medium">{{ abs($metric['trend']) }}%</span>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <!-- Flash Messages -->
        @include('partials.alerts')

        <!-- Page Content -->
        <div class="max-w-7xl">
            @yield('content')
        </div>
        </main>
    </div>
    </div>

    @stack('scripts')

    {{-- Inline scripts must include the CSP nonce attribute --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        // Mark body as loaded when Alpine initializes to prevent layout shift
        document.addEventListener('alpine:init', () => {
            document.body.classList.add('alpine-loaded');
        });

        // Fallback: mark as loaded after a short delay if Alpine is already initialized
        setTimeout(() => {
            document.body.classList.add('alpine-loaded');
        }, 100);
    </script>
</body>

</html>