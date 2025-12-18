<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OvertimeStaff - Enterprise-grade shift marketplace platform">

    <title>@yield('title', 'OvertimeStaff')</title>

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'OvertimeStaff')">
    <meta property="og:description" content="@yield('meta_description', 'Enterprise-grade shift marketplace platform')">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:site_name" content="OvertimeStaff">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'OvertimeStaff')">
    <meta name="twitter:description"
        content="@yield('meta_description', 'Enterprise-grade shift marketplace platform')">
    <meta name="twitter:image" content="{{ asset('images/twitter-card.jpg') }}">

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

    <!-- Icon Fonts (for legacy compatibility) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
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

<body class="bg-background text-foreground font-sans antialiased min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Clean Navigation -->
        @include('components.clean-navbar')

        <!-- Main Content -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <x-global-footer class="bg-white border-t py-6" />
    </div>

    @stack('scripts')
</body>

</html>