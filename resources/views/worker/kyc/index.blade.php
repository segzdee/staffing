@extends('layouts.dashboard')

@section('title', 'Identity Verification')
@section('page-title', 'Identity Verification (KYC)')
@section('page-subtitle', 'Verify your identity to start working on shifts')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Current Status Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    @if($status['is_verified'])
                        <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'in_review')
                        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'pending')
                        <div class="w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'rejected')
                        <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="flex-1">
                    <h2 class="text-xl font-semibold text-gray-900">
                        @if($status['is_verified'])
                            Identity Verified
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'in_review')
                            Under Review
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'pending')
                            Pending Review
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'rejected')
                            Verification Required
                        @else
                            Not Verified
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        @if($status['is_verified'])
                            Your identity has been verified. Verification level: <span class="font-medium">{{ ucfirst($status['kyc_level']) }}</span>
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'in_review')
                            Your documents are being reviewed. This usually takes 1-3 business days.
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'pending')
                            Your verification is pending review.
                        @elseif($status['latest_verification'] && $status['latest_verification']['status'] === 'rejected')
                            Your previous verification was not approved.
                            @if($status['latest_verification']['can_retry'])
                                You can submit new documents.
                            @else
                                Please contact support for assistance.
                            @endif
                        @else
                            Complete identity verification to start applying for shifts.
                        @endif
                    </p>

                    @if($status['latest_verification'] && $status['latest_verification']['rejection_reason'])
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800">
                                <span class="font-medium">Reason:</span> {{ $status['latest_verification']['rejection_reason'] }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex-shrink-0">
                    @if($status['can_submit'])
                        <a href="{{ route('worker.kyc.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $status['latest_verification'] && $status['latest_verification']['status'] === 'rejected' ? 'Resubmit' : 'Start Verification' }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($status['is_verified'] && $status['latest_verification'])
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">
                        Document: {{ $status['latest_verification']['document_type_name'] ?? 'N/A' }}
                    </span>
                    @if($status['latest_verification']['expires_at'])
                        <span class="text-gray-600">
                            Valid until: {{ \Carbon\Carbon::parse($status['latest_verification']['expires_at'])->format('M d, Y') }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Verification Steps --}}
    @if(!$status['is_verified'])
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">How to verify your identity</h3>
        <div class="space-y-4">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">1</div>
                <div>
                    <h4 class="font-medium text-gray-900">Prepare your documents</h4>
                    <p class="text-sm text-gray-600">Have a valid government-issued ID ready (passport, driver's license, or national ID).</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">2</div>
                <div>
                    <h4 class="font-medium text-gray-900">Upload clear photos</h4>
                    <p class="text-sm text-gray-600">Take clear photos of the front and back of your ID. Make sure all text is readable.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">3</div>
                <div>
                    <h4 class="font-medium text-gray-900">Take a selfie</h4>
                    <p class="text-sm text-gray-600">We'll compare your selfie with your ID photo to confirm your identity.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">4</div>
                <div>
                    <h4 class="font-medium text-gray-900">Wait for review</h4>
                    <p class="text-sm text-gray-600">Our team will review your submission within 1-3 business days.</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Verification History --}}
    @if($verifications->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Verification History</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($verifications as $verification)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($verification->status === 'approved') bg-green-100 text-green-800
                            @elseif($verification->status === 'rejected') bg-red-100 text-red-800
                            @elseif($verification->status === 'in_review') bg-blue-100 text-blue-800
                            @elseif($verification->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ $verification->status_name }}
                        </span>
                        <span class="text-sm text-gray-900">{{ $verification->document_type_name }}</span>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $verification->created_at->format('M d, Y') }}
                    </div>
                </div>
                @if($verification->rejection_reason)
                    <p class="mt-2 text-sm text-red-600">{{ $verification->rejection_reason }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Help Section --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h4 class="font-medium text-blue-900">Need help?</h4>
                <p class="mt-1 text-sm text-blue-700">
                    If you're having trouble verifying your identity or have questions about the process,
                    please <a href="{{ Route::has('support') ? route('support') : '#' }}" class="underline hover:text-blue-800">contact our support team</a>.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
