@extends('layouts.guest')

@section('title', 'Create Account - OvertimeStaff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<div class="auth-container" style="max-width: 32rem;">
    <!-- Accessibility: Live region for announcing validation errors -->
    <div id="validation-announcer" class="validation-announcer" aria-live="polite" aria-atomic="true"></div>

    <!-- Card Container -->
    <div class="auth-card" x-data="registerForm()" x-init="init()">
        <!-- Logo & Header -->
        <div class="auth-logo-section">
            <div class="auth-logo">
                <img src="/images/logo.svg" alt="OvertimeStaff" class="auth-logo-img">
            </div>
            <div class="auth-header">
                <h2>Create Your Account</h2>
                <p>Join OvertimeStaff and start connecting</p>
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

        <!-- Registration Form -->
        <form method="POST"
              action="{{ route('register') }}"
              class="auth-form"
              @submit="handleSubmit"
              novalidate>
            @csrf

            <!-- User Type Selection -->
            <div class="form-group">
                <label class="form-label" id="user-type-label">I want to</label>
                <div class="form-radio-group" role="radiogroup" aria-labelledby="user-type-label">
                    <label class="form-radio-option">
                        <input type="radio"
                               name="user_type"
                               value="worker"
                               x-model="userType"
                               {{ old('user_type', $type ?? 'worker') == 'worker' ? 'checked' : '' }}
                               required
                               aria-describedby="worker-desc">
                        <div class="form-radio-card">
                            <div class="form-radio-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="form-radio-title">Find Work</p>
                            <p class="form-radio-description" id="worker-desc">I'm a worker looking for shifts</p>
                        </div>
                    </label>

                    <label class="form-radio-option">
                        <input type="radio"
                               name="user_type"
                               value="business"
                               x-model="userType"
                               {{ old('user_type', $type ?? 'worker') == 'business' ? 'checked' : '' }}
                               required
                               aria-describedby="business-desc">
                        <div class="form-radio-card">
                            <div class="form-radio-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <p class="form-radio-title">Hire Workers</p>
                            <p class="form-radio-description" id="business-desc">I'm a business looking to hire</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="auth-form-grid">
                <!-- Name -->
                <div class="form-group" :class="{ 'has-error': errors.name, 'has-success': touched.name && !errors.name && name }">
                    <label for="name" class="form-label">Full Name <span class="required" aria-hidden="true">*</span></label>
                    <div class="form-input-wrapper">
                        <div class="form-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            x-model="name"
                            @blur="validateName"
                            @input="clearError('name')"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            minlength="2"
                            maxlength="255"
                            class="form-input"
                            :class="{ 'form-input-error': errors.name, 'form-input-valid': touched.name && !errors.name && name }"
                            :aria-invalid="errors.name ? 'true' : 'false'"
                            :aria-describedby="errors.name ? 'name-error' : null"
                            placeholder="John Doe"
                        >
                        <!-- Validation icons -->
                        <svg x-show="touched.name && !errors.name && name"
                             class="validation-icon validation-icon-valid show"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="errors.name"
                             class="validation-icon validation-icon-error show"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
                        </svg>
                    </div>
                    <!-- Error message -->
                    <div x-show="errors.name"
                         x-transition:enter="animate"
                         class="validation-message validation-message-error show"
                         id="name-error"
                         role="alert">
                        <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="errors.name"></span>
                    </div>
                    @if($errors->has('name'))
                        <span class="validation-message validation-message-error show" role="alert">
                            <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $errors->first('name') }}
                        </span>
                    @endif
                </div>

                <!-- Email -->
                <div class="form-group" :class="{ 'has-error': errors.email, 'has-success': touched.email && !errors.email && email }">
                    <label for="email" class="form-label">Email Address <span class="required" aria-hidden="true">*</span></label>
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
                <div class="form-group" :class="{ 'has-error': errors.password, 'has-success': touched.password && !errors.password && passwordStrength === 'strong' }">
                    <label for="password" class="form-label">Password <span class="required" aria-hidden="true">*</span></label>
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
                            @input="onPasswordInput"
                            required
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="128"
                            class="form-input"
                            :class="{ 'form-input-error': errors.password, 'form-input-valid': touched.password && !errors.password && passwordStrength === 'strong' }"
                            :aria-invalid="errors.password ? 'true' : 'false'"
                            :aria-describedby="'password-requirements ' + (errors.password ? 'password-error' : '')"
                            placeholder="Create a password"
                        >
                        <!-- Validation icons -->
                        <svg x-show="touched.password && !errors.password && passwordStrength === 'strong'"
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

                    <!-- Password strength indicator -->
                    <div class="password-strength" x-show="password.length > 0">
                        <div class="password-strength-label">
                            <span>Password strength</span>
                            <span x-text="passwordStrengthLabel" :style="{ color: passwordStrengthColor }"></span>
                        </div>
                        <div class="password-strength-meter" role="progressbar" :aria-valuenow="passwordStrengthPercent" aria-valuemin="0" aria-valuemax="100">
                            <div class="password-strength-fill" :class="passwordStrength"></div>
                        </div>
                    </div>

                    <!-- Password requirements checklist -->
                    <div class="password-requirements" id="password-requirements" x-show="password.length > 0 || touched.password">
                        <p class="password-requirements-title">Password must have:</p>
                        <div class="password-requirement" :class="{ 'met': requirements.minLength }">
                            <svg x-show="requirements.minLength" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!requirements.minLength" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            </svg>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="password-requirement" :class="{ 'met': requirements.hasUppercase }">
                            <svg x-show="requirements.hasUppercase" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!requirements.hasUppercase" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            </svg>
                            <span>One uppercase letter</span>
                        </div>
                        <div class="password-requirement" :class="{ 'met': requirements.hasLowercase }">
                            <svg x-show="requirements.hasLowercase" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!requirements.hasLowercase" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            </svg>
                            <span>One lowercase letter</span>
                        </div>
                        <div class="password-requirement" :class="{ 'met': requirements.hasNumber }">
                            <svg x-show="requirements.hasNumber" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!requirements.hasNumber" class="password-requirement-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            </svg>
                            <span>One number</span>
                        </div>
                    </div>

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

                <!-- Confirm Password -->
                <div class="form-group" :class="{ 'has-error': errors.password_confirmation, 'has-success': touched.password_confirmation && !errors.password_confirmation && password_confirmation && passwordsMatch }">
                    <label for="password_confirmation" class="form-label">Confirm Password <span class="required" aria-hidden="true">*</span></label>
                    <div class="form-input-wrapper">
                        <div class="form-input-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            x-model="password_confirmation"
                            @blur="validatePasswordConfirmation"
                            @input="clearError('password_confirmation')"
                            required
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="128"
                            class="form-input"
                            :class="{ 'form-input-error': errors.password_confirmation, 'form-input-valid': touched.password_confirmation && !errors.password_confirmation && password_confirmation && passwordsMatch }"
                            :aria-invalid="errors.password_confirmation ? 'true' : 'false'"
                            :aria-describedby="errors.password_confirmation ? 'password-confirmation-error' : null"
                            placeholder="Confirm your password"
                        >
                        <!-- Validation icons -->
                        <svg x-show="touched.password_confirmation && !errors.password_confirmation && password_confirmation && passwordsMatch"
                             class="validation-icon validation-icon-valid show"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="errors.password_confirmation"
                             class="validation-icon validation-icon-error show"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
                        </svg>
                    </div>
                    <!-- Match indicator -->
                    <div x-show="password_confirmation && !errors.password_confirmation && passwordsMatch"
                         class="validation-message validation-message-success show">
                        <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Passwords match</span>
                    </div>
                    <!-- Error message -->
                    <div x-show="errors.password_confirmation"
                         x-transition:enter="animate"
                         class="validation-message validation-message-error show"
                         id="password-confirmation-error"
                         role="alert">
                        <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="errors.password_confirmation"></span>
                    </div>
                    @if($errors->has('password_confirmation'))
                        <span class="validation-message validation-message-error show" role="alert">
                            <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $errors->first('password_confirmation') }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- Terms Agreement -->
            <div class="auth-terms-box" :class="{ 'has-error': errors.agree_terms }">
                <label class="form-checkbox-wrapper">
                    <input
                        type="checkbox"
                        name="agree_terms"
                        x-model="agreeTerms"
                        @change="validateTerms"
                        required
                        class="form-checkbox"
                        :aria-invalid="errors.agree_terms ? 'true' : 'false'"
                        :aria-describedby="errors.agree_terms ? 'terms-error' : null"
                    >
                    <span class="form-checkbox-label">
                        I agree to the
                        <a href="/terms" target="_blank" class="auth-link" rel="noopener noreferrer">Terms of Service</a>
                        and
                        <a href="/privacy" target="_blank" class="auth-link" rel="noopener noreferrer">Privacy Policy</a>
                    </span>
                </label>
                <!-- Error message -->
                <div x-show="errors.agree_terms"
                     x-transition:enter="animate"
                     class="validation-message validation-message-error show"
                     id="terms-error"
                     role="alert"
                     style="margin-top: 8px;">
                    <svg class="validation-message-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="errors.agree_terms"></span>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="btn-form-primary"
                :class="{ 'validating': isSubmitting }"
                :disabled="isSubmitting"
                :aria-busy="isSubmitting"
            >
                <span x-show="!isSubmitting">Create Account</span>
                <span x-show="isSubmitting" class="sr-only">Creating account...</span>
                <svg x-show="!isSubmitting" class="auth-icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </form>

        <!-- Divider -->
        <div class="auth-divider">
            <span>Already have an account?</span>
        </div>

        <!-- Login Link -->
        <a
            href="{{ route('login') }}"
            class="btn-form-secondary"
        >
            Sign In Instead
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function registerForm() {
    return {
        name: '{{ old('name') }}',
        email: '{{ old('email') }}',
        password: '',
        password_confirmation: '',
        userType: '{{ old('user_type', $type ?? 'worker') }}',
        agreeTerms: false,
        errors: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            agree_terms: ''
        },
        touched: {
            name: false,
            email: false,
            password: false,
            password_confirmation: false
        },
        requirements: {
            minLength: false,
            hasUppercase: false,
            hasLowercase: false,
            hasNumber: false
        },
        isSubmitting: false,

        init() {
            // Initialize with old values if present
            if (this.name) this.touched.name = true;
            if (this.email) this.touched.email = true;
        },

        get passwordsMatch() {
            return this.password && this.password_confirmation && this.password === this.password_confirmation;
        },

        get passwordStrength() {
            if (!this.password) return '';

            let strength = 0;
            if (this.requirements.minLength) strength++;
            if (this.requirements.hasUppercase) strength++;
            if (this.requirements.hasLowercase) strength++;
            if (this.requirements.hasNumber) strength++;

            if (strength <= 1) return 'weak';
            if (strength === 2) return 'fair';
            if (strength === 3) return 'good';
            return 'strong';
        },

        get passwordStrengthLabel() {
            const labels = {
                'weak': 'Weak',
                'fair': 'Fair',
                'good': 'Good',
                'strong': 'Strong'
            };
            return labels[this.passwordStrength] || '';
        },

        get passwordStrengthColor() {
            const colors = {
                'weak': '#EF4444',
                'fair': '#F59E0B',
                'good': '#3B82F6',
                'strong': '#10B981'
            };
            return colors[this.passwordStrength] || '#6B7280';
        },

        get passwordStrengthPercent() {
            const percents = {
                'weak': 25,
                'fair': 50,
                'good': 75,
                'strong': 100
            };
            return percents[this.passwordStrength] || 0;
        },

        validateName() {
            this.touched.name = true;
            const name = this.name.trim();

            if (!name) {
                this.errors.name = 'Full name is required';
                this.announceError('Full name is required');
                return false;
            }

            if (name.length < 2) {
                this.errors.name = 'Name must be at least 2 characters';
                this.announceError('Name must be at least 2 characters');
                return false;
            }

            if (name.length > 255) {
                this.errors.name = 'Name must be less than 255 characters';
                this.announceError('Name must be less than 255 characters');
                return false;
            }

            // Check for valid characters (letters, spaces, hyphens, apostrophes)
            const nameRegex = /^[a-zA-Z\s\-']+$/;
            if (!nameRegex.test(name)) {
                this.errors.name = 'Name can only contain letters, spaces, hyphens, and apostrophes';
                this.announceError('Name can only contain letters, spaces, hyphens, and apostrophes');
                return false;
            }

            this.errors.name = '';
            return true;
        },

        validateEmail() {
            this.touched.email = true;
            const email = this.email.trim();

            if (!email) {
                this.errors.email = 'Email address is required';
                this.announceError('Email address is required');
                return false;
            }

            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(email)) {
                this.errors.email = 'Please enter a valid email address';
                this.announceError('Please enter a valid email address');
                return false;
            }

            this.errors.email = '';
            return true;
        },

        checkPasswordRequirements() {
            this.requirements.minLength = this.password.length >= 8;
            this.requirements.hasUppercase = /[A-Z]/.test(this.password);
            this.requirements.hasLowercase = /[a-z]/.test(this.password);
            this.requirements.hasNumber = /[0-9]/.test(this.password);
        },

        onPasswordInput() {
            this.checkPasswordRequirements();
            this.clearError('password');

            // Re-validate password confirmation if it's been touched
            if (this.touched.password_confirmation && this.password_confirmation) {
                this.validatePasswordConfirmation();
            }
        },

        validatePassword() {
            this.touched.password = true;
            this.checkPasswordRequirements();

            if (!this.password) {
                this.errors.password = 'Password is required';
                this.announceError('Password is required');
                return false;
            }

            if (!this.requirements.minLength) {
                this.errors.password = 'Password must be at least 8 characters';
                this.announceError('Password must be at least 8 characters');
                return false;
            }

            // For registration, require stronger passwords
            if (!this.requirements.hasUppercase || !this.requirements.hasLowercase || !this.requirements.hasNumber) {
                this.errors.password = 'Password must include uppercase, lowercase, and a number';
                this.announceError('Password must include uppercase, lowercase, and a number');
                return false;
            }

            this.errors.password = '';
            return true;
        },

        validatePasswordConfirmation() {
            this.touched.password_confirmation = true;

            if (!this.password_confirmation) {
                this.errors.password_confirmation = 'Please confirm your password';
                this.announceError('Please confirm your password');
                return false;
            }

            if (this.password !== this.password_confirmation) {
                this.errors.password_confirmation = 'Passwords do not match';
                this.announceError('Passwords do not match');
                return false;
            }

            this.errors.password_confirmation = '';
            return true;
        },

        validateTerms() {
            if (!this.agreeTerms) {
                this.errors.agree_terms = 'You must agree to the Terms of Service and Privacy Policy';
                this.announceError('You must agree to the Terms of Service and Privacy Policy');
                return false;
            }

            this.errors.agree_terms = '';
            return true;
        },

        clearError(field) {
            if (this.touched[field] && this.errors[field]) {
                switch(field) {
                    case 'name':
                        this.validateName();
                        break;
                    case 'email':
                        this.validateEmail();
                        break;
                    case 'password':
                        this.validatePassword();
                        break;
                    case 'password_confirmation':
                        this.validatePasswordConfirmation();
                        break;
                }
            }
        },

        validateAll() {
            const nameValid = this.validateName();
            const emailValid = this.validateEmail();
            const passwordValid = this.validatePassword();
            const passwordConfirmValid = this.validatePasswordConfirmation();
            const termsValid = this.validateTerms();

            return nameValid && emailValid && passwordValid && passwordConfirmValid && termsValid;
        },

        handleSubmit(event) {
            if (!this.validateAll()) {
                event.preventDefault();

                // Focus first field with error
                const errorFields = ['name', 'email', 'password', 'password_confirmation'];
                for (const field of errorFields) {
                    if (this.errors[field]) {
                        document.getElementById(field).focus();
                        this.shakeInput(field);
                        break;
                    }
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
                setTimeout(() => {
                    announcer.textContent = '';
                }, 1000);
            }
        }
    };
}
</script>
@endpush
