@extends('layouts.guest')

@section('title', 'Two-Factor Verification - OvertimeStaff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">
    <!-- Accessibility: Live region for announcing validation errors -->
    <div id="validation-announcer" class="validation-announcer" aria-live="polite" aria-atomic="true"></div>

    <!-- Card Container -->
    <div class="auth-card" x-data="twoFactorForm()">
        <!-- Logo & Header -->
        <div class="auth-logo-section">
            <div class="auth-logo">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
            <div class="auth-header">
                <h2>Two-Factor Verification</h2>
                <p>Enter the 6-digit code from your authenticator app</p>
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
        <div class="auth-alert auth-alert-error" role="alert">
            <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="auth-alert-content">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="auth-alert auth-alert-warning" role="alert">
            <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="auth-alert-content">
                <span>{{ session('warning') }}</span>
            </div>
        </div>
        @endif

        <!-- 2FA Form -->
        <form method="POST" action="{{ route('two-factor.verify-code') }}" class="auth-form" @submit="handleSubmit">
            @csrf

            <!-- Verification Code -->
            <div class="form-group">
                <label for="code" class="form-label">Authentication Code</label>
                <div class="flex justify-center gap-2">
                    <input
                        type="text"
                        id="code"
                        name="code"
                        x-model="code"
                        @input="onCodeInput"
                        maxlength="6"
                        pattern="[0-9]*"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        class="w-full text-center text-2xl font-mono tracking-[0.5em] py-4 px-4 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900"
                        style="border-color: hsl(240 5.9% 90%); max-width: 220px; letter-spacing: 0.5em;"
                        placeholder="000000"
                        :aria-invalid="errors.code ? 'true' : 'false'"
                    >
                </div>
                <p class="mt-2 text-sm text-center" style="color: hsl(240 3.8% 46.1%);">
                    Open your authenticator app to view your code
                </p>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-form-primary"
                :class="{ 'validating': isSubmitting }"
                :disabled="isSubmitting || code.length !== 6"
                :aria-busy="isSubmitting"
            >
                <span x-show="!isSubmitting">Verify</span>
                <span x-show="isSubmitting" class="sr-only">Verifying...</span>
                <svg x-show="!isSubmitting" class="auth-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </form>

        <!-- Divider -->
        <div class="auth-divider">
            <span>Lost your authenticator device?</span>
        </div>

        <!-- Recovery Code Link -->
        <a href="{{ route('two-factor.recovery') }}" class="btn-form-secondary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            Use a Recovery Code
        </a>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to login
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function twoFactorForm() {
    return {
        code: '',
        errors: { code: '' },
        isSubmitting: false,

        onCodeInput(event) {
            // Only allow numbers
            this.code = this.code.replace(/[^0-9]/g, '');

            // Auto-submit when 6 digits entered
            if (this.code.length === 6) {
                this.errors.code = '';
            }
        },

        handleSubmit(event) {
            if (this.code.length !== 6) {
                event.preventDefault();
                this.errors.code = 'Please enter a 6-digit code';
                return false;
            }

            this.isSubmitting = true;
        }
    };
}
</script>
@endpush
