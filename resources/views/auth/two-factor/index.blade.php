@extends('layouts.authenticated')

@section('title', 'Two-Factor Authentication - OvertimeStaff')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border p-6" style="border-color: hsl(240 5.9% 90%);">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Two-Factor Authentication</h1>
            <p class="mt-2 text-sm text-gray-600">
                Add an extra layer of security to your account by enabling two-factor authentication.
            </p>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
        @endif

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

        @if(session('info'))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
            </div>
        </div>
        @endif

        <!-- 2FA Status -->
        <div class="mb-8">
            <div class="flex items-center justify-between p-4 rounded-lg {{ $twoFactorEnabled ? 'bg-green-50' : 'bg-gray-50' }}">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($twoFactorEnabled)
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        @else
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium {{ $twoFactorEnabled ? 'text-green-900' : 'text-gray-900' }}">
                            {{ $twoFactorEnabled ? 'Two-Factor Authentication is Enabled' : 'Two-Factor Authentication is Disabled' }}
                        </h3>
                        <p class="mt-1 text-sm {{ $twoFactorEnabled ? 'text-green-700' : 'text-gray-500' }}">
                            @if($twoFactorEnabled)
                            Your account is protected with an authenticator app.
                            @else
                            Protect your account with a time-based one-time password (TOTP) from an authenticator app.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        @if($twoFactorEnabled)
        <!-- Recovery Codes Info -->
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-amber-800">Recovery Codes</h4>
                    <p class="mt-1 text-sm text-amber-700">
                        You have <strong>{{ $recoveryCodesCount }}</strong> recovery codes remaining.
                        @if($recoveryCodesCount <= 2)
                        <span class="text-red-600">Please regenerate your recovery codes soon.</span>
                        @endif
                    </p>
                    <a href="{{ route('two-factor.recovery-codes') }}" class="inline-flex items-center mt-2 text-sm font-medium text-amber-800 hover:text-amber-900">
                        View Recovery Codes
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Disable 2FA Form -->
        <div class="border-t pt-6" style="border-color: hsl(240 5.9% 90%);">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Disable Two-Factor Authentication</h3>
            <p class="text-sm text-gray-500 mb-4">
                Enter your password to disable two-factor authentication. This will make your account less secure.
            </p>
            <form action="{{ route('two-factor.disable') }}" method="POST" x-data="{ showPassword: false }">
                @csrf
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            id="password"
                            required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('password') border-red-500 @enderror"
                            style="border-color: hsl(240 5.9% 90%);"
                        >
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Disable Two-Factor Authentication
                </button>
            </form>
        </div>

        @else
        <!-- Enable 2FA -->
        <div class="text-center py-4">
            <a href="{{ route('two-factor.enable') }}" class="inline-flex items-center px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Enable Two-Factor Authentication
            </a>
        </div>

        <!-- Info Section -->
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-900 mb-2">How it works:</h3>
            <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                <li>Install an authenticator app like Google Authenticator, Microsoft Authenticator, or Authy</li>
                <li>Scan the QR code or enter the secret key manually</li>
                <li>Enter the 6-digit code to verify and enable 2FA</li>
                <li>Save your recovery codes in a secure location</li>
            </ol>
        </div>
        @endif

        <!-- Back Link -->
        <div class="mt-8 pt-6 border-t" style="border-color: hsl(240 5.9% 90%);">
            <a href="{{ route('settings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Settings
            </a>
        </div>
    </div>
</div>
@endsection
