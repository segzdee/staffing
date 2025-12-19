@extends('layouts.dashboard')

@section('title', 'Add Funds')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('business.dashboard') }}" class="hover:text-gray-700">Dashboard</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900 font-medium">Add Funds</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">Add Funds to Your Account</h1>
        <p class="mt-1 text-sm text-gray-500">Add funds to your balance to pay for shift postings and worker payments.</p>
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

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-red-700">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="addFundsForm()">
        {{-- Left Column: Add Funds Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Payment Details</h2>
                </div>

                <form id="payment-form" @submit.prevent="submitPayment" class="p-6 space-y-6">
                    @csrf

                    {{-- Amount Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Select Amount
                        </label>
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <button type="button"
                                    @click="selectAmount(100)"
                                    :class="amount === 100 ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 hover:border-gray-300'"
                                    class="p-4 border rounded-lg text-center transition-all">
                                <span class="block text-lg font-bold text-gray-900">$100</span>
                                <span class="block text-xs text-gray-500 mt-1">Starter</span>
                            </button>
                            <button type="button"
                                    @click="selectAmount(250)"
                                    :class="amount === 250 ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 hover:border-gray-300'"
                                    class="p-4 border rounded-lg text-center transition-all relative">
                                <span class="absolute -top-2 left-1/2 -translate-x-1/2 px-2 py-0.5 bg-indigo-600 text-white text-xs font-medium rounded">Popular</span>
                                <span class="block text-lg font-bold text-gray-900">$250</span>
                                <span class="block text-xs text-gray-500 mt-1">Standard</span>
                            </button>
                            <button type="button"
                                    @click="selectAmount(500)"
                                    :class="amount === 500 ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500' : 'border-gray-200 hover:border-gray-300'"
                                    class="p-4 border rounded-lg text-center transition-all">
                                <span class="block text-lg font-bold text-gray-900">$500</span>
                                <span class="block text-xs text-gray-500 mt-1">Business</span>
                            </button>
                        </div>

                        {{-- Custom Amount --}}
                        <div class="relative">
                            <label for="custom-amount" class="block text-xs font-medium text-gray-500 mb-1">
                                Or enter custom amount
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-lg">$</span>
                                </div>
                                <input type="number"
                                       id="custom-amount"
                                       x-model.number="customAmount"
                                       @input="amount = customAmount"
                                       min="25"
                                       max="10000"
                                       step="1"
                                       class="block w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg"
                                       placeholder="Custom amount">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Minimum: $25 | Maximum: $10,000</p>
                        </div>
                    </div>

                    {{-- Card Input --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Card Details
                            </label>
                            {{-- Card Brand Icons --}}
                            <div class="flex items-center gap-1">
                                <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="50" height="16" rx="2" fill="#1A1F71"/>
                                    <path d="M19.5 11.5L21.5 4.5H24L22 11.5H19.5Z" fill="white"/>
                                    <path d="M28 4.3C27.4 4.1 26.5 4 25.5 4C23 4 21.3 5.2 21.3 6.9C21.3 8.2 22.5 8.9 23.5 9.3C24.5 9.7 24.8 10 24.8 10.4C24.8 11 24.1 11.3 23.4 11.3C22.4 11.3 21.9 11.2 21 10.8L20.7 10.7L20.4 12.5C21.1 12.8 22.3 13 23.5 13C26.2 13 27.9 11.8 27.9 10C27.9 9 27.3 8.2 25.9 7.6C25 7.2 24.5 6.9 24.5 6.5C24.5 6.1 24.9 5.7 25.8 5.7C26.6 5.7 27.2 5.8 27.6 6L27.8 6.1L28 4.3Z" fill="white"/>
                                    <path d="M14 4.5H10L10 4.7C13.2 5.5 15.3 7.4 16 9.5L15.2 5.2C15.1 4.7 14.6 4.5 14 4.5Z" fill="#F9A533"/>
                                </svg>
                                <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="50" height="16" rx="2" fill="#16366F"/>
                                    <circle cx="20" cy="8" r="5" fill="#EB001B"/>
                                    <circle cx="30" cy="8" r="5" fill="#F79E1B"/>
                                    <path d="M25 4.27C26.27 5.27 27 6.55 27 8C27 9.45 26.27 10.73 25 11.73C23.73 10.73 23 9.45 23 8C23 6.55 23.73 5.27 25 4.27Z" fill="#FF5F00"/>
                                </svg>
                                <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="50" height="16" rx="2" fill="#016FD0"/>
                                    <path d="M13 5L11 11H13L13.3 10H15.7L16 11H18L16 5H13ZM14.5 6.5L15.3 8.5H13.7L14.5 6.5Z" fill="white"/>
                                    <path d="M18.5 5V11H20.5V8.5L22 11H24.5L22.5 8.5L24.5 5H22L20.5 7.5V5H18.5Z" fill="white"/>
                                    <path d="M25 5V11H30V9.5H27V8.5H30V7H27V6.5H30V5H25Z" fill="white"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Stripe Card Element Container --}}
                        <div id="card-element"
                             class="p-4 border border-gray-300 rounded-lg bg-white transition-all duration-200 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                        </div>
                        <div id="card-errors" class="mt-2 text-sm text-red-600" x-show="cardError" x-text="cardError"></div>

                        {{-- Security Notice --}}
                        <div class="mt-2 flex items-center text-xs text-gray-500">
                            <svg class="h-4 w-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Your card details are encrypted and securely processed by Stripe.
                        </div>
                    </div>

                    {{-- Billing Address (Optional) --}}
                    <div x-show="showBillingAddress">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Billing Address
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input type="text"
                                   x-model="billingDetails.address.line1"
                                   placeholder="Address Line 1"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="text"
                                   x-model="billingDetails.address.city"
                                   placeholder="City"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="text"
                                   x-model="billingDetails.address.state"
                                   placeholder="State"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <input type="text"
                                   x-model="billingDetails.address.postal_code"
                                   placeholder="ZIP Code"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    {{-- Error Display --}}
                    <div x-show="errorMessage" x-cloak class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="ml-3 text-sm text-red-700" x-text="errorMessage"></p>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            :disabled="processing || !canSubmit"
                            class="w-full py-3 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!processing" class="flex items-center justify-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Add $<span x-text="amount.toFixed(2)"></span> to Account
                        </span>
                        <span x-show="processing" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Right Column: Summary --}}
        <div class="space-y-6">
            {{-- Order Summary --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Amount</span>
                        <span class="text-gray-900 font-medium">$<span x-text="amount.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Processing Fee</span>
                        <span class="text-gray-900">$0.00</span>
                    </div>
                    <div class="border-t border-gray-200 pt-3 flex justify-between">
                        <span class="font-medium text-gray-900">Total</span>
                        <span class="font-bold text-gray-900">$<span x-text="amount.toFixed(2)"></span></span>
                    </div>
                </div>
            </div>

            {{-- Current Balance --}}
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm text-indigo-100 mb-1">Current Balance</p>
                <p class="text-3xl font-bold">${{ number_format(auth()->user()->businessProfile?->credit_balance ?? 0, 2) }}</p>
                <p class="text-sm text-indigo-200 mt-2">After this deposit: $<span x-text="({{ auth()->user()->businessProfile?->credit_balance ?? 0 }} + amount).toFixed(2)"></span></p>
            </div>

            {{-- Benefits --}}
            <div class="bg-gray-50 rounded-xl p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Why Add Funds?</h3>
                <ul class="space-y-2">
                    <li class="flex items-start text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Instant shift posting without payment delays
                    </li>
                    <li class="flex items-start text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Automatic escrow for worker payments
                    </li>
                    <li class="flex items-start text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        No processing fees on deposits
                    </li>
                </ul>
            </div>

            {{-- Security Badge --}}
            <div class="flex items-center justify-center text-sm text-gray-500">
                <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Secured by Stripe
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
function addFundsForm() {
    return {
        stripe: null,
        cardElement: null,
        amount: 250,
        customAmount: '',
        processing: false,
        cardError: '',
        errorMessage: '',
        cardComplete: false,
        showBillingAddress: false,
        billingDetails: {
            name: '{{ auth()->user()->name }}',
            email: '{{ auth()->user()->email }}',
            address: {
                line1: '',
                city: '',
                state: '',
                postal_code: '',
                country: 'US'
            }
        },

        get canSubmit() {
            return this.amount >= 25 && this.cardComplete && !this.processing;
        },

        init() {
            this.stripe = Stripe('{{ config("services.stripe.key") }}');
            const elements = this.stripe.elements({
                fonts: [
                    {
                        cssSrc: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap',
                    },
                ],
            });

            this.cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#1f2937',
                        fontFamily: 'Inter, system-ui, sans-serif',
                        '::placeholder': {
                            color: '#9ca3af',
                        },
                    },
                    invalid: {
                        color: '#ef4444',
                        iconColor: '#ef4444',
                    },
                },
            });

            this.cardElement.mount('#card-element');

            this.cardElement.on('change', (event) => {
                this.cardError = event.error ? event.error.message : '';
                this.cardComplete = event.complete;
            });
        },

        selectAmount(amt) {
            this.amount = amt;
            this.customAmount = '';
        },

        async submitPayment() {
            if (this.processing || !this.canSubmit) return;

            this.processing = true;
            this.errorMessage = '';

            try {
                // Create Payment Intent on server
                const intentResponse = await fetch('{{ route("business.payments.create-intent") ?? "#" }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        amount: Math.round(this.amount * 100), // Convert to cents
                        currency: 'usd'
                    })
                });

                const intentData = await intentResponse.json();

                if (!intentData.success || !intentData.client_secret) {
                    throw new Error(intentData.message || 'Failed to create payment intent.');
                }

                // Confirm card payment with Stripe
                const { error, paymentIntent } = await this.stripe.confirmCardPayment(
                    intentData.client_secret,
                    {
                        payment_method: {
                            card: this.cardElement,
                            billing_details: this.billingDetails
                        }
                    }
                );

                if (error) {
                    throw new Error(error.message);
                }

                if (paymentIntent.status === 'succeeded') {
                    // Redirect to success page or show success message
                    window.location.href = '{{ route("business.payments.history") }}?success=1';
                } else if (paymentIntent.status === 'requires_action') {
                    // 3D Secure or other action required - Stripe handles this automatically
                    this.errorMessage = 'Additional verification required. Please complete the authentication.';
                } else {
                    throw new Error('Payment could not be completed. Please try again.');
                }

            } catch (error) {
                this.errorMessage = error.message || 'An unexpected error occurred. Please try again.';
            } finally {
                this.processing = false;
            }
        }
    };
}
</script>
@endpush
@endsection
