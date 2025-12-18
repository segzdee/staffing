<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $config->brand_name }} - White-Label Preview</title>
    <link rel="icon" href="{{ $config->getFaviconUrlOrDefault() }}">

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- White-Label Styles --}}
    <style>
    {!! $customCss !!}
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    {{-- Preview Banner --}}
    <div class="bg-yellow-500 text-yellow-900 text-center py-2 text-sm font-medium">
        Preview Mode - This is how your white-label portal will look to workers
    </div>

    {{-- Header --}}
    <header class="bg-white border-b border-gray-100">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    <a href="#" class="flex items-center gap-2 group">
                        @if($config->logo_url)
                            <img src="{{ $config->logo_url }}" alt="{{ $config->brand_name }}" class="h-9 w-auto">
                        @else
                            <span class="text-xl font-bold wl-text-primary" style="color: {{ $config->primary_color }}">
                                {{ $config->brand_name }}
                            </span>
                        @endif
                    </a>
                </div>

                {{-- Nav Links --}}
                <div class="hidden lg:flex items-center gap-6">
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition-colors">Find Shifts</a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition-colors">My Shifts</a>
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition-colors">Profile</a>
                </div>

                {{-- Auth --}}
                <div class="hidden lg:flex items-center gap-3">
                    <a href="#" class="text-gray-700 hover:text-gray-900 transition-colors text-sm font-medium">Sign In</a>
                    <a href="#" class="px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors wl-bg-primary"
                        style="background-color: {{ $config->primary_color }}">
                        Get Started
                    </a>
                </div>
            </div>
        </nav>
    </header>

    {{-- Hero Section --}}
    <section class="py-20 wl-bg-primary" style="background-color: {{ $config->primary_color }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                Find Flexible Work with {{ $config->brand_name }}
            </h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto mb-8">
                Browse available shifts, apply with one click, and get paid fast. Join our community of workers today.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#" class="px-8 py-4 bg-white font-semibold rounded-lg transition-colors wl-text-primary"
                    style="color: {{ $config->primary_color }}">
                    Browse Shifts
                </a>
                <a href="#" class="px-8 py-4 bg-white/20 text-white font-semibold rounded-lg transition-colors hover:bg-white/30 border border-white/30">
                    Create Account
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Why Work With Us?</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center wl-bg-primary"
                        style="background-color: {{ $config->primary_color }}20">
                        <svg class="w-8 h-8 wl-text-primary" style="color: {{ $config->primary_color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Competitive Pay</h3>
                    <p class="text-gray-600">Earn competitive hourly rates with fast, reliable payments directly to your account.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
                        style="background-color: {{ $config->secondary_color }}20">
                        <svg class="w-8 h-8" style="color: {{ $config->secondary_color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Flexible Hours</h3>
                    <p class="text-gray-600">Choose shifts that fit your schedule. Work when you want, where you want.</p>
                </div>

                <div class="text-center p-6">
                    <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
                        style="background-color: {{ $config->accent_color }}20">
                        <svg class="w-8 h-8" style="color: {{ $config->accent_color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Trusted Platform</h3>
                    <p class="text-gray-600">Work with verified businesses and enjoy the security of our escrow payment system.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 bg-gray-100">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Start Working?</h2>
            <p class="text-xl text-gray-600 mb-8">Create your free account and start browsing available shifts in your area.</p>
            <a href="#" class="inline-block px-8 py-4 text-white font-semibold rounded-lg transition-colors wl-bg-primary"
                style="background-color: {{ $config->primary_color }}">
                Create Free Account
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white text-xl font-bold mb-4">{{ $config->brand_name }}</h3>
                    <p class="text-gray-400 mb-4">Your trusted partner for flexible work opportunities.</p>
                    @if($config->support_email)
                        <a href="mailto:{{ $config->support_email }}" class="text-gray-400 hover:text-white transition-colors">
                            {{ $config->support_email }}
                        </a>
                    @endif
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Find Shifts</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Sign In</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Support</h4>
                    <ul class="space-y-2">
                        @if($config->support_email)
                            <li><a href="mailto:{{ $config->support_email }}" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                        @endif
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-sm">
                    &copy; {{ date('Y') }} {{ $config->brand_name }}. All rights reserved.
                </p>
                @if(!$config->hide_powered_by)
                    <p class="text-gray-500 text-sm">
                        Powered by <a href="https://overtimestaff.com" target="_blank" class="hover:text-white transition-colors" style="color: {{ $config->primary_color }}">OvertimeStaff</a>
                    </p>
                @endif
            </div>
        </div>
    </footer>

    {{-- Close Preview Button --}}
    <div class="fixed bottom-6 right-6">
        <a href="{{ route('agency.white-label.index') }}"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 text-white font-medium rounded-lg shadow-lg hover:bg-gray-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Close Preview
        </a>
    </div>
</body>
</html>
