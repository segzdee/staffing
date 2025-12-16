@extends('layouts.marketing')

@section('title', 'Pricing - OvertimeStaff')
@section('meta_description', 'Simple, transparent pricing for businesses. Pay only for the shifts you fill. No monthly fees, no hidden costs.')

@section('content')
    <!-- Hero Section -->
    <section class="py-16 lg:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                <x-ui.badge-pill color="blue" class="mb-6">PRICING</x-ui.badge-pill>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-gray-900 mb-6">
                    Transparent Pricing
                </h1>
                <p class="text-lg md:text-xl text-gray-500 leading-relaxed">
                    Pay only for the shifts you fill. No monthly fees, no hidden costs, no long-term contracts.
                </p>
            </div>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Pay-Per-Shift Plan -->
                <x-ui.card-white class="p-8 relative">
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Pay-Per-Shift</h3>
                        <p class="text-sm text-gray-500 mb-4">Perfect for occasional staffing needs</p>
                        <div class="flex items-baseline justify-center">
                            <span class="text-4xl font-bold text-gray-900">15%</span>
                            <span class="text-gray-500 ml-2">markup</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">on worker's hourly rate</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Pay only for filled shifts</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Verified workers</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Basic support</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Standard matching</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">No monthly fees</span>
                        </li>
                    </ul>
                    <x-ui.button-primary href="{{ route('register', ['type' => 'business']) }}" :fullWidth="true" variant="outline">
                        Get Started
                    </x-ui.button-primary>
                </x-ui.card-white>

                <!-- Monthly Plan -->
                <x-ui.card-white class="p-8 relative border-2 border-blue-600">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="bg-blue-600 text-white text-sm font-medium px-4 py-1 rounded-full">Most Popular</span>
                    </div>
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Monthly</h3>
                        <p class="text-sm text-gray-500 mb-4">For growing businesses with regular needs</p>
                        <div class="flex items-baseline justify-center">
                            <span class="text-4xl font-bold text-gray-900">12%</span>
                            <span class="text-gray-500 ml-2">markup</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">+ $299/month</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Unlimited shifts</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Priority matching</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Priority support</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Favorite workers</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Team management</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Advanced analytics</span>
                        </li>
                    </ul>
                    <x-ui.button-primary href="{{ route('register', ['type' => 'business']) }}" :fullWidth="true">
                        Get Started
                    </x-ui.button-primary>
                </x-ui.card-white>

                <!-- Enterprise Plan -->
                <x-ui.card-white class="p-8 relative">
                    <div class="text-center mb-8">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Enterprise</h3>
                        <p class="text-sm text-gray-500 mb-4">For large organizations with complex needs</p>
                        <div class="flex items-baseline justify-center">
                            <span class="text-4xl font-bold text-gray-900">Custom</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">volume-based pricing</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Everything in Monthly</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Dedicated account manager</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">API access</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Custom integrations</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">SLA guarantees</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-600">Volume discounts</span>
                        </li>
                    </ul>
                    <x-ui.button-primary href="{{ route('contact') }}" :fullWidth="true" variant="outline">
                        Contact Sales
                    </x-ui.button-primary>
                </x-ui.card-white>
            </div>

            <!-- No Hidden Fees Message -->
            <div class="mt-12 text-center">
                <x-ui.card-white class="p-8 max-w-2xl mx-auto">
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900">No hidden fees</h3>
                    </div>
                    <p class="text-gray-600">
                        What you see is what you pay. The markup percentage is all you pay. No setup fees, no monthly minimums (except Monthly plan), no cancellation fees. You only pay for shifts that are actually filled.
                    </p>
                </x-ui.card-white>
            </div>
        </div>
    </section>

    <!-- Pricing Calculator Section -->
    <section class="py-16 lg:py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Calculate Your Cost
                </h2>
                <p class="text-lg text-gray-500">
                    See how much you'll pay per shift
                </p>
            </div>

            <x-ui.card-white class="p-8" x-data="{ hourlyRate: 20, hours: 8, markup: 15, plan: 'pay-per-shift' }">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Worker Hourly Rate</label>
                        <div class="flex items-center gap-4">
                            <span class="text-2xl font-bold text-gray-900">$</span>
                            <input type="number" x-model="hourlyRate" min="10" max="100" step="1" class="flex-1 h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hours per Shift</label>
                        <input type="number" x-model="hours" min="1" max="24" step="0.5" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pricing Plan</label>
                        <select x-model="plan" @change="markup = plan === 'pay-per-shift' ? 15 : (plan === 'monthly' ? 12 : 10)" class="w-full h-12 px-4 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="pay-per-shift">Pay-Per-Shift (15% markup)</option>
                            <option value="monthly">Monthly (12% markup + $299/mo)</option>
                            <option value="enterprise">Enterprise (10% markup)</option>
                        </select>
                    </div>

                    <div class="pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Worker Pay</div>
                                <div class="text-2xl font-bold text-gray-900">$<span x-text="(hourlyRate * hours).toFixed(2)"></span></div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 mb-1">Platform Fee (<span x-text="markup"></span>%)</div>
                                <div class="text-2xl font-bold text-blue-600">$<span x-text="(hourlyRate * hours * markup / 100).toFixed(2)"></span></div>
                            </div>
                            <div class="col-span-2 pt-4 border-t border-gray-200">
                                <div class="text-sm text-gray-500 mb-1">Total Cost per Shift</div>
                                <div class="text-3xl font-bold text-gray-900">$<span x-text="(hourlyRate * hours * (1 + markup / 100)).toFixed(2)"></span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card-white>
        </div>
    </section>

    <!-- Comparison Section -->
    <section class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Compare to traditional staffing
                </h2>
                <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                    See how OvertimeStaff stacks up against traditional staffing agencies
                </p>
            </div>

            <div class="max-w-4xl mx-auto overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-4 px-4 font-semibold text-gray-900">Feature</th>
                            <th class="text-center py-4 px-4 font-semibold text-blue-600">OvertimeStaff</th>
                            <th class="text-center py-4 px-4 font-semibold text-gray-500">Traditional Agency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Markup on wages</td>
                            <td class="py-4 px-4 text-center text-green-600 font-semibold">12-15%</td>
                            <td class="py-4 px-4 text-center text-gray-500">30-50%</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Monthly fees</td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-4 text-center text-gray-500">$500-2000/mo</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Time to fill shifts</td>
                            <td class="py-4 px-4 text-center text-green-600 font-semibold">15 minutes</td>
                            <td class="py-4 px-4 text-center text-gray-500">24-48 hours</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Contract required</td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-4 text-center text-gray-500">6-12 months</td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Payroll handling</td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-4 px-4 text-gray-600">Real-time tracking</td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Pricing FAQs
                </h2>
            </div>

            <div class="space-y-4" x-data="{ open: null }">
                <div class="border border-gray-200 rounded-lg bg-white">
                    <button @click="open = open === 1 ? null : 1" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">How does billing work?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="open === 1 && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 1" x-collapse class="px-6 pb-4 text-gray-500">
                        You're billed weekly for completed shifts. We send a detailed invoice showing each shift, worker, hours, and total cost. Payment is due within 14 days via ACH or credit card.
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg bg-white">
                    <button @click="open = open === 2 ? null : 2" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Are there any hidden fees?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="open === 2 && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 2" x-collapse class="px-6 pb-4 text-gray-500">
                        No. The markup percentage is all you pay. There are no setup fees, monthly minimums, or cancellation fees. You only pay for shifts that are actually filled.
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg bg-white">
                    <button @click="open = open === 3 ? null : 3" class="w-full px-6 py-4 text-left flex justify-between items-center">
                        <span class="font-semibold text-gray-900">Can I switch plans?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="open === 3 && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === 3" x-collapse class="px-6 pb-4 text-gray-500">
                        Yes! You can upgrade or downgrade at any time. Changes take effect immediately. If you upgrade mid-cycle, you'll get the better rate on all future shifts.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <x-trust-section background="white" />

    <!-- CTA Section -->
    <section class="py-16 lg:py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Ready to get started?
            </h2>
            <p class="text-lg text-gray-600 mb-8">
                Post your first shift free. No credit card required.
            </p>
            <x-ui.button-primary href="{{ route('register', ['type' => 'business']) }}" btnSize="lg">
                Start Free Trial
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </x-ui.button-primary>
        </div>
    </section>
@endsection
