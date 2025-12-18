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
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
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
            @include('auth.partials.brand-panel', [
                'brandHeading' => trim($__env->yieldContent('brand-headline', 'Work. Covered.')),
                'brandSubheading' => trim($__env->yieldContent('brand-subtext', 'When shifts break, the right people show up.'))
            ])
        </div>

        {{-- Right Panel - Form --}}
        <div class="w-full lg:w-1/2 flex flex-col min-h-screen bg-background relative">
            {{-- Mobile Logo (Visible only on small screens) --}}
            <div class="lg:hidden absolute top-6 left-6 z-20">
                <a href="{{ url('/') }}" class="flex items-center gap-2">
                    <x-logo class="h-8 w-auto" />
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
                        <a href="{{ route('privacy.settings') }}"
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