@extends('layouts.marketing')

@section('title', 'Features | OvertimeStaff')
@section('meta_description', 'Discover powerful features for modern staffing. Real-time matching, instant payouts, verified workers, GPS tracking, and advanced analytics.')

@section('content')

    <!-- Hero -->
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-5xl font-bold mb-6">Powerful Features for Modern Staffing</h1>
            <p class="text-xl text-brand-100 max-w-3xl mx-auto">
                Everything you need to manage shifts, workers, and payments in one platform.
            </p>
        </div>
    </div>

    <!-- Features Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Real-Time Matching</h3>
                <p class="text-gray-600">Instantly connect with qualified workers based on skills, location, and availability.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Instant Payouts</h3>
                <p class="text-gray-600">Workers receive payment within 15 minutes of shift completion via Stripe Connect.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Verified Workers</h3>
                <p class="text-gray-600">Background checks, certifications, and skill verification ensure quality staffing.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">GPS Clock-In/Out</h3>
                <p class="text-gray-600">Location-verified time tracking with photo verification for accountability.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Advanced Analytics</h3>
                <p class="text-gray-600">Track performance, costs, and worker reliability with detailed reporting.</p>
            </div>

            <div class="bg-white p-8 rounded-xl border border-gray-200">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Built-In Messaging</h3>
                <p class="text-gray-600">Communicate directly with workers about shift details and requirements.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Ready to Get Started?</h2>
            <p class="text-lg text-gray-600 mb-8">Join thousands of businesses and workers using OvertimeStaff.</p>
            <x-ui.button-primary href="{{ route('register') }}" btnSize="lg">
                Get Started
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </x-ui.button-primary>
        </div>
    </section>
@endsection
