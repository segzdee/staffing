{{--
    Stripe Card Element Component

    Usage:
    <x-payments.stripe-card-element
        :stripe-key="config('services.stripe.key')"
        element-id="card-element"
        form-id="payment-form"
    />

    Props:
    - stripeKey: Stripe publishable key
    - elementId: ID for the card element container (default: 'card-element')
    - formId: ID of the parent form (default: 'payment-form')
    - showBrands: Whether to show supported card brands (default: true)
    - label: Label text (default: 'Card Details')
--}}

@props([
    'stripeKey',
    'elementId' => 'card-element',
    'formId' => 'payment-form',
    'showBrands' => true,
    'label' => 'Card Details'
])

<div class="stripe-card-wrapper">
    {{-- Label with Card Brands --}}
    <div class="flex items-center justify-between mb-2">
        <label for="{{ $elementId }}" class="block text-sm font-medium text-gray-700">
            {{ $label }}
        </label>

        @if($showBrands)
        <div class="flex items-center gap-1">
            {{-- Visa --}}
            <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="16" rx="2" fill="#1A1F71"/>
                <path d="M19.5 11.5L21.5 4.5H24L22 11.5H19.5Z" fill="white"/>
                <path d="M28 4.3C27.4 4.1 26.5 4 25.5 4C23 4 21.3 5.2 21.3 6.9C21.3 8.2 22.5 8.9 23.5 9.3C24.5 9.7 24.8 10 24.8 10.4C24.8 11 24.1 11.3 23.4 11.3C22.4 11.3 21.9 11.2 21 10.8L20.7 10.7L20.4 12.5C21.1 12.8 22.3 13 23.5 13C26.2 13 27.9 11.8 27.9 10C27.9 9 27.3 8.2 25.9 7.6C25 7.2 24.5 6.9 24.5 6.5C24.5 6.1 24.9 5.7 25.8 5.7C26.6 5.7 27.2 5.8 27.6 6L27.8 6.1L28 4.3Z" fill="white"/>
                <path d="M32.5 4.5C32 4.5 31.6 4.7 31.4 5.2L27.5 11.5H30.2L30.7 10H34L34.3 11.5H36.5L34.5 4.5H32.5ZM31.4 8.3C31.6 7.7 32.4 5.7 32.4 5.7C32.4 5.7 32.6 5.1 32.7 4.8L32.9 5.6C32.9 5.6 33.4 7.7 33.5 8.3H31.4Z" fill="white"/>
                <path d="M18 4.5L15.5 9.3L15.2 7.8C14.7 6.2 13.2 4.5 11.5 3.7L13.8 11.5H16.5L20.5 4.5H18Z" fill="white"/>
                <path d="M14 4.5H10L10 4.7C13.2 5.5 15.3 7.4 16 9.5L15.2 5.2C15.1 4.7 14.6 4.5 14 4.5Z" fill="#F9A533"/>
            </svg>

            {{-- Mastercard --}}
            <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="16" rx="2" fill="#16366F"/>
                <circle cx="20" cy="8" r="5" fill="#EB001B"/>
                <circle cx="30" cy="8" r="5" fill="#F79E1B"/>
                <path d="M25 4.27C26.27 5.27 27 6.55 27 8C27 9.45 26.27 10.73 25 11.73C23.73 10.73 23 9.45 23 8C23 6.55 23.73 5.27 25 4.27Z" fill="#FF5F00"/>
            </svg>

            {{-- American Express --}}
            <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="16" rx="2" fill="#016FD0"/>
                <path d="M13 5L11 11H13L13.3 10H15.7L16 11H18L16 5H13ZM14.5 6.5L15.3 8.5H13.7L14.5 6.5Z" fill="white"/>
                <path d="M18.5 5V11H20.5V8.5L22 11H24.5L22.5 8.5L24.5 5H22L20.5 7.5V5H18.5Z" fill="white"/>
                <path d="M25 5V11H30V9.5H27V8.5H30V7H27V6.5H30V5H25Z" fill="white"/>
                <path d="M31 5L33 8L31 11H33.5L34.5 9.5L35.5 11H38L36 8L38 5H35.5L34.5 6.5L33.5 5H31Z" fill="white"/>
            </svg>

            {{-- Discover --}}
            <svg class="h-6 w-auto" viewBox="0 0 50 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="50" height="16" rx="2" fill="#F9F9F9"/>
                <rect x="0.5" y="0.5" width="49" height="15" rx="1.5" stroke="#E5E5E5"/>
                <path d="M10 5H12C14 5 15 6 15 8C15 10 14 11 12 11H10V5ZM11.5 10H12C13.1 10 13.5 9.2 13.5 8C13.5 6.8 13.1 6 12 6H11.5V10Z" fill="#231F20"/>
                <path d="M16 5H17.5V11H16V5Z" fill="#231F20"/>
                <path d="M18.5 9.5C19 10.2 19.7 10.5 20.5 10.5C21.2 10.5 21.7 10.2 21.7 9.7C21.7 8.8 20 8.8 20 7.3C20 6 21 5 22.5 5C23.2 5 23.8 5.2 24.3 5.5L23.8 6.5C23.4 6.2 22.9 6 22.4 6C21.8 6 21.4 6.3 21.4 6.8C21.4 7.7 23.1 7.6 23.1 9.2C23.1 10.5 22 11.2 20.5 11.2C19.5 11.2 18.7 10.9 18.2 10.4L18.5 9.5Z" fill="#231F20"/>
                <circle cx="32" cy="8" r="5" fill="#F47216"/>
                <path d="M38 5H39.5L41 8.5L42.5 5H44L41.5 11H40.5L38 5Z" fill="#231F20"/>
                <path d="M44.5 5H48V6H46V7.5H48V8.5H46V10H48V11H44.5V5Z" fill="#231F20"/>
            </svg>
        </div>
        @endif
    </div>

    {{-- Card Element Container --}}
    <div id="{{ $elementId }}"
         class="p-4 border border-gray-300 rounded-lg bg-white transition-all duration-200 focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
    </div>

    {{-- Error Display --}}
    <div id="{{ $elementId }}-errors" class="mt-2 text-sm text-red-600 hidden"></div>

    {{-- Security Notice --}}
    <div class="mt-2 flex items-center text-xs text-gray-500">
        <svg class="h-4 w-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        Your card details are encrypted and securely processed by Stripe. We never store your full card number.
    </div>
