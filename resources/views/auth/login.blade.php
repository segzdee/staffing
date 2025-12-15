@extends('layouts.guest')

@section('title', 'Sign In - OvertimeStaff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container">
    <!-- Accessibility: Live region for announcing validation errors -->
    <div id="validation-announcer" class="validation-announcer" aria-live="polite" aria-atomic="true"></div>

    <!-- Card Container -->
    <div class="auth-card" x-data="loginForm()" x-init="init()">
        <!-- Logo & Header -->
        <div class="auth-logo-section">
            <div class="auth-logo">
                <img src="/images/logo.svg" alt="OvertimeStaff" class="auth-logo-img">
            </div>
            <div class="auth-header">
                <h2>Welcome Back</h2>
                <p>Sign in to continue to OvertimeStaff</p>
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

        @if(session('status'))
        <div class="auth-alert auth-alert-success" role="status">
            <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="auth-alert-content">
                <span>{{ session('status') }}</span>
            </div>
        </div>
        @endif

        @if(session('login_required'))
        <div class="auth-alert auth-alert-warning" role="alert">
            <svg class="auth-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="auth-alert-content">
                <span>Please login to continue</span>
            </div>
        </div>
        @endif

        <!-- Login Form -->
        <form method="POST"
              action="{{ route('login') }}"
              class="auth-form"
              @submit="handleSubmit"
              novalidate>
            @csrf

            <!-- Email -->
            <div class="form-group" :class="{ 'has-error': errors.email, 'has-success': touched.email && !errors.email && email }">
                <label for="email" class="form-label">Email Address</label>
                <div class="form-input-wrapper">
                    <div class="form-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        x-model="email"
                        @blur="validateEmail"
                        @input="clearError('email')"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        inputmode="email"
                        minlength="5"
                        maxlength="255"
                        pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                        class="form-input"
                        :class="{ 'form-input-error': errors.email, 'form-input-valid': touched.email && !errors.email && email }"
                        :aria-invalid="errors.email ? 'true' : 'false'"
                        :aria-describedby="errors.email ? 'email-error' : null"
                        placeholder="you@example.com"
                    >
                    <!-- Validation icons -->
                    <svg x-show="touched.email && !errors.email && email"
                         class="validation-icon validation-icon-valid show"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="errors.email"
                         class="validation-icon validation-icon-error show"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
                    </svg>
                </div>
                <!-- Error message -->
                <div x-show="errors.email"
                     x-transition:enter="animate"
                     class="validation-message validation-message-error show"
                     id="email-error"
                     role="alert">
                    <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="errors.email"></span>
                </div>
                @if($errors->has('email'))
                    <span class="validation-message validation-message-error show" role="alert">
                        <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $errors->first('email') }}
                    </span>
                @endif
            </div>

            <!-- Password -->
            <div class="form-group" :class="{ 'has-error': errors.password, 'has-success': touched.password && !errors.password && password }">
                <label for="password" class="form-label">Password</label>
                <div class="form-input-wrapper">
                    <div class="form-input-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        x-model="password"
                        @blur="validatePassword"
                        @input="clearError('password')"
                        required
                        autocomplete="current-password"
                        minlength="8"
                        maxlength="128"
                        class="form-input"
                        :class="{ 'form-input-error': errors.password, 'form-input-valid': touched.password && !errors.password && password }"
                        :aria-invalid="errors.password ? 'true' : 'false'"
                        :aria-describedby="errors.password ? 'password-error' : 'password-hint'"
                        placeholder="Enter your password"
                    >
                    <!-- Validation icons -->
                    <svg x-show="touched.password && !errors.password && password"
                         class="validation-icon validation-icon-valid show"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="errors.password"
                         class="validation-icon validation-icon-error show"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
                    </svg>
                </div>
                <!-- Helper text -->
                <p class="auth-text-small" id="password-hint" x-show="!errors.password && !touched.password">
                    <svg class="auth-icon-sm" style="display: inline-block; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Minimum 8 characters
                </p>
                <!-- Error message -->
                <div x-show="errors.password"
                     x-transition:enter="animate"
                     class="validation-message validation-message-error show"
                     id="password-error"
                     role="alert">
                    <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="errors.password"></span>
                </div>
                @if($errors->has('password'))
                    <span class="validation-message validation-message-error show" role="alert">
                        <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $errors->first('password') }}
                    </span>
                @endif
            </div>

            <!-- Remember Me & Forgot -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <label class="form-checkbox-wrapper">
                    <input
                        type="checkbox"
                        name="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="form-checkbox"
                    >
                    <span class="form-checkbox-label">Remember me</span>
                </label>

                <a href="{{ route('password.request') }}" class="auth-link">
                    Forgot password?
                </a>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-form-primary"
                :class="{ 'validating': isSubmitting }"
                :disabled="isSubmitting"
                :aria-busy="isSubmitting"
            >
                <span x-show="!isSubmitting">Sign In</span>
                <span x-show="isSubmitting" class="sr-only">Signing in...</span>
                <svg x-show="!isSubmitting" class="auth-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </form>

        <!-- Divider -->
        <div class="auth-divider">
            <span>New to OvertimeStaff?</span>
        </div>

        <!-- Register Link -->
        <a
            href="{{ route('register') }}"
            class="btn-form-secondary"
        >
            Create an Account
        </a>
    </div>

    <!-- Quick Login (Development) -->
    @if(config('app.env') === 'local')
    <div class="auth-dev-section">
        <p class="auth-dev-header">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Quick Dev Login
        </p>
        <div class="auth-dev-grid">
            <a href="{{ route('dev.login', 'worker') }}" class="auth-dev-btn">
                Worker
            </a>
            <a href="{{ route('dev.login', 'business') }}" class="auth-dev-btn">
                Business
            </a>
            <a href="{{ route('dev.login', 'agency') }}" class="auth-dev-btn">
                Agency
            </a>
            <a href="{{ route('dev.login', 'agent') }}" class="auth-dev-btn">
                AI Agent
            </a>
            <a href="{{ route('dev.login', 'admin') }}" class="auth-dev-btn">
                Admin
            </a>
        </div>
        <p>
            <a href="{{ route('dev.credentials') }}" class="auth-dev-link">View all credentials</a>
        </p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function loginForm() {
    return {
        email: '{{ old('email') }}',
        password: '',
        errors: {
            email: '',
            password: ''
        },
        touched: {
            email: false,
            password: false
        },
        isSubmitting: false,

        init() {
            // Initialize with old values if present
            if (this.email) {
                this.touched.email = true;
            }
        },

        validateEmail() {
            this.touched.email = true;
            const email = this.email.trim();

            if (!email) {
                this.errors.email = 'Email address is required';
                this.announceError('Email address is required');
                return false;
            }

            // Basic email format validation
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email)) {
                this.errors.email = 'Please enter a valid email address';
                this.announceError('Please enter a valid email address');
                return false;
            }

            this.errors.email = '';
            return true;
        },

        validatePassword() {
            this.touched.password = true;

            if (!this.password) {
                this.errors.password = 'Password is required';
                this.announceError('Password is required');
                return false;
            }

            if (this.password.length < 8) {
                this.errors.password = 'Password must be at least 8 characters';
                this.announceError('Password must be at least 8 characters');
                return false;
            }

            this.errors.password = '';
            return true;
        },

        clearError(field) {
            if (this.touched[field] && this.errors[field]) {
                // Re-validate on input if there was an error
                if (field === 'email') {
                    this.validateEmail();
                } else if (field === 'password') {
                    this.validatePassword();
                }
            }
        },

        validateAll() {
            const emailValid = this.validateEmail();
            const passwordValid = this.validatePassword();
            return emailValid && passwordValid;
        },

        handleSubmit(event) {
            if (!this.validateAll()) {
                event.preventDefault();

                // Focus first field with error
                if (this.errors.email) {
                    document.getElementById('email').focus();
                } else if (this.errors.password) {
                    document.getElementById('password').focus();
                }

                // Add shake animation to inputs with errors
                if (this.errors.email) {
                    this.shakeInput('email');
                }
                if (this.errors.password) {
                    this.shakeInput('password');
                }

                return false;
            }

            this.isSubmitting = true;
        },

        shakeInput(fieldId) {
            const input = document.getElementById(fieldId);
            if (input) {
                input.classList.add('shake');
                setTimeout(() => {
                    input.classList.remove('shake');
                }, 400);
            }
        },

        announceError(message) {
            const announcer = document.getElementById('validation-announcer');
            if (announcer) {
                announcer.textContent = message;
                // Clear after announcement
                setTimeout(() => {
                    announcer.textContent = '';
                }, 1000);
            }
        }
    };
}

// Quick login helper for development
function quickLogin(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    // Trigger Alpine.js model update
    document.getElementById('email').dispatchEvent(new Event('input'));
    document.getElementById('password').dispatchEvent(new Event('input'));
}
</script>
@endpush
