@extends('layouts.dashboard')

@section('title', 'Stripe Connect Setup')
@section('page-title', 'Set Up Payouts')
@section('page-subtitle', 'Connect your bank account to receive commission payouts')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    @if(session('warning'))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>{{ session('warning') }}</span>
    </div>
    @endif

    {{-- Main Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {{-- Header --}}
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-8 h-8 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Connect with Stripe</h2>
                    <p class="text-gray-600 mt-1">Securely connect your bank account to receive automatic payouts</p>
                </div>
            </div>
        </div>

        {{-- Pending Commission Banner --}}
        @if($pendingCommission > 0)
        <div class="p-4 bg-amber-50 border-b border-amber-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-amber-800">You have pending commissions!</p>
                        <p class="text-sm text-amber-600">Complete setup to receive your payouts</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-amber-700">${{ number_format($pendingCommission, 2) }}</p>
                    <p class="text-xs text-amber-600">Pending</p>
                </div>
            </div>
        </div>
        @endif

        {{-- Content based on status --}}
        <div class="p-6">
            @if($stripeStatus === 'not_created' || $stripeStatus === 'pending_details')
                {{-- Not started or incomplete onboarding --}}
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Set up your payout account</h3>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">
                        Connect your bank account through Stripe to receive automatic weekly commission payouts.
                        The setup process takes about 5 minutes.
                    </p>

                    {{-- Benefits --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 text-left">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 mb-1">Automatic Payouts</h4>
                            <p class="text-sm text-gray-600">Receive weekly payouts directly to your bank</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 mb-1">Secure & Protected</h4>
                            <p class="text-sm text-gray-600">Bank-level security with Stripe</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <h4 class="font-medium text-gray-900 mb-1">Easy Tracking</h4>
                            <p class="text-sm text-gray-600">View all payouts in your dashboard</p>
                        </div>
                    </div>

                    <a href="{{ route('agency.stripe.connect') }}" class="inline-flex items-center gap-2 px-8 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                        </svg>
                        Connect with Stripe
                    </a>

                    <p class="mt-4 text-sm text-gray-500">
                        You'll be redirected to Stripe to securely enter your information
                    </p>
                </div>
            @elseif($stripeStatus === 'pending_verification')
                {{-- Pending verification --}}
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Verification in Progress</h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        Stripe is verifying your information. This usually takes 1-2 business days.
                        We'll notify you once verification is complete.
                    </p>

                    @if(!empty($statusDetails['requirements']))
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-left max-w-md mx-auto mb-6">
                        <h4 class="font-medium text-orange-800 mb-2">Additional information needed:</h4>
                        <ul class="text-sm text-orange-700 space-y-1">
                            @foreach($statusDetails['requirements'] as $requirement)
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ ucwords(str_replace(['_', '.'], ' ', $requirement)) }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <a href="{{ route('agency.stripe.connect') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors">
                        Complete Verification
                    </a>
                </div>
            @else
                {{-- Active or other status - redirect to status page --}}
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Stripe Connect is Active</h3>
                    <p class="text-gray-600 mb-6">Your payout account is set up and ready to receive commissions.</p>
                    <a href="{{ route('agency.stripe.status') }}" class="inline-flex items-center gap-2 px-6 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        View Payout Status
                    </a>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="p-4 bg-gray-50 border-t border-gray-200">
            <p class="text-xs text-gray-500 text-center">
                Powered by <a href="https://stripe.com" target="_blank" class="text-indigo-600 hover:underline">Stripe</a>.
                Your bank information is securely stored by Stripe and never shared with OvertimeStaff.
            </p>
        </div>
    </div>
</div>
@endsection
