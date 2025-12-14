<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="OvertimeStaff - Enterprise-grade shift marketplace platform connecting businesses with verified workers. AI-powered matching, instant payouts, and complete workforce management.">
    <title>OvertimeStaff - Enterprise Shift Marketplace Platform</title>

    <!-- Google Fonts - Inter for professional typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Tailwind Config - shadcn/ui style -->
    <script>
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

    <style>
        /* Button styles - shadcn */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.5rem;
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
            padding: 0.625rem 1.5rem;
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

        /* Card styles - shadcn */
        .card {
            background: white;
            border: 1px solid hsl(240 5.9% 90%);
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        /* Feature card */
        .feature-card {
            background: white;
            border: 1px solid hsl(240 5.9% 90%);
            border-radius: 0.5rem;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            border-color: hsl(240 5.9% 80%);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        /* Stats section background */
        .stats-section {
            background: hsl(240 5.9% 10%);
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-900" x-data="{ mobileMenuOpen: false }">

    <!-- Navigation -->
    <nav class="border-b bg-white sticky top-0 z-50" style="border-color: hsl(240 5.9% 90%);" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="/" class="flex items-center">
                    <img src="/images/logo.svg" alt="OvertimeStaff" class="h-8">
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="{{ route('register', ['type' => 'worker']) }}" class="text-sm font-medium transition-colors" style="color: hsl(240 3.8% 46.1%);">
                        Find Shifts
                    </a>
                    <a href="{{ route('register', ['type' => 'business']) }}" class="text-sm font-medium transition-colors" style="color: hsl(240 3.8% 46.1%);">
                        Find Staff
                    </a>
                </div>

                <!-- Right Side -->
                <div class="hidden lg:flex items-center space-x-4">
                    @guest
                        <a href="{{ route('login') }}" class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity" style="background: hsl(240 5.9% 10%);">
                            Get Started
                        </a>
                    @endguest
                    @auth
                        <a href="{{ auth()->user()->getDashboardRoute() }}" class="px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity" style="background: hsl(240 5.9% 10%);">
                            Dashboard
                        </a>
                    @endauth
                </div>

                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2" style="color: hsl(240 3.8% 46.1%);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" x-cloak class="lg:hidden py-4 space-y-2">
                <a href="{{ route('register', ['type' => 'worker']) }}" class="block px-4 py-2 text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                    Find Shifts
                </a>
                <a href="{{ route('register', ['type' => 'business']) }}" class="block px-4 py-2 text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                    Find Staff
                </a>
                <div class="border-t pt-2" style="border-color: hsl(240 5.9% 90%);">
                    @guest
                        <a href="{{ route('login') }}" class="block px-4 py-2 text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="block px-4 py-2 text-sm font-medium text-white rounded-lg mx-4" style="background: hsl(240 5.9% 10%);">
                            Get Started
                        </a>
                    @endguest
                    @auth
                        <a href="{{ auth()->user()->getDashboardRoute() }}" class="block px-4 py-2 text-sm font-medium text-white rounded-lg mx-4" style="background: hsl(240 5.9% 10%);">
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Column - Content -->
                <div class="space-y-8">
                    <!-- Badge -->
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border" style="background: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%); border-color: hsl(240 5.9% 90%);">
                            <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                            70+ countries
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium border" style="background: white; color: hsl(240 3.8% 46.1%); border-color: hsl(240 5.9% 90%);">
                            Always secure
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight leading-tight">
                        Work. Covered.
                    </h1>

                    <p class="text-lg md:text-xl leading-relaxed max-w-xl" style="color: hsl(240 3.8% 46.1%);">
                        When shifts break, the right people show up. Instantly.
                    </p>

                    <!-- Feature Pills -->
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm border" style="background: white; border-color: hsl(240 5.9% 90%);">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Instant pay
                        </span>
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm border" style="background: white; border-color: hsl(240 5.9% 90%);">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Verified workers
                        </span>
                        <span class="inline-flex items-center px-3 py-2 rounded-md text-sm border" style="background: white; border-color: hsl(240 5.9% 90%);">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Smart matching
                        </span>
                    </div>


                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t" style="border-color: hsl(240 5.9% 90%);">
                        <div>
                            <div class="text-3xl font-bold">2.3M+</div>
                            <div class="text-sm" style="color: hsl(240 3.8% 46.1%);">shifts</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold">98.7%</div>
                            <div class="text-sm" style="color: hsl(240 3.8% 46.1%);">filled</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold">15min</div>
                            <div class="text-sm" style="color: hsl(240 3.8% 46.1%);">to match</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Dashboard Preview Card -->
                <div class="lg:pl-8">
                    <div class="bg-white shadow-xl rounded-2xl border border-gray-200 p-8" x-data="{ formTab: 'business' }">
                        <!-- Tab Navigation -->
                        <div class="inline-flex items-center p-1 rounded-md mb-6" style="background: hsl(240 4.8% 95.9%);">
                            <button
                                @click="formTab = 'business'"
                                :class="formTab === 'business' ? 'bg-gray-900 text-white' : 'bg-transparent text-gray-600'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                            >
                                For Business
                            </button>
                            <button
                                @click="formTab = 'worker'"
                                :class="formTab === 'worker' ? 'bg-gray-900 text-white' : 'bg-transparent text-gray-600'"
                                class="px-4 py-2 text-sm font-medium rounded-md transition-all duration-200"
                            >
                                For Workers
                            </button>
                        </div>

                        <!-- Preview Content -->
                        <div class="space-y-4">
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-semibold mb-2" x-text="formTab === 'business' ? 'Post a shift.' : 'Find Your Next Shift'">Post a shift.</h3>
                                <p class="text-sm" style="color: hsl(240 3.8% 46.1%);" x-text="formTab === 'business' ? 'We\'ll handle the rest.' : 'Browse shifts and start earning today'">We'll handle the rest.</p>
                            </div>

                            <!-- Mock Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5" x-text="formTab === 'business' ? 'Job Title' : 'Your Skills'">Job Title</label>
                                    <input type="text" :placeholder="formTab === 'business' ? 'e.g., Event Server, Warehouse Associate' : 'e.g., Server, Bartender, Retail'" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5" x-text="formTab === 'business' ? 'Date' : 'Location'">Date</label>
                                        <input type="text" :placeholder="formTab === 'business' ? 'Select date' : 'City, State'" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5" x-text="formTab === 'business' ? 'Workers' : 'Availability'">Workers</label>
                                        <input type="text" :placeholder="formTab === 'business' ? '5' : 'Full-time'" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg transition-all duration-200 placeholder:text-gray-400 focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10 focus:outline-none">
                                    </div>
                                </div>
                                <a :href="formTab === 'business' ? '{{ route('register', ['type' => 'business']) }}' : '{{ route('register', ['type' => 'worker']) }}'" class="w-full h-12 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-lg inline-flex items-center justify-center transition-all duration-200">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Indicators Section -->
    <section class="py-16 bg-white border-y" style="border-color: hsl(240 5.9% 90%);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">Trusted worldwide.</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">500+</div>
                    <div class="text-sm text-gray-600 mt-1">Businesses</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">70+</div>
                    <div class="text-sm text-gray-600 mt-1">Countries</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">24/7</div>
                    <div class="text-sm text-gray-600 mt-1">Support</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-900">15min</div>
                    <div class="text-sm text-gray-600 mt-1">to match</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Shift Market Section -->
    <section id="live-market" class="py-20 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-4" style="background: hsl(240 4.8% 95.9%); color: hsl(240 5.9% 10%);">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                    LIVE
                </span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Open shifts. Right now.
                </h2>
                <p class="text-lg max-w-2xl mx-auto" style="color: hsl(240 3.8% 46.1%);">
                    See them. Claim them.
                </p>
            </div>

            {{-- Live Market Component --}}
            <x-live-shift-market variant="landing" :limit="6" />

            <div class="text-center mt-8">
                <a href="{{ route('register') }}" class="btn-primary text-base px-8 py-3">
                    Browse Shifts
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Built for work that can't wait.
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Hospitality -->
                <div class="group transition-all duration-200 hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 cursor-pointer bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center mb-4 transition-colors" style="background: hsl(240 4.8% 95.9%);">
                        <svg class="w-6 h-6 transition-colors group-hover:text-gray-900" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Hospitality</h3>
                </div>

                <!-- Healthcare -->
                <div class="group transition-all duration-200 hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 cursor-pointer bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center mb-4 transition-colors" style="background: hsl(240 4.8% 95.9%);">
                        <svg class="w-6 h-6 transition-colors group-hover:text-gray-900" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Healthcare</h3>
                </div>

                <!-- Retail -->
                <div class="group transition-all duration-200 hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 cursor-pointer bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center mb-4 transition-colors" style="background: hsl(240 4.8% 95.9%);">
                        <svg class="w-6 h-6 transition-colors group-hover:text-gray-900" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Retail</h3>
                </div>

                <!-- Logistics -->
                <div class="group transition-all duration-200 hover:shadow-lg hover:-translate-y-1 hover:border-gray-300 cursor-pointer bg-white border border-gray-200 rounded-lg p-6">
                    <div class="w-12 h-12 rounded-md flex items-center justify-center mb-4 transition-colors" style="background: hsl(240 4.8% 95.9%);">
                        <svg class="w-6 h-6 transition-colors group-hover:text-gray-900" style="color: hsl(240 3.8% 46.1%);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold">Logistics</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20" style="background: hsl(240 4.8% 95.9% / 0.5);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-4" style="background: white; color: hsl(240 5.9% 10%); border: 1px solid hsl(240 5.9% 90%);">
                    HOW IT WORKS
                </span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Post. Match. Work. Pay.
                </h2>
            </div>

            <div class="grid md:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-14 h-14 rounded-md flex items-center justify-center mx-auto mb-4" style="background: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                        <span class="text-xl font-bold">1</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">You post</h3>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-14 h-14 rounded-md flex items-center justify-center mx-auto mb-4" style="background: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                        <span class="text-xl font-bold">2</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">We match</h3>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-14 h-14 rounded-md flex items-center justify-center mx-auto mb-4" style="background: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                        <span class="text-xl font-bold">3</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">They show up</h3>
                </div>

                <!-- Step 4 -->
                <div class="text-center">
                    <div class="w-14 h-14 rounded-md flex items-center justify-center mx-auto mb-4" style="background: hsl(240 5.9% 10%); color: hsl(0 0% 98%);">
                        <span class="text-xl font-bold">4</span>
                    </div>
                    <h3 class="text-lg font-semibold mb-2">Everyone's paid</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Section -->
    <section id="security" class="py-20 stats-section text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium mb-4" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);">
                    SECURITY
                </span>
                <h2 class="text-3xl md:text-4xl font-bold mb-4">
                    Your data. Protected.
                </h2>
                <p class="text-lg max-w-2xl mx-auto opacity-80">
                    Encrypted. Audited. Compliant.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="p-6 rounded-lg" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="w-10 h-10 rounded-md flex items-center justify-center mb-4" style="background: rgba(255,255,255,0.1);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">ISO 27001</h3>
                </div>

                <div class="p-6 rounded-lg" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="w-10 h-10 rounded-md flex items-center justify-center mb-4" style="background: rgba(255,255,255,0.1);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">256-bit encrypted</h3>
                </div>

                <div class="p-6 rounded-lg" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="w-10 h-10 rounded-md flex items-center justify-center mb-4" style="background: rgba(255,255,255,0.1);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">GDPR ready</h3>
                </div>

                <div class="p-6 rounded-lg" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <div class="w-10 h-10 rounded-md flex items-center justify-center mb-4" style="background: rgba(255,255,255,0.1);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold mb-2">Every worker checked</h3>
                </div>
            </div>

            <!-- Compliance Logos -->
            <div class="flex flex-wrap justify-center items-center gap-8 pt-8 border-t" style="border-color: rgba(255,255,255,0.1);">
                <span class="text-sm font-medium opacity-50">SOC 2 Type II</span>
                <span class="text-sm font-medium opacity-50">PCI DSS</span>
                <span class="text-sm font-medium opacity-50">HIPAA</span>
                <span class="text-sm font-medium opacity-50">ISO 27001</span>
                <span class="text-sm font-medium opacity-50">GDPR</span>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="pricing" class="py-20 bg-gray-900 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">
                One shift.
            </h2>
            <p class="text-lg text-gray-300 mb-8">
                See the difference.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register', ['type' => 'business']) }}" class="px-8 py-4 text-base font-medium text-white bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors">
                    Find Staff
                </a>
                <a href="{{ route('register', ['type' => 'worker']) }}" class="px-8 py-4 text-base font-medium text-gray-900 bg-white rounded-lg hover:bg-gray-100 transition-colors">
                    Find Shifts
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t py-12" style="background: hsl(240 5.9% 10%); color: white;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-5 gap-8 mb-8">
                <!-- Logo Column -->
                <div class="md:col-span-2">
                    <a href="/" class="flex items-center gap-3 mb-4">
                        <img src="/images/logo-dark.svg" alt="OvertimeStaff" class="h-8">
                    </a>
                    <p class="text-sm opacity-60 leading-relaxed">Shifts covered. Globally.</p>
                </div>

                <!-- For Businesses -->
                <div>
                    <h3 class="font-semibold mb-4 text-sm">For Businesses</h3>
                    <ul class="space-y-2 text-sm opacity-60">
                        <li><a href="{{ route('register', ['type' => 'business']) }}" class="hover:opacity-100 transition-opacity">Post Shifts</a></li>
                        <li><a href="{{ route('register', ['type' => 'business']) }}" class="hover:opacity-100 transition-opacity">Find Workers</a></li>
                        <li><a href="/pricing" class="hover:opacity-100 transition-opacity">Pricing</a></li>
                        <li><a href="/enterprise" class="hover:opacity-100 transition-opacity">Enterprise</a></li>
                    </ul>
                </div>

                <!-- For Workers -->
                <div>
                    <h3 class="font-semibold mb-4 text-sm">For Workers</h3>
                    <ul class="space-y-2 text-sm opacity-60">
                        <li><a href="{{ route('register', ['type' => 'worker']) }}" class="hover:opacity-100 transition-opacity">Find Shifts</a></li>
                        <li><a href="{{ route('register', ['type' => 'worker']) }}" class="hover:opacity-100 transition-opacity">Get Verified</a></li>
                        <li><a href="/features#payouts" class="hover:opacity-100 transition-opacity">Instant Payouts</a></li>
                        <li><a href="/app" class="hover:opacity-100 transition-opacity">Mobile App</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="font-semibold mb-4 text-sm">Company</h3>
                    <ul class="space-y-2 text-sm opacity-60">
                        <li><a href="/about" class="hover:opacity-100 transition-opacity">About Us</a></li>
                        <li><a href="/contact" class="hover:opacity-100 transition-opacity">Contact</a></li>
                        <li><a href="/careers" class="hover:opacity-100 transition-opacity">Careers</a></li>
                        <li><a href="/security" class="hover:opacity-100 transition-opacity">Security</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t pt-8 flex flex-col md:flex-row justify-between items-center text-sm opacity-60" style="border-color: rgba(255,255,255,0.1);">
                <p>&copy; {{ date('Y') }} OvertimeStaff. All rights reserved.</p>
                <div class="flex gap-6 mt-4 md:mt-0">
                    <a href="/terms" class="hover:opacity-100">Terms</a>
                    <a href="/privacy" class="hover:opacity-100">Privacy</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
