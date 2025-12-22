{{--
Marketing Layout - Clean, focused layout for public/marketing pages
Global Design System v4.0
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="@yield('meta_description', 'OvertimeStaff - The global staffing marketplace connecting businesses with verified workers')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <meta name="keywords"
        content="@yield('keywords', 'staffing, shift marketplace, on-demand workers, temporary staffing, gig work, instant pay')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <title>@yield('title', 'OvertimeStaff - Global Staffing Marketplace')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    {{-- Open Graph / Facebook --}}
    @php
        $ogTitle = $__env->yieldContent('og_title') ?: (config('app.name') . ' - Global Staffing Marketplace');
        $ogDescription = $__env->yieldContent('og_description') ?: $__env->yieldContent('meta_description') ?: 'Connect with verified workers instantly. OvertimeStaff is the global staffing marketplace trusted by 500+ businesses in 70+ countries.';
        $ogUrl = $__env->yieldContent('og_url') ?: url()->current();
        $ogImage = $__env->yieldContent('og_image') ?: asset('images/og-image.jpg');
        $twitterTitle = $__env->yieldContent('twitter_title') ?: $ogTitle;
        $twitterDescription = $__env->yieldContent('twitter_description') ?: $ogDescription;
        $twitterImage = $__env->yieldContent('twitter_image') ?: asset('images/twitter-card.jpg');
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:site_name" content="OvertimeStaff">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $ogUrl }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    <meta name="twitter:image" content="{{ $twitterImage }}">

    {{-- Structured Data (JSON-LD) --}}
    @php
        $schemaDescription = $__env->yieldContent('meta_description') ?: 'The global staffing marketplace connecting businesses with verified workers';
    @endphp
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "OvertimeStaff",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('images/logo.svg') }}",
        "description": "{{ $schemaDescription }}",
        "sameAs": [
            "https://twitter.com/overtimestaff",
            "https://facebook.com/overtimestaff",
            "https://linkedin.com/company/overtimestaff"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "email": "support@overtimestaff.com",
            "contactType": "Customer Service"
        }
    }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Icon Fonts (for legacy compatibility) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-feature-settings: "rlig" 1, "calt" 1;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-background text-foreground font-sans antialiased min-h-screen flex flex-col" x-data="{ loaded: false }"
    x-init="loaded = true">

    <!-- Global Header -->
    <x-global-header :transparent="$transparentHeader ?? false" />

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Global Footer -->
    <x-global-footer />

    @stack('scripts')
</body>

</html>