@extends('layouts.marketing')

@section('title', 'About Us | OvertimeStaff')
@section('meta_description', 'Learn about OvertimeStaff, our mission to connect businesses with verified workers, and our commitment to fair, instant payments.')

@section('content')
    <!-- Hero -->
    <section class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-6">The Global Shift Marketplace</h1>
            <p class="text-xl text-gray-300 max-w-3xl mx-auto">
                We keep work moving when schedules break.
            </p>
        </div>
    </section>

    <!-- Mission -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Our Mission</h2>
            <p class="text-xl text-gray-600 leading-relaxed">
                To create a transparent, efficient marketplace that connects businesses with skilled workers for short-term and flexible staffing needs. We believe in fair compensation, instant payments, and empowering both workers and businesses with technology.
            </p>
        </div>

        <!-- Values Grid -->
        <div class="grid md:grid-cols-3 gap-8 mb-20">
            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Speed</h3>
                <p class="text-gray-600">Fill shifts in minutes, not days. Workers get paid instantly after completion.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Trust</h3>
                <p class="text-gray-600">All workers are verified and background-checked for your peace of mind.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200 text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="grid md:grid-cols-3 gap-8 mb-20">
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">70+</div>
                <p class="text-gray-600">Countries</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">500+</div>
                <p class="text-gray-600">Businesses</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold text-blue-600 mb-2">2.3M+</div>
                <p class="text-gray-600">Shifts</p>
            </div>
        </div>

        <!-- Team Section -->
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Our Team</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                We're a diverse team of engineers, operators, and industry experts passionate about transforming the staffing industry.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 mb-20">
            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Sarah Chen</h3>
                <p class="text-blue-600 mb-3">CEO & Co-Founder</p>
                <p class="text-gray-600 text-sm">Former VP of Operations at a leading hospitality group</p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Marcus Johnson</h3>
                <p class="text-blue-600 mb-3">CTO & Co-Founder</p>
                <p class="text-gray-600 text-sm">Previously led engineering at two marketplace unicorns</p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-gray-200 text-center">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 class="text-xl font-semibold text-gray-900 mb-1">Priya Patel</h3>
                <p class="text-blue-600 mb-3">Head of Product</p>
                <p class="text-gray-600 text-sm">10+ years building worker-focused products at tech companies</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Join Our Growing Community</h2>
            <p class="text-lg text-gray-600 mb-8">Whether you're looking for work or looking to hire, we're here to help.</p>
            <x-ui.button-primary href="{{ route('register') }}" btnSize="lg">
                Create Account
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </x-ui.button-primary>
        </div>
    </section>
@endsection
