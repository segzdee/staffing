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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

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
        [x-cloak] {
            display: none !important;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-background text-foreground font-sans antialiased">
    {{-- Split Screen Container --}}
    <div class="flex min-h-screen">
        {{-- Left Panel - Brand --}}
        <div class="hidden lg:flex lg:w-1/2 relative bg-slate-900 overflow-hidden">
            {{-- Professional Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/90 to-slate-900 mix-blend-multiply z-0"></div>

            {{-- Abstract Pattern --}}
            <svg class="absolute inset-0 w-full h-full opacity-10 z-0" viewBox="0 0 100 100" preserveAspectRatio="none">
                <pattern id="grid-pattern" width="8" height="8" patternUnits="userSpaceOnUse">
                    <path d="M0 8L8 0" stroke="white" stroke-width="0.5" />
                </pattern>
                <rect width="100" height="100" fill="url(#grid-pattern)" />
            </svg>

            {{-- Decorative Elements --}}
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-80 h-80 rounded-full bg-blue-500/20 blur-3xl z-0"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl z-0">
            </div>

            {{-- Logo --}}
            <div class="absolute top-10 left-10 z-10">
                <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                    <div
                        class="w-10 h-10 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/20 transition-all duration-300 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl tracking-tight drop-shadow-sm">OvertimeStaff</span>
                </a>
            </div>

            {{-- Brand Message (Dynamic) --}}
            <div class="absolute bottom-0 left-0 right-0 p-12 z-10 bg-gradient-to-t from-slate-900/80 to-transparent">
                <blockquote class="max-w-md">
                    <h1 class="text-3xl md:text-4xl font-bold text-white mb-4 leading-tight tracking-tight"
                        data-brand-headline>
                        @yield('brand-headline', 'Work. Covered.')
                    </h1>
                    <p class="text-lg text-slate-300 leading-relaxed font-light" data-brand-subtext>
                        @yield('brand-subtext', 'When shifts break, the right people show up.')
                    </p>
                </blockquote>
            </div>
        </div>

        {{-- Right Panel - Form --}}
        <div class="w-full lg:w-1/2 flex flex-col min-h-screen bg-background relative">
            {{-- Mobile Logo (Visible only on small screens) --}}
            <div class="lg:hidden absolute top-6 left-6 z-20">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-foreground" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="font-bold text-lg text-foreground">OvertimeStaff</span>
                </a>
            </div>
            <div class="flex-1 flex items-center justify-center p-8">
                <div class="w-full max-w-md space-y-8">
                    @yield('form')
                </div>
            </div>

            {{-- Auth Footer --}}
            <footer
                class="py-6 border-t border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="container flex flex-col items-center justify-center gap-4 md:h-12 md:flex-row">
                    <p class="text-xs text-muted-foreground text-center">
                        &copy; {{ date('Y') }} OvertimeStaff. All rights reserved.
                    </p>
                    <nav class="flex gap-4 sm:gap-6">
                        <a href="{{ route('terms') }}"
                            class="text-xs font-medium text-muted-foreground underline-offset-4 hover:text-primary hover:underline transition-colors">
                            Terms
                        </a>
                        <a href="{{ route('privacy') }}"
                            class="text-xs font-medium text-muted-foreground underline-offset-4 hover:text-primary hover:underline transition-colors">
                            Privacy
                        </a>
                        <a href="{{ route('contact') }}"
                            class="text-xs font-medium text-muted-foreground underline-offset-4 hover:text-primary hover:underline transition-colors">
                            Help
                        </a>
                    </nav>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>

</html>