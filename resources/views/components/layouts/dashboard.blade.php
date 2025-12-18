@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ sidebarOpen: false, mobileMenuOpen: false }"
    x-init="sidebarOpen = window.innerWidth >= 1024">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style nonce="{{ $cspNonce ?? '' }}">
        [x-cloak] {
            display: none !important;
        }

        body {
            visibility: hidden;
        }

        body.alpine-loaded {
            visibility: visible;
        }

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
                <!-- Page Title -->
                @if(isset($title) && $title)
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
                </div>
                @endif

                <!-- Flash Messages -->
                @include('partials.alerts')

                <!-- Page Content -->
                <div class="max-w-7xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')

    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('alpine:init', () => {
            document.body.classList.add('alpine-loaded');
        });

        setTimeout(() => {
            document.body.classList.add('alpine-loaded');
        }, 100);
    </script>
</body>

</html>
