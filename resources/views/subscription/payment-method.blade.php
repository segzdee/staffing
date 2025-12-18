@extends('layouts.app')

@section('title', 'Update Payment Method - OvertimeStaff')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg mx-auto">
        {{-- Back Link --}}
        <a href="{{ route('subscription.manage') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-8">
            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to subscription
        </a>

        {{-- Update Card --}}
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-900">Update Payment Method</h1>
                <p class="text-sm text-gray-500 mt-1">Enter your new card details below.</p>
            </div>

            <div class="p-6" x-data="paymentMethodForm()">
                <form @submit.prevent="updatePaymentMethod">
                    {{-- Card Element --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Card Details
                        </label>
                        <div id="card-element" class="p-3 border border-gray-300 rounded-lg bg-white"></div>
                        <div id="card-errors" class="mt-2 text-sm text-red-600" x-text="cardError"></div>
                    </div>

                    {{-- Success Message --}}
                    <div x-show="successMessage" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800" x-text="successMessage"></p>
                    </div>

                    {{-- Error Message --}}
                    <div x-show="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800" x-text="errorMessage"></p>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            :disabled="processing"
                            class="w-full py-3 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!processing">Update Payment Method</span>
                        <span x-show="processing" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Security Badge --}}
        <div class="mt-6 flex items-center justify-center text-sm text-gray-500">
            <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Secured by Stripe. Your payment info is encrypted.
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
function paymentMethodForm() {
    return {
        stripe: null,
        cardElement: null,
        processing: false,
        cardError: '',
        errorMessage: '',
        successMessage: '',

        init() {
            this.stripe = Stripe('{{ $stripeKey }}');
            const elements = this.stripe.elements();

            this.cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#374151',
                        fontFamily: 'Inter, system-ui, sans-serif',
                        '::placeholder': {
                            color: '#9CA3AF',
                        },
                    },
                    invalid: {
                        color: '#EF4444',
                        iconColor: '#EF4444',
                    },
                },
            });

            this.cardElement.mount('#card-element');

            this.cardElement.on('change', (event) => {
                this.cardError = event.error ? event.error.message : '';
            });
        },

        async updatePaymentMethod() {
            if (this.processing) return;

            this.processing = true;
            this.errorMessage = '';
            this.successMessage = '';

            try {
                // Create payment method
                const { error, paymentMethod } = await this.stripe.createPaymentMethod({
                    type: 'card',
                    card: this.cardElement,
                });

                if (error) {
                    this.errorMessage = error.message;
                    this.processing = false;
                    return;
                }

                // Submit to server
                const response = await fetch('{{ route('subscription.update-payment-method') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        payment_method: paymentMethod.id,
                    }),
                });

                const result = await response.json();

                if (!result.success) {
                    this.errorMessage = result.error || 'Something went wrong. Please try again.';
                } else {
                    this.successMessage = 'Payment method updated successfully!';
                    this.cardElement.clear();
                }

                this.processing = false;

            } catch (err) {
                this.errorMessage = 'An unexpected error occurred. Please try again.';
                this.processing = false;
            }
        }
    };
}
</script>
@endpush
@endsection
