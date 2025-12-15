<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing | OvertimeStaff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<body class="font-sans" x-data="{ mobileMenuOpen: false }">
    <!-- Navigation -->
    @include('partials.public-navbar')

    <!-- Hero -->
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Simple, Transparent Pricing</h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                Pay only for what you use. No hidden fees, no long-term contracts.
            </p>
        </div>
    </div>

    <!-- Pricing Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
            <!-- Workers -->
            <div class="bg-white rounded-xl border-2 border-gray-200 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">For Workers</h2>
                    <p class="text-gray-600">Find flexible work opportunities</p>
                </div>
                <div class="text-center mb-8">
                    <div class="text-5xl font-bold text-gray-900 mb-2">Free</div>
                    <p class="text-gray-600">Always free to browse and apply</p>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Unlimited shift applications</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Instant payouts (15 min after shift completion)</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Profile and badge system</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Calendar and availability management</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">GPS clock-in/out verification</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">24/7 support</span>
                    </li>
                </ul>
                <a href="{{ route('register', ['type' => 'worker']) }}" class="block w-full text-center px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-semibold">
                    Sign Up as Worker
                </a>
            </div>

            <!-- Businesses -->
            <div class="bg-white rounded-xl border-2 border-gray-900 p-8 relative">
                <div class="absolute top-0 right-0 bg-gray-900 text-white px-4 py-1 rounded-bl-lg rounded-tr-lg text-sm font-semibold">
                    Most Popular
                </div>
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">For Businesses</h2>
                    <p class="text-gray-600">Fill shifts in minutes</p>
                </div>
                <div class="text-center mb-8">
                    <div class="text-5xl font-bold text-gray-900 mb-2">8%</div>
                    <p class="text-gray-600">Per completed shift hour</p>
                </div>
                <ul class="space-y-4 mb-8">
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Unlimited shift postings</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Access to verified workers</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Real-time shift matching</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Shift templates and recurring shifts</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">GPS-verified time tracking</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Analytics and reporting</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-gray-700">Priority support</span>
                    </li>
                </ul>
                <a href="{{ route('register', ['type' => 'business']) }}" class="block w-full text-center px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-semibold">
                    Sign Up as Business
                </a>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-20 max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Frequently Asked Questions</h2>
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">How does payment work?</h3>
                    <p class="text-gray-600">Workers receive instant payouts 15 minutes after shift completion via Stripe Connect. Businesses are charged the hourly rate plus our 8% service fee, which covers payment processing, insurance, and platform features.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Are there any setup fees?</h3>
                    <p class="text-gray-600">No setup fees, no monthly subscriptions, and no long-term contracts. You only pay when shifts are completed.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">What about background checks?</h3>
                    <p class="text-gray-600">All workers undergo identity verification and background checks. Additional certifications and skills are verified through our badge system. These costs are covered by our service fee.</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Can I cancel a shift?</h3>
                    <p class="text-gray-600">Yes, shifts can be cancelled up to 24 hours before start time without penalty. Late cancellations may incur fees as outlined in our terms of service.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="bg-gray-900 text-white py-20">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold mb-4">Ready to Get Started?</h2>
            <p class="text-xl text-gray-400 mb-8">Join thousands of businesses and workers on OvertimeStaff.</p>
            <a href="{{ route('register') }}" class="inline-block px-8 py-4 bg-white text-gray-900 rounded-lg hover:bg-gray-100 text-lg font-semibold">
                Create Free Account
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="font-bold text-lg mb-4"><span class="text-white">Over</span><span class="text-blue-500">TIME</span><span class="text-white">Staff</span></h3>
                    <p class="text-gray-400 text-sm">Enterprise shift marketplace connecting businesses with verified workers worldwide.</p>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">Product</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('features') }}" class="hover:text-white">Features</a></li>
                        <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Get Started</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">Company</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('about') }}" class="hover:text-white">About</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-white">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('terms') }}" class="hover:text-white">Terms</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-white">Privacy</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-gray-400 text-sm">
                <p>&copy; {{ date('Y') }} OvertimeStaff. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
