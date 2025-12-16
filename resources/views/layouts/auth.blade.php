<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OvertimeStaff - Enterprise-grade shift marketplace platform">

    <title>@yield('title', 'OvertimeStaff')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Fallback to Tailwind CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script nonce="{{ $cspNonce ?? '' }}">
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Inter', 'system-ui', 'sans-serif'],
                        },
                    },
                },
            }
        </script>
    @endif

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- CSP Nonce: All inline styles and scripts must include the nonce attribute --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-white text-gray-900 font-sans antialiased">
    {{-- Split Screen Container --}}
    <div class="flex min-h-screen">
        {{-- Left Panel - Brand --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-[#2563eb] to-[#1d4ed8] relative overflow-hidden">
            {{-- Logo --}}
            <div class="absolute top-8 left-8 z-10">
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl">OVERTIMESTAFF</span>
                </a>
            </div>
            
            {{-- Decorative Circles --}}
            <div class="absolute top-1/4 right-1/4 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="absolute bottom-1/4 left-1/4 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="absolute top-1/2 right-1/3 w-16 h-16 bg-white/10 rounded-full"></div>
            
            {{-- Brand Message (Dynamic) --}}
            <div class="absolute bottom-16 left-8 right-8 z-10">
                <h1 class="text-3xl font-bold text-white mb-2" data-brand-headline>
                    @yield('brand-headline', 'Work. Covered.')
                </h1>
                <p class="text-white/70 text-base" data-brand-subtext>
                    @yield('brand-subtext', 'When shifts break, the right people show up.')
                </p>
            </div>
        </div>
        
        {{-- Right Panel - Form --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                @yield('form')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
