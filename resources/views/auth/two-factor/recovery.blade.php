@extends('layouts.guest')

@section('title', 'Recovery Code - OvertimeStaff')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth-container">
        <!-- Accessibility: Live region for announcing validation errors -->
        <div id="validation-announcer" class="validation-announcer" aria-live="polite" aria-atomic="true"></div>

        <!-- Card Container -->
        <div class="auth-card" x-data="recoveryForm()">
            <!-- Logo & Header -->
            <div class="auth-logo-section">
                <div class="auth-logo">
                    <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                </div>
                <div class="auth-header">
                    <h2>Use a Recovery Code</h2>
                    <p>Enter one of your emergency recovery codes</p>
                </div>
            </div>

            <!-- Warning Message -->
            <div class="auth-alert auth-alert-warning mb-4" role="alert">
                <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="auth-alert-content">
                    <span>Recovery codes can only be used once. After using this code, it will be invalidated.</span>
                </div>
            </div>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="auth-alert auth-alert-error" role="alert">
                    <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
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

            <!-- Recovery Code Form -->
            <form method="POST" action="{{ route('two-factor.recovery.verify') }}" class="auth-form" @submit="handleSubmit">
                @csrf

                <!-- Recovery Code Input -->
                <div class="space-y-2">
                    <x-ui.label for="recovery_code" value="Recovery Code" />
                    <x-ui.input type="text" id="recovery_code" name="recovery_code" x-model="recoveryCode"
                        @input="formatRecoveryCode" required autofocus autocomplete="off"
                        class="w-full text-center text-lg font-mono tracking-wider py-3 px-4 h-auto"
                        placeholder="XXXXXXXXXX" :aria-invalid="errors.recovery_code ? 'true' : 'false'" />
                    <p class="text-sm text-center text-muted-foreground mt-2">
                        Enter a 10-character recovery code
                    </p>
                </div>

                <!-- Submit Button -->
                <x-ui.button type="submit" class="w-full"
                    ::disabled="isSubmitting || recoveryCode.replace(/[^A-Z0-9]/gi, '').length < 10"
                    :aria-busy="isSubmitting">
                    <span x-show="!isSubmitting">Verify Recovery Code</span>
                    <span x-show="isSubmitting" class="sr-only">Verifying...</span>
                    <svg x-show="!isSubmitting" class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </x-ui.button>
            </form>

            <!-- Divider -->
            <div class="auth-divider">
                <span>Have your authenticator app?</span>
            </div>

            <!-- Back to 2FA Verification -->
            <a href="{{ route('two-factor.verify') }}"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Use Authenticator App
            </a>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium" style="color: hsl(240 3.8% 46.1%);">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to login
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        function recoveryForm() {
            return {
                recoveryCode: '',
                errors: { recovery_code: '' },
                isSubmitting: false,

                formatRecoveryCode(event) {
                    // Convert to uppercase and remove invalid characters
                    this.recoveryCode = this.recoveryCode.toUpperCase().replace(/[^A-Z0-9]/g, '');
                },

                handleSubmit(event) {
                    const cleanCode = this.recoveryCode.replace(/[^A-Z0-9]/gi, '');
                    if (cleanCode.length < 10) {
                        event.preventDefault();
                        this.errors.recovery_code = 'Please enter a valid recovery code';
                        return false;
                    }

                    this.isSubmitting = true;
                }
            };
        }
    </script>
@endpush