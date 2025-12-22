<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OvertimeStaff - Enterprise-grade global shift marketplace platform">

    <title>@yield('title', config('app.name', 'OvertimeStaff'))</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

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
        {{-- CSP Nonce: Inline scripts must include the nonce attribute for security --}}
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{--
    ================================================================================
    CSP NONCE USAGE GUIDE
    ================================================================================

    The $cspNonce variable is automatically shared with all views by the
    ContentSecurityPolicy middleware. You MUST add this nonce to all inline
    scripts and styles for them to work when CSP is enforced.

    INLINE STYLES:
    <style nonce="{{ $cspNonce ?? '' }}">
        .my-class { color: red; }
    </style>

    INLINE SCRIPTS:
    <script nonce="{{ $cspNonce ?? '' }}">
        console.log('This script will execute because it has a valid nonce');
    </script>

    EVENT HANDLERS (will NOT work with strict CSP):
    <button onclick="doSomething()">Click</button>  // BAD - blocked by CSP

    Instead, use Alpine.js or attach handlers via JavaScript:
    <button x-on:click="doSomething()">Click</button>  // GOOD - Alpine.js
    <button id="myBtn">Click</button>  // GOOD - attach listener in script

    EXTERNAL SCRIPTS (no nonce needed for allowed domains):
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    ================================================================================
    --}}

    {{-- CSP Nonce: All inline styles must include the nonce attribute --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        [x-cloak] { display: none !important; }

        /* Base button styles */
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

        /* Prevent horizontal scroll on mobile */
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }

        /* Safe area insets for notched devices (iPhone X+, etc.) */
        @supports (padding: max(0px)) {
            .fixed.bottom-0 {
                padding-bottom: max(0px, env(safe-area-inset-bottom));
            }
            .fixed.top-0 {
                padding-top: max(0px, env(safe-area-inset-top));
            }
        }

        /* Mobile touch target minimum size (44px as per WCAG) */
        @media (max-width: 1023px) {
            a, button {
                min-height: 44px;
            }
        }

        /* Smooth scrolling for mobile */
        @media (max-width: 1023px) {
            html {
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-foreground font-sans antialiased min-h-screen">
    {{-- Main application content --}}
    @yield('content')

    @stack('scripts')

    {{-- Example: Inline script with CSP nonce --}}
    {{-- Uncomment and modify as needed:
    <script nonce="{{ $cspNonce ?? '' }}">
        // Your inline JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded with CSP nonce protection');
        });
    </script>
    --}}
</body>
</html>
