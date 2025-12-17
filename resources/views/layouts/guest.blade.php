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
                        colors: {
                            border: 'hsl(240 5.9% 90%)',
                            input: 'hsl(240 5.9% 90%)',
                            ring: 'hsl(240 5.9% 10%)',
                            background: 'hsl(0 0% 100%)',
                            foreground: 'hsl(240 10% 3.9%)',
                            primary: {
                                DEFAULT: 'hsl(240 5.9% 10%)',
                                foreground: 'hsl(0 0% 98%)',
                            },
                            secondary: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 5.9% 10%)',
                            },
                            destructive: {
                                DEFAULT: 'hsl(0 84.2% 60.2%)',
                                foreground: 'hsl(0 0% 98%)',
                            },
                            muted: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 3.8% 46.1%)',
                            },
                            accent: {
                                DEFAULT: 'hsl(240 4.8% 95.9%)',
                                foreground: 'hsl(240 5.9% 10%)',
                            },
                            success: '#10B981',
                            warning: '#F59E0B',
                            error: '#EF4444',
                            info: '#3B82F6',
                        },
                        borderRadius: {
                            lg: '0.5rem',
                            md: '0.375rem',
                            sm: '0.25rem',
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

        /* Button styles - shadcn */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: hsl(0 0% 98%);
            background: hsl(240 5.9% 10%);
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: hsl(240 5.9% 10% / 0.9);
            color: hsl(0 0% 98%);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: hsl(240 5.9% 10%);
            background: transparent;
            border: 1px solid hsl(240 5.9% 90%);
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: hsl(240 4.8% 95.9%);
            color: hsl(240 5.9% 10%);
        }
    </style>

    @stack('styles')
</head>

<body class="bg-muted/30 text-foreground font-sans antialiased min-h-screen"
    style="background-color: hsl(240 4.8% 95.9% / 0.3);">
    <div class="min-h-screen flex flex-col">
        <!-- Clean Navigation -->
        @include('components.clean-navbar')

        <!-- Main Content -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t py-6" style="border-color: hsl(240 5.9% 90%);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                    <p class="text-sm" style="color: hsl(240 3.8% 46.1%);">
                        &copy; {{ date('Y') }} OvertimeStaff. All rights reserved.
                    </p>
                    <div class="flex items-center space-x-6">
                        <a href="#" class="text-sm transition-colors"
                            style="color: hsl(240 3.8% 46.1%);">Features</a>
                        <a href="{{ route('business.pricing') }}" class="text-sm transition-colors"
                            style="color: hsl(240 3.8% 46.1%);">Pricing</a>
                        <a href="#" class="text-sm transition-colors"
                            style="color: hsl(240 3.8% 46.1%);">About</a>
                        <a href="{{ route('privacy') }}" class="text-sm transition-colors"
                            style="color: hsl(240 3.8% 46.1%);">Privacy</a>
                        <a href="{{ route('terms') }}" class="text-sm transition-colors"
                            style="color: hsl(240 3.8% 46.1%);">Terms</a>
                        <a href="#" class="text-sm transition-colors" style="color: hsl(240 3.8% 46.1%);">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>

</html>