</div>

@once
@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
// Global Stripe Elements manager
window.StripeElementsManager = {
    instances: {},

    init(stripeKey, elementId, options = {}) {
        if (this.instances[elementId]) {
            return this.instances[elementId];
        }

        const stripe = Stripe(stripeKey);
        const elements = stripe.elements({
            fonts: [
                {
                    cssSrc: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap',
                },
            ],
        });

        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1f2937',
                    fontFamily: 'Inter, system-ui, sans-serif',
                    fontWeight: '400',
                    lineHeight: '24px',
                    '::placeholder': {
                        color: '#9ca3af',
                    },
                },
                invalid: {
                    color: '#ef4444',
                    iconColor: '#ef4444',
                },
            },
            hidePostalCode: options.hidePostalCode || false,
        });

        const container = document.getElementById(elementId);
        if (container) {
            cardElement.mount('#' + elementId);

            // Error handling
            const errorElement = document.getElementById(elementId + '-errors');
            cardElement.on('change', (event) => {
                if (errorElement) {
                    if (event.error) {
                        errorElement.textContent = event.error.message;
                        errorElement.classList.remove('hidden');
                    } else {
                        errorElement.textContent = '';
                        errorElement.classList.add('hidden');
                    }
                }

                // Dispatch custom event for Alpine.js
                container.dispatchEvent(new CustomEvent('stripe-change', {
                    detail: {
                        complete: event.complete,
                        error: event.error,
                        brand: event.brand,
                        empty: event.empty,
                    }
                }));
            });
        }

        this.instances[elementId] = {
            stripe,
            elements,
            cardElement,
        };

        return this.instances[elementId];
    },

    get(elementId) {
        return this.instances[elementId];
    },

    async createPaymentMethod(elementId, billingDetails = {}) {
        const instance = this.get(elementId);
        if (!instance) {
            throw new Error('Stripe Elements not initialized for ' + elementId);
        }

        return await instance.stripe.createPaymentMethod({
            type: 'card',
            card: instance.cardElement,
            billing_details: billingDetails,
        });
    },

    async confirmCardPayment(elementId, clientSecret, options = {}) {
        const instance = this.get(elementId);
        if (!instance) {
            throw new Error('Stripe Elements not initialized for ' + elementId);
        }

        return await instance.stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: instance.cardElement,
                billing_details: options.billingDetails || {},
            },
        });
    },

    async confirmCardSetup(elementId, clientSecret, options = {}) {
        const instance = this.get(elementId);
        if (!instance) {
            throw new Error('Stripe Elements not initialized for ' + elementId);
        }

        return await instance.stripe.confirmCardSetup(clientSecret, {
            payment_method: {
                card: instance.cardElement,
                billing_details: options.billingDetails || {},
            },
        });
    },

    destroy(elementId) {
        const instance = this.instances[elementId];
        if (instance) {
            instance.cardElement.destroy();
            delete this.instances[elementId];
        }
    }
};
</script>
@endpush
@endonce
