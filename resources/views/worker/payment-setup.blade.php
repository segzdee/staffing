@extends('layouts.dashboard')

@section('title', 'Payment Setup')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Payment Setup</h1>
        <p class="mt-1 text-sm text-gray-500">Set up your payout account to receive earnings from completed shifts.</p>
    </div>

    {{-- Status Messages --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-green-700">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if(session('info'))
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-blue-700">{{ session('info') }}</p>
        </div>
    </div>
    @endif

    <div x-data="paymentSetup()" x-init="init()">
        {{-- Setup Progress --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Setup Progress</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <template x-if="status.has_account">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </template>
                        <template x-if="!status.has_account">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                                <span class="text-gray-600 font-medium">1</span>
                            </span>
                        </template>
                        <span class="ml-3 text-sm font-medium" :class="status.has_account ? 'text-green-600' : 'text-gray-900'">Account Created</span>
                    </div>
                    <div class="flex items-center">
                        <template x-if="status.details_submitted">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </template>
                        <template x-if="!status.details_submitted">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                                <span class="text-gray-600 font-medium">2</span>
                            </span>
                        </template>
                        <span class="ml-3 text-sm font-medium" :class="status.details_submitted ? 'text-green-600' : 'text-gray-900'">Details Submitted</span>
                    </div>
                    <div class="flex items-center">
                        <template x-if="status.payouts_enabled">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        </template>
                        <template x-if="!status.payouts_enabled">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100">
                                <span class="text-gray-600 font-medium">3</span>
                            </span>
                        </template>
                        <span class="ml-3 text-sm font-medium" :class="status.payouts_enabled ? 'text-green-600' : 'text-gray-900'">Payouts Enabled</span>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full transition-all duration-500" :style="'width: ' + progressPercent + '%'"></div>
                </div>
            </div>
        </div>

        {{-- Main Setup Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Not Started State --}}
            <template x-if="!status.has_account">
                <div class="p-8 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 mb-4">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Set Up Direct Deposit</h3>
                    <p class="text-gray-500 mb-6 max-w-md mx-auto">
                        Connect your bank account to receive payments directly. We use Stripe for secure, fast payouts.
                    </p>

                    {{-- Features List --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8 text-left max-w-2xl mx-auto">
                        <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Fast Payouts</p>
                                <p class="text-xs text-gray-500">Get paid within 1-2 business days</p>
                            </div>
                        </div>
                        <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Bank-Level Security</p>
                                <p class="text-xs text-gray-500">256-bit encryption</p>
                            </div>
                        </div>
                        <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">Instant Available</p>
                                <p class="text-xs text-gray-500">Opt for instant (small fee)</p>
                            </div>
                        </div>
                    </div>

                    <button @click="initiateOnboarding()"
                            :disabled="loading"
                            class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <template x-if="!loading">
                            <span class="flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Set Up Payouts
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Setting up...
                            </span>
                        </template>
                    </button>

                    {{-- Stripe Badge --}}
                    <p class="mt-4 text-xs text-gray-400 flex items-center justify-center">
                        <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Powered by Stripe - PCI DSS Compliant
                    </p>
                </div>
            </template>

            {{-- In Progress State --}}
            <template x-if="status.has_account && !status.payouts_enabled">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 mb-4">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Complete Your Setup</h3>
                        <p class="text-gray-500 max-w-md mx-auto">
                            Your account has been created but requires additional information before you can receive payouts.
                        </p>
                    </div>

                    {{-- Requirements List --}}
                    <div x-show="requirements.length > 0" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Required Information:</h4>
                        <ul class="space-y-2">
                            <template x-for="req in requirements" :key="req">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="h-4 w-4 text-yellow-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span x-text="formatRequirement(req)"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button @click="initiateOnboarding()"
                                :disabled="loading"
                                class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                            <template x-if="!loading">
                                <span>Continue Setup</span>
                            </template>
                            <template x-if="loading">
                                <span class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Loading...
                                </span>
                            </template>
                        </button>

                        <button @click="refreshStatus()"
                                :disabled="refreshing"
                                class="inline-flex items-center justify-center px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                            <template x-if="!refreshing">
                                <span class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Refresh Status
                                </span>
                            </template>
                            <template x-if="refreshing">
                                <span class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Checking...
                                </span>
                            </template>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Complete State --}}
            <template x-if="status.payouts_enabled">
                <div class="p-8">
                    <div class="text-center mb-8">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 mb-4">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Payouts Enabled!</h3>
                        <p class="text-gray-500 max-w-md mx-auto">
                            Your account is set up and ready to receive payments. Earnings from completed shifts will be deposited automatically.
                        </p>
                    </div>

                    {{-- Payout Settings --}}
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">Payout Schedule</h4>
                        <div class="flex flex-wrap gap-3">
                            <button @click="updateSchedule('daily')"
                                    :class="schedule === 'daily' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Daily
                            </button>
                            <button @click="updateSchedule('weekly')"
                                    :class="schedule === 'weekly' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Weekly
                            </button>
                            <button @click="updateSchedule('monthly')"
                                    :class="schedule === 'monthly' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Monthly
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">
                            <template x-if="schedule === 'daily'">
                                <span>Earnings are deposited every business day.</span>
                            </template>
                            <template x-if="schedule === 'weekly'">
                                <span>Earnings are deposited every Friday.</span>
                            </template>
                            <template x-if="schedule === 'monthly'">
                                <span>Earnings are deposited on the 1st of each month.</span>
                            </template>
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button @click="openDashboard()"
                                :disabled="dashboardLoading"
                                class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-colors">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View Stripe Dashboard
                        </button>

                        <a href="{{ route('worker.withdraw') }}"
                           class="inline-flex items-center justify-center px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Request Withdrawal
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- Error Display --}}
        <div x-show="error" x-cloak class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="ml-3 text-sm text-red-700" x-text="error"></p>
            </div>
        </div>

        {{-- Help Section --}}
        <div class="mt-8 bg-gray-50 rounded-xl p-6">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Frequently Asked Questions</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-700">How long do payouts take?</p>
                    <p class="text-sm text-gray-500">Standard payouts arrive in 1-2 business days. Instant payouts (1.5% fee) arrive within minutes.</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">What information do I need?</p>
                    <p class="text-sm text-gray-500">You'll need your bank account details, Social Security Number (for tax reporting), and a valid ID.</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Is my information secure?</p>
                    <p class="text-sm text-gray-500">Yes! We use Stripe, which is PCI DSS Level 1 certified - the highest level of security certification.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function paymentSetup() {
    return {
        status: @json($status ?? ['has_account' => false, 'details_submitted' => false, 'payouts_enabled' => false]),
        requirements: [],
        schedule: 'daily',
        loading: false,
        refreshing: false,
        dashboardLoading: false,
        error: '',

        get progressPercent() {
            if (this.status.payouts_enabled) return 100;
            if (this.status.details_submitted) return 66;
            if (this.status.has_account) return 33;
            return 0;
        },

        init() {
            if (this.status.has_account && !this.status.payouts_enabled) {
                this.loadRequirements();
            }
            if (this.status.payout_schedule) {
                this.schedule = this.status.payout_schedule;
            }
        },

        async initiateOnboarding() {
            this.loading = true;
            this.error = '';

            try {
                const response = await fetch('{{ route("worker.payment-setup.initiate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        refresh_url: window.location.href,
                        return_url: '{{ route("worker.payment-setup") }}?callback=true'
                    })
                });

                const data = await response.json();

                if (data.success && data.data?.url) {
                    window.location.href = data.data.url;
                } else {
                    this.error = data.message || 'Failed to generate onboarding link.';
                }
            } catch (e) {
                this.error = 'An unexpected error occurred. Please try again.';
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        async refreshStatus() {
            this.refreshing = true;
            this.error = '';

            try {
                const response = await fetch('{{ route("api.worker.payment.refresh") ?? "#" }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.status = {
                        has_account: data.data.has_account,
                        details_submitted: data.data.details_submitted,
                        payouts_enabled: data.data.payouts_enabled,
                        payout_schedule: data.data.payout_schedule
                    };
                    if (data.data.payout_schedule) {
                        this.schedule = data.data.payout_schedule;
                    }
                } else {
                    this.error = data.message || 'Failed to refresh status.';
                }
            } catch (e) {
                this.error = 'Failed to refresh status.';
            } finally {
                this.refreshing = false;
            }
        },

        async loadRequirements() {
            try {
                const response = await fetch('{{ route("api.worker.payment.requirements") ?? "#" }}', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.data) {
                    this.requirements = data.data.currently_due || [];
                }
            } catch (e) {
                console.error('Failed to load requirements', e);
            }
        },

        async updateSchedule(newSchedule) {
            if (newSchedule === this.schedule) return;

            const oldSchedule = this.schedule;
            this.schedule = newSchedule;

            try {
                const response = await fetch('{{ route("api.worker.payment.schedule") ?? "#" }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ schedule: newSchedule })
                });

                const data = await response.json();

                if (!data.success) {
                    this.schedule = oldSchedule;
                    this.error = data.message || 'Failed to update schedule.';
                }
            } catch (e) {
                this.schedule = oldSchedule;
                this.error = 'Failed to update schedule.';
            }
        },

        async openDashboard() {
            this.dashboardLoading = true;

            try {
                const response = await fetch('{{ route("api.worker.payment.dashboard-link") ?? "#" }}', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success && data.data?.url) {
                    window.open(data.data.url, '_blank');
                } else {
                    this.error = data.message || 'Failed to get dashboard link.';
                }
            } catch (e) {
                this.error = 'Failed to get dashboard link.';
            } finally {
                this.dashboardLoading = false;
            }
        },

        formatRequirement(req) {
            const map = {
                'individual.verification.document': 'Government-issued ID',
                'individual.ssn_last_4': 'Social Security Number (last 4)',
                'individual.id_number': 'Social Security Number',
                'external_account': 'Bank account information',
                'business_profile.url': 'Business website',
                'individual.address.city': 'City',
                'individual.address.line1': 'Street address',
                'individual.address.postal_code': 'ZIP code',
                'individual.address.state': 'State',
                'individual.dob.day': 'Date of birth',
                'individual.dob.month': 'Date of birth',
                'individual.dob.year': 'Date of birth',
                'individual.email': 'Email address',
                'individual.first_name': 'First name',
                'individual.last_name': 'Last name',
                'individual.phone': 'Phone number',
            };
            return map[req] || req.replace(/_/g, ' ').replace(/\./g, ' - ');
        }
    };
}
</script>
@endpush
@endsection
