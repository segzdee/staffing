@extends('layouts.marketing')

@section('title', 'Post Shifts - OvertimeStaff')
@section('meta_description', 'Post shifts and get matched with qualified workers in minutes. Easy shift posting, smart matching, and hassle-free management.')

@section('content')
    <!-- Hero Section -->
    <section class="py-16 lg:py-24 bg-gradient-to-br from-blue-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left Column - Content -->
                <div class="space-y-8">
                    <div class="flex items-center gap-3">
                        <x-ui.badge-pill color="green">2-Min Setup</x-ui.badge-pill>
                        <x-ui.badge-pill color="blue" :dot="false">Smart Matching</x-ui.badge-pill>
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-gray-900">
                        Post a Shift in Minutes
                    </h1>

                    <p class="text-lg md:text-xl text-gray-500 leading-relaxed max-w-xl">
                        Tell us what you need and our AI will match you with qualified, verified workers. It's that simple.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <x-ui.button-primary href="{{ route('register', ['type' => 'business']) }}" btnSize="lg">
                            Post Your First Shift
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </x-ui.button-primary>
                        <x-ui.button-primary href="#how-it-works" variant="outline" btnSize="lg">
                            See How It Works
                        </x-ui.button-primary>
                    </div>
                </div>

                <!-- Right Column - Shift Posting Preview -->
                <div class="lg:pl-8">
                    <x-ui.card-white class="p-8">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Post a Shift</h3>
                            <p class="text-sm text-gray-500">Preview of shift posting form</p>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Shift Title</label>
                                <input type="text" placeholder="e.g., Event Server, Warehouse Associate" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 focus:outline-none" disabled>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date</label>
                                    <input type="date" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 focus:outline-none" disabled>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Workers</label>
                                    <input type="number" placeholder="5" min="1" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-200 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-600/10 focus:outline-none" disabled>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-200">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">AI-powered matching in 15 minutes</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Verified workers only</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Automated payment handling</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.card-white>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <x-ui.badge-pill color="gray" :dot="false" class="mb-4">HOW IT WORKS</x-ui.badge-pill>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Post shifts in 4 easy steps
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Enter Details</h3>
                    <p class="text-gray-500">Job title, location, date, time, and number of workers needed.</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Set Requirements</h3>
                    <p class="text-gray-500">Specify any skills, certifications, or experience needed.</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Set Pay Rate</h3>
                    <p class="text-gray-500">Choose your pay rate. Our system suggests competitive rates based on market data.</p>
                </div>

                <!-- Step 4 -->
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-green-600 text-white flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">We Match Workers</h3>
                    <p class="text-gray-500">Our AI instantly matches your shift with qualified, verified workers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Powerful shift management
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    Everything you need to manage shifts efficiently
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Instant Matching</h3>
                    <p class="text-gray-500">Our AI matches your shift with the best available workers in under 15 minutes.</p>
                </x-ui.card-white>

                <!-- Feature 2 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-green-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Auto-Fill</h3>
                    <p class="text-gray-500">Enable auto-fill and let our system automatically assign workers who meet your criteria.</p>
                </x-ui.card-white>

                <!-- Feature 3 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Recurring Shifts</h3>
                    <p class="text-gray-500">Set up recurring shifts with one click. Daily, weekly, or custom schedules supported.</p>
                </x-ui.card-white>

                <!-- Feature 4 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-orange-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Favorite Workers</h3>
                    <p class="text-gray-500">Save your best workers to favorites and give them priority access to your shifts.</p>
                </x-ui.card-white>

                <!-- Feature 5 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-pink-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Performance Analytics</h3>
                    <p class="text-gray-500">Track fill rates, worker performance, and costs with real-time analytics.</p>
                </x-ui.card-white>

                <!-- Feature 6 -->
                <x-ui.card-white class="p-8">
                    <div class="w-14 h-14 rounded-xl bg-teal-100 flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Mobile Notifications</h3>
                    <p class="text-gray-500">Get instant notifications when workers apply, confirm, or check in to your shifts.</p>
                </x-ui.card-white>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <x-trust-section background="white" />

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Ready to fill your first shift?
            </h2>
            <p class="text-lg text-gray-600 mb-8">
                Create your account and post your first shift in under 5 minutes.
            </p>
            <x-ui.button-primary href="{{ route('register', ['type' => 'business']) }}" btnSize="lg">
                Get Started Free
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </x-ui.button-primary>
        </div>
    </section>
@endsection
