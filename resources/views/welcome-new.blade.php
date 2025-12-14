<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="OVERTIMESTAFF - The Shift Market Platform that revolutionizes how companies find temporary workers across 70 countries.">
    <title>OVERTIMESTAFF - You Don't Find Workers. They Find You.</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-purple': '#667eea',
                        'brand-teal': '#0ea5e9',
                        'brand-green': '#10b981',
                    },
                    animation: {
                        'ticker': 'ticker 30s linear infinite',
                        'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
                    },
                    keyframes: {
                        ticker: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-50%)' },
                        },
                        'pulse-glow': {
                            '0%, 100%': { opacity: '1', boxShadow: '0 0 5px rgba(239, 68, 68, 0.5)' },
                            '50%': { opacity: '0.7', boxShadow: '0 0 20px rgba(239, 68, 68, 0.8)' },
                        },
                    },
                },
            },
        }
    </script>

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #667eea 0%, #0ea5e9 50%, #10b981 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="font-sans antialiased">

    <!-- Hero Section -->
    <section class="hero-gradient min-h-screen relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-1/2 -right-1/2 w-full h-full bg-white opacity-5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-1/2 -left-1/2 w-full h-full bg-white opacity-5 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 py-8 relative z-10">
            <!-- Navigation -->
            <nav class="flex items-center justify-between mb-12" role="navigation" aria-label="Main navigation">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center font-bold text-brand-purple">
                        OS
                    </div>
                    <div class="text-white">
                        <div class="font-bold text-xl">OVERTIMESTAFF</div>
                        <div class="text-xs opacity-90">The Shift Market Platform™</div>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-6 text-white">
                    <a href="#how-it-works" class="hover:opacity-80 transition-opacity">How It Works</a>
                    <a href="#industries" class="hover:opacity-80 transition-opacity">Industries</a>
                    <a href="#pricing" class="hover:opacity-80 transition-opacity">Pricing</a>
                    <button class="px-4 py-2 bg-white text-brand-purple rounded-lg font-semibold hover:shadow-lg transition-shadow">
                        Get Started
                    </button>
                </div>
            </nav>

            <!-- Hero Content -->
            <div class="grid lg:grid-cols-2 gap-8 items-center min-h-[80vh]">
                <!-- Left Column - Headline -->
                <div class="text-white space-y-6" data-aos="fade-right">
                    <div class="inline-flex items-center space-x-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm">
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span>Live in 70 countries</span>
                    </div>

                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
                        You Don't Find Workers.
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-green-200">
                            They Find You.
                        </span>
                    </h1>

                    <p class="text-xl md:text-2xl opacity-90 leading-relaxed">
                        AI-powered shift marketplace connecting businesses with qualified workers in
                        <span class="font-bold">15 minutes</span>. Post once, get matched instantly.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-6 h-6 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Instant payouts</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-6 h-6 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Verified workers</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-6 h-6 text-green-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>AI matching</span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 pt-8">
                        <div class="text-center">
                            <div class="text-3xl font-bold">50K+</div>
                            <div class="text-sm opacity-80">Active Workers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold">12K+</div>
                            <div class="text-sm opacity-80">Companies</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold">98%</div>
                            <div class="text-sm opacity-80">Fill Rate</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Auth Card -->
                <div class="flex justify-center lg:justify-end" data-aos="fade-left">
                    @include('components.auth-card')
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    <!-- Live Shift Market Section -->
    <section id="industries" class="py-16 bg-gray-50">
        @livewire('live-shift-market')
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    How It Works
                </h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    From posting to payment in 4 simple steps
                </p>
            </div>

            @include('components.how-it-works')
        </div>
    </section>

    <!-- Trust Section -->
    <section class="py-16 bg-gradient-to-r from-brand-purple to-brand-teal">
        <div class="container mx-auto px-4">
            <div class="text-center text-white space-y-8">
                <h2 class="text-3xl md:text-4xl font-bold">
                    Trusted by Industry Leaders
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 items-center opacity-80">
                    <div class="text-2xl font-bold">Hilton</div>
                    <div class="text-2xl font-bold">Marriott</div>
                    <div class="text-2xl font-bold">Amazon</div>
                    <div class="text-2xl font-bold">Walmart</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gray-900 text-white">
        <div class="container mx-auto px-4 text-center space-y-8">
            <h2 class="text-4xl md:text-5xl font-bold">
                Ready to Transform Your Hiring?
            </h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Join thousands of businesses finding qualified workers instantly
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <button class="px-8 py-4 bg-brand-green text-white rounded-lg font-semibold text-lg hover:bg-green-600 transition-colors transform hover:scale-105">
                    Post Your First Shift
                </button>
                <button class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-semibold text-lg hover:bg-white hover:text-gray-900 transition-all">
                    Find Work Now
                </button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 border-t border-gray-800">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="text-white font-bold text-lg mb-4">OVERTIMESTAFF</div>
                    <p class="text-sm">The Shift Market Platform revolutionizing temporary work across 70 countries.</p>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">For Businesses</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Post Shifts</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Find Workers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Pricing</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">For Workers</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Find Shifts</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Get Verified</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Instant Payouts</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold mb-4">Company</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; 2025 OVERTIMESTAFF. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
