@extends('layouts.dashboard')

@section('title', 'Setup Payment Method')

@section('page-title', 'Setup Payment Method')
@section('page-subtitle', 'Configure how you will pay workers for completed shifts')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Alert message -->
    <div class="bg-gray-50 border-l-4 border-gray-900 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-gray-900 font-medium">
                    Payment method required
                </p>
                <p class="mt-1 text-sm text-gray-700">
                    You need to set up a payment method before you can post shifts.
                </p>
            </div>
        </div>
    </div>

    <!-- Payment method options -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Payment Method</h3>

        <div class="space-y-4">
            <!-- Stripe -->
            <div class="border border-gray-300 rounded-lg p-4 hover:border-gray-900 transition-colors cursor-pointer">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-base font-semibold text-gray-900">Credit/Debit Card via Stripe</h4>
                        <p class="mt-1 text-sm text-gray-600">Secure payment processing with instant payouts to workers</p>
                        <div class="mt-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-900 text-white">
                                Recommended
                            </span>
                            <span class="ml-2 text-xs text-gray-500">Processing fee: 2.9% + $0.30</span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                            Setup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bank Transfer -->
            <div class="border border-gray-300 rounded-lg p-4 hover:border-gray-900 transition-colors cursor-pointer opacity-60">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <h4 class="text-base font-semibold text-gray-900">Bank Transfer (ACH)</h4>
                        <p class="mt-1 text-sm text-gray-600">Direct bank transfers for payments (2-3 business days)</p>
                        <div class="mt-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                Coming Soon
                            </span>
                        </div>
                    </div>
                    <div class="ml-4">
                        <button type="button" disabled class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                            Setup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">How Payment Works</h3>
        <ol class="space-y-3">
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">1</span>
                <p class="ml-3 text-sm text-gray-700">You post a shift with an hourly rate</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">2</span>
                <p class="ml-3 text-sm text-gray-700">Funds are held in escrow when shift is assigned</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">3</span>
                <p class="ml-3 text-sm text-gray-700">Worker completes shift and checks out</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">4</span>
                <p class="ml-3 text-sm text-gray-700">You approve hours worked</p>
            </li>
            <li class="flex items-start">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-900 text-white flex items-center justify-center text-sm font-bold">5</span>
                <p class="ml-3 text-sm text-gray-700">Payment is released to worker immediately</p>
            </li>
        </ol>
    </div>

    <!-- Security badge -->
    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6">
        <div class="flex items-center">
            <svg class="h-6 w-6 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Your payment information is secure</p>
                <p class="text-xs text-gray-600 mt-1">We use industry-standard encryption and never store sensitive payment details</p>
            </div>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-6 flex flex-col sm:flex-row gap-4">
        <a href="{{ route('settings.index') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
            Go to Settings
        </a>
        <a href="{{ route('business.dashboard') }}" class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
            Skip for Now
        </a>
    </div>
</div>
@endsection
