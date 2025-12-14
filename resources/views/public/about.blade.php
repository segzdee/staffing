<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | OvertimeStaff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#FFF7ED',
                            100: '#FFEDD5',
                            200: '#FED7AA',
                            300: '#FDBA74',
                            400: '#FB923C',
                            500: '#F97316',
                            600: '#EA580C',
                            700: '#C2410C',
                            800: '#9A3412',
                            900: '#7C2D12',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-bold text-gray-900">OvertimeStaff</a>
                </div>
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-gray-600 hover:text-gray-900">Home</a>
                    <a href="/features" class="text-gray-600 hover:text-gray-900">Features</a>
                    <a href="/pricing" class="text-gray-600 hover:text-gray-900">Pricing</a>
                    <a href="/about" class="text-gray-900 font-medium">About</a>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Sign In</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">Get Started</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="bg-gradient-to-br from-brand-500 to-brand-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-6">About OvertimeStaff</h1>
            <p class="text-xl text-brand-100 max-w-3xl mx-auto">
                We're revolutionizing how businesses find flexible workers and how workers find meaningful opportunities.
            </p>
        </div>
    </div>

    <!-- Mission -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Mission</h2>
            <p class="text-xl text-gray-600 leading-relaxed">
                To create a transparent, efficient marketplace that connects businesses with skilled workers for short-term and flexible staffing needs. We believe in fair compensation, instant payments, and empowering both workers and businesses with technology.
            </p>
        </div>

        <!-- Values Grid -->
        <div class="grid md:grid-cols-3 gap-8 mb-20">
            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Speed</h3>
                <p class="text-gray-600">Fill shifts in minutes, not days. Workers get paid instantly after completion.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Trust</h3>
                <p class="text-gray-600">All workers are verified and background-checked for your peace of mind.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-brand-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Fairness</h3>
                <p class="text-gray-600">Transparent pricing, no hidden fees, and fair compensation for all parties.</p>
            </div>
        </div>

        <!-- Story Section -->
        <div class="bg-gray-50 rounded-2xl p-12 mb-20">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Our Story</h2>
                <div class="prose prose-lg text-gray-600 space-y-4">
                    <p>
                        OvertimeStaff was founded in 2024 by a team of hospitality and technology veterans who experienced firsthand the challenges of last-minute staffing needs and the frustrations workers face with traditional staffing agencies.
                    </p>
                    <p>
                        Traditional staffing agencies often take weeks to fill positions, charge exorbitant fees, and delay worker payments for days or weeks. We knew there had to be a better way.
                    </p>
                    <p>
                        By combining modern technology with fair marketplace principles, we created a platform that benefits everyone. Businesses get qualified workers within minutes, workers get instant payment after shifts, and our transparent pricing means no surprises.
                    </p>
                    <p>
                        Today, we're proud to serve thousands of businesses and workers across multiple industries, from hospitality and retail to warehousing and events. Our mission remains the same: make flexible staffing simple, fast, and fair.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid md:grid-cols-4 gap-8 mb-20">
            <div class="text-center">
                <div class="text-4xl font-bold text-brand-600 mb-2">10,000+</div>
                <p class="text-gray-600">Active Workers</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-brand-600 mb-2">2,500+</div>
                <p class="text-gray-600">Business Partners</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-brand-600 mb-2">100,000+</div>
                <p class="text-gray-600">Shifts Completed</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-brand-600 mb-2">15 min</div>
                <p class="text-gray-600">Average Payout Time</p>
            </div>
        </div>

        <!-- Team Section -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Team</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                We're a diverse team of engineers, operators, and industry experts passionate about transforming the staffing industry.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Sarah Chen</h3>
                <p class="text-brand-600 mb-3">CEO & Co-Founder</p>
                <p class="text-gray-600 text-sm">Former VP of Operations at a leading hospitality group</p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Marcus Johnson</h3>
                <p class="text-brand-600 mb-3">CTO & Co-Founder</p>
                <p class="text-gray-600 text-sm">Previously led engineering at two marketplace unicorns</p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Priya Patel</h3>
                <p class="text-brand-600 mb-3">Head of Product</p>
                <p class="text-gray-600 text-sm">10+ years building worker-focused products at tech companies</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="bg-gray-900 text-white py-20">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold mb-4">Join Our Growing Community</h2>
            <p class="text-xl text-gray-400 mb-8">Whether you're looking for work or looking to hire, we're here to help.</p>
            <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-lg font-semibold">
                Get Started Today
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-600">
            <p>&copy; 2025 OvertimeStaff. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
