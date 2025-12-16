@extends('layouts.authenticated')

@section('title', 'Enable Two-Factor Authentication - OvertimeStaff')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border p-6" style="border-color: hsl(240 5.9% 90%);">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Enable Two-Factor Authentication</h1>
            <p class="mt-2 text-sm text-gray-600">
                Scan the QR code below with your authenticator app, then enter the verification code to enable 2FA.
            </p>
        </div>

        <!-- Flash Messages -->
        @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        <!-- Step 1: Scan QR Code -->
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <span class="flex items-center justify-center w-7 h-7 bg-gray-900 text-white text-sm font-bold rounded-full mr-3">1</span>
                Scan QR Code
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Open your authenticator app and scan this QR code:
            </p>
            <div class="flex justify-center p-6 bg-white border rounded-lg" style="border-color: hsl(240 5.9% 90%);">
                <div class="qr-code-container">
                    {!! $qrCodeSvg !!}
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 text-center">
                Compatible with Google Authenticator, Microsoft Authenticator, Authy, and other TOTP apps
            </p>
        </div>

        <!-- Step 2: Manual Entry -->
        <div class="mb-8" x-data="{ showSecret: false }">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <span class="flex items-center justify-center w-7 h-7 bg-gray-900 text-white text-sm font-bold rounded-full mr-3">2</span>
                Or Enter Code Manually
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                If you can't scan the QR code, enter this secret key in your authenticator app:
            </p>
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <code class="text-sm font-mono" x-text="showSecret ? '{{ $secret }}' : '{{ str_repeat('*', 32) }}'"></code>
                    <button
                        type="button"
                        @click="showSecret = !showSecret"
                        class="ml-4 px-3 py-1 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition-colors"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <span x-text="showSecret ? 'Hide' : 'Show'"></span>
                    </button>
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ $secret }}'); $dispatch('toast', 'Copied to clipboard!')"
                        class="ml-2 px-3 py-1 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition-colors"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        Copy
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Verify Code -->
        <div class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                <span class="flex items-center justify-center w-7 h-7 bg-gray-900 text-white text-sm font-bold rounded-full mr-3">3</span>
                Verify Setup
            </h2>
            <p class="text-sm text-gray-600 mb-4">
                Enter the 6-digit code from your authenticator app to complete setup:
            </p>

            <form action="{{ route('two-factor.confirm') }}" method="POST" x-data="{ code: '' }">
                @csrf
                <div class="mb-4">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        x-model="code"
                        maxlength="6"
                        pattern="[0-9]*"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        class="w-full px-4 py-3 text-center text-2xl font-mono tracking-widest border rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 @error('code') border-red-500 @enderror"
                        style="border-color: hsl(240 5.9% 90%); max-width: 200px;"
                        placeholder="000000"
                    >
                    @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    :disabled="code.length !== 6"
                    class="w-full sm:w-auto px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Enable Two-Factor Authentication
                </button>
            </form>
        </div>

        <!-- Warning -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Important</h4>
                    <p class="mt-1 text-sm text-amber-700">
                        After enabling 2FA, you will receive recovery codes. Store them securely - they are the only way to access your account if you lose your authenticator device.
                    </p>
                </div>
            </div>
        </div>

        <!-- Back Link -->
        <div class="pt-6 border-t" style="border-color: hsl(240 5.9% 90%);">
            <a href="{{ route('two-factor.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Cancel
            </a>
        </div>
    </div>
</div>

<style nonce="{{ $cspNonce ?? '' }}">
    .qr-code-container svg {
        width: 200px;
        height: 200px;
    }
</style>
@endsection
