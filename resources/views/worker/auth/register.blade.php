@extends('layouts.guest')

@section('title', 'Worker Registration - OvertimeStaff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
<style>
    .social-auth-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .social-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: white;
        font-weight: 500;
        color: #374151;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .social-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }
    .social-btn svg {
        width: 1.25rem;
        height: 1.25rem;
    }
    .social-btn-google:hover { border-color: #ea4335; }
    .social-btn-facebook:hover { border-color: #1877f2; }
    .social-btn-apple:hover { border-color: #000; }

    .referral-banner {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }
    .referral-banner-title {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    .referral-banner-subtitle {
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .agency-invite-banner {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .agency-invite-title {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    .agency-invite-message {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-top: 0.5rem;
        font-style: italic;
    }

    .phone-input-group {
        display: flex;
        gap: 0.5rem;
    }
    .country-code-select {
        width: 100px;
        flex-shrink: 0;
    }
    .phone-number-input {
        flex: 1;
    }

    .tab-buttons {
        display: flex;
        border-radius: 0.5rem;
        background: #f3f4f6;
        padding: 0.25rem;
        margin-bottom: 1.5rem;
    }
    .tab-button {
        flex: 1;
        padding: 0.625rem 1rem;
        border: none;
        background: transparent;
        border-radius: 0.375rem;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .tab-button.active {
        background: white;
        color: #111827;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    .tab-button:hover:not(.active) {
        color: #374151;
    }
</style>
@endpush

@section('content')
<div class="auth-container" style="max-width: 32rem;">
    <div id="validation-announcer" class="validation-announcer" aria-live="polite" aria-atomic="true"></div>

    <div class="auth-card" x-data="workerRegisterForm()" x-init="init()">
        <!-- Logo & Header -->
        <div class="auth-logo-section">
            <div class="auth-logo">
                <img src="/images/logo.svg" alt="OvertimeStaff" class="auth-logo-img">
            </div>
            <div class="auth-header">
                <h2>Start Working Today</h2>
                <p>Create your worker account and find shifts near you</p>
            </div>
        </div>

        <!-- Referral Banner -->
        @if($referralInfo)
        <div class="referral-banner">
            <div class="referral-banner-title">
                {{ $referralInfo['referrer_name'] }} invited you!
            </div>
            <div class="referral-banner-subtitle">
                Complete 3 shifts to earn ${{ number_format($referralInfo['referee_reward'], 2) }} bonus
            </div>
            <input type="hidden" name="referral_code" value="{{ $referralInfo['code'] }}">
        </div>
        @endif

        <!-- Agency Invitation Banner -->
        @if($invitationInfo)
        <div class="agency-invite-banner">
            <div class="agency-invite-title">
                {{ $invitationInfo['agency_name'] }} is inviting you to join their team
            </div>
            @if($invitationInfo['message'])
            <div class="agency-invite-message">
                "{{ $invitationInfo['message'] }}"
            </div>
            @endif
            <input type="hidden" name="agency_invitation_token" value="{{ $invitationInfo['token'] }}">
        </div>
        @endif

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

        <!-- Social Auth Buttons -->
        <div class="social-auth-buttons">
            <a href="{{ route('oauth.redirect', ['provider' => 'google']) }}{{ $referralInfo ? '?ref=' . $referralInfo['code'] : '' }}" class="social-btn social-btn-google">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#EA4335" d="M5.26620003,9.76452941 C6.19878754,6.93863203 8.85444915,4.90909091 12,4.90909091 C13.6909091,4.90909091 15.2181818,5.50909091 16.4181818,6.49090909 L19.9090909,3 C17.7818182,1.14545455 15.0545455,0 12,0 C7.27006974,0 3.1977497,2.69829785 1.23999023,6.65002441 L5.26620003,9.76452941 Z"/>
                    <path fill="#34A853" d="M16.0407269,18.0125889 C14.9509167,18.7163016 13.5660892,19.0909091 12,19.0909091 C8.86648613,19.0909091 6.21911939,17.076871 5.27698177,14.2678769 L1.23746264,17.3349879 C3.19279051,21.2936293 7.26500293,24 12,24 C14.9328362,24 17.7353462,22.9573905 19.834192,20.9995801 L16.0407269,18.0125889 Z"/>
                    <path fill="#4A90E2" d="M19.834192,20.9995801 C22.0291676,18.9520994 23.4545455,15.903663 23.4545455,12 C23.4545455,11.2909091 23.3454545,10.5272727 23.1818182,9.81818182 L12,9.81818182 L12,14.4545455 L18.4363636,14.4545455 C18.1187732,16.013626 17.2662994,17.2212117 16.0407269,18.0125889 L19.834192,20.9995801 Z"/>
                    <path fill="#FBBC05" d="M5.27698177,14.2678769 C5.03832634,13.556323 4.90909091,12.7937589 4.90909091,12 C4.90909091,11.2182781 5.03443647,10.4668121 5.26620003,9.76452941 L1.23999023,6.65002441 C0.43658717,8.26043162 0,10.0753848 0,12 C0,13.9195484 0.444780743,15.7## C1.23746264,17.3349879 L5.27698177,14.2678769 Z"/>
                </svg>
                Continue with Google
            </a>
            <a href="{{ route('oauth.redirect', ['provider' => 'facebook']) }}{{ $referralInfo ? '?ref=' . $referralInfo['code'] : '' }}" class="social-btn social-btn-facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Continue with Facebook
            </a>
            <a href="{{ route('oauth.redirect', ['provider' => 'apple']) }}{{ $referralInfo ? '?ref=' . $referralInfo['code'] : '' }}" class="social-btn social-btn-apple">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#000" d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                </svg>
                Continue with Apple
            </a>
        </div>

        <!-- Divider -->
        <div class="auth-divider">
            <span>Or register with</span>
        </div>

        <!-- Tab Buttons -->
        <div class="tab-buttons">
            <button type="button"
                    class="tab-button"
                    :class="{ 'active': registrationMethod === 'email' }"
                    @click="registrationMethod = 'email'">
                Email
            </button>
            <button type="button"
                    class="tab-button"
                    :class="{ 'active': registrationMethod === 'phone' }"
                    @click="registrationMethod = 'phone'">
                Phone
            </button>
        </div>

        <!-- Registration Form -->
        <form id="registration-form"
              method="POST"
              action="{{ route('api.worker.register') }}"
              class="auth-form"
              @submit.prevent="handleSubmit"
              novalidate>
            @csrf

            <!-- Hidden fields -->
            <input type="hidden" name="terms_accepted" x-model="termsAccepted">
            <input type="hidden" name="privacy_accepted" x-model="privacyAccepted">
            @if($referralInfo)
            <input type="hidden" name="referral_code" value="{{ $referralInfo['code'] }}">
            @endif
            @if($invitationInfo)
            <input type="hidden" name="agency_invitation_token" value="{{ $invitationInfo['token'] }}">
            @endif

            <!-- Name Field -->
            <div class="space-y-2" :class="{ 'has-error': errors.name }">
                <x-ui.label for="name" value="Full Name" />
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <x-ui.input
                           type="text"
                           id="name"
                           name="name"
                           x-model="name"
                           @blur="validateName"
                           value="{{ $invitationInfo['invitee_name'] ?? old('name') }}"
                           required
                           autofocus
                           autocomplete="name"
                           class="pl-10"
                           :class="{ 'border-destructive focus-visible:ring-destructive': errors.name }"
                           placeholder="John Doe" />
                </div>
                <p x-show="errors.name" x-text="errors.name" class="text-sm text-destructive mt-1" role="alert"></p>
            </div>

            <!-- Email Field (shown when email tab active) -->
            <div class="space-y-2" x-show="registrationMethod === 'email'" :class="{ 'has-error': errors.email }">
                <x-ui.label for="email" value="Email Address" />
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <x-ui.input
                           type="email"
                           id="email"
                           name="email"
                           x-model="email"
                           @blur="validateEmail"
                           value="{{ $invitationInfo['invitee_email'] ?? old('email') }}"
                           :required="registrationMethod === 'email'"
                           autocomplete="email"
                           class="pl-10"
                           :class="{ 'border-destructive focus-visible:ring-destructive': errors.email }"
                           placeholder="you@example.com" />
                </div>
                <p x-show="errors.email" x-text="errors.email" class="text-sm text-destructive mt-1" role="alert"></p>
            </div>

            <!-- Phone Field (shown when phone tab active) -->
            <div class="space-y-2" x-show="registrationMethod === 'phone'" :class="{ 'has-error': errors.phone }">
                <x-ui.label for="phone" value="Phone Number" />
                <div class="flex gap-2">
                    <div class="w-32 shrink-0">
                        <select name="phone_country_code" x-model="phoneCountryCode" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <option value="+1">+1 (US)</option>
                            <option value="+44">+44 (UK)</option>
                            <option value="+61">+61 (AU)</option>
                            <option value="+91">+91 (IN)</option>
                            <option value="+234">+234 (NG)</option>
                            <option value="+27">+27 (ZA)</option>
                            <option value="+49">+49 (DE)</option>
                            <option value="+33">+33 (FR)</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <x-ui.input
                               type="tel"
                               id="phone"
                               name="phone"
                               x-model="phone"
                               @blur="validatePhone"
                               value="{{ $invitationInfo['invitee_phone'] ?? old('phone') }}"
                               :required="registrationMethod === 'phone'"
                               autocomplete="tel"
                               :class="{ 'border-destructive focus-visible:ring-destructive': errors.phone }"
                               placeholder="(555) 123-4567" />
                    </div>
                </div>
                <p x-show="errors.phone" x-text="errors.phone" class="text-sm text-destructive mt-1" role="alert"></p>
            </div>

            <!-- Password Fields -->
            <div class="space-y-2" :class="{ 'has-error': errors.password }">
                <x-ui.label for="password" value="Password" />
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <x-ui.input
                           type="password"
                           id="password"
                           name="password"
                           x-model="password"
                           @blur="validatePassword"
                           @input="checkPasswordStrength"
                           required
                           autocomplete="new-password"
                           class="pl-10"
                           :class="{ 'border-destructive focus-visible:ring-destructive': errors.password }"
                           placeholder="Min 8 chars, 1 number, 1 special" />
                </div>

                <!-- Password Strength Indicator -->
                <div class="flex items-center gap-2 mt-2" x-show="password.length > 0">
                    <div class="text-xs text-muted-foreground">
                        <span>Strength:</span>
                        <span x-text="passwordStrengthLabel" :style="{ color: passwordStrengthColor }" class="font-medium"></span>
                    </div>
                    <div class="flex-1 h-1.5 bg-secondary rounded-full overflow-hidden">
                        <div class="h-full transition-all duration-300" :class="{
                            'bg-destructive': passwordStrength === 'weak',
                            'bg-warning': passwordStrength === 'fair',
                            'bg-primary': passwordStrength === 'good',
                            'bg-success': passwordStrength === 'strong'
                        }" :style="{ width: passwordStrength === 'weak' ? '25%' : (passwordStrength === 'fair' ? '50%' : (passwordStrength === 'good' ? '75%' : '100%')) }"></div>
                    </div>
                </div>

                <p x-show="errors.password" x-text="errors.password" class="text-sm text-destructive mt-1" role="alert"></p>
            </div>

            <div class="space-y-2" :class="{ 'has-error': errors.password_confirmation }">
                <x-ui.label for="password_confirmation" value="Confirm Password" />
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <x-ui.input
                           type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           x-model="password_confirmation"
                           @blur="validatePasswordConfirmation"
                           required
                           autocomplete="new-password"
                           class="pl-10"
                           :class="{ 'border-destructive focus-visible:ring-destructive': errors.password_confirmation }"
                           placeholder="Confirm your password" />
                </div>
                <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="text-sm text-destructive mt-1" role="alert"></p>
            </div>

            <!-- Referral Code (if not already provided) -->
            @if(!$referralInfo)
            <div class="space-y-2" x-show="showReferralField">
                <x-ui.label for="referral_code" value="Referral Code (Optional)" />
                <div class="relative">
                     <x-ui.input
                           type="text"
                           id="referral_code"
                           name="referral_code"
                           x-model="referralCode"
                           @blur="validateReferralCode"
                           class=""
                           :class="{ 'border-destructive focus-visible:ring-destructive': errors.referral_code, 'border-success focus-visible:ring-success': referralValid }"
                           placeholder="Enter code" />
                </div>
                <p x-show="errors.referral_code" x-text="errors.referral_code" class="text-sm text-destructive mt-1" role="alert"></p>
                <p x-show="referralValid" x-text="referralMessage" class="text-sm text-success mt-1"></p>
            </div>
            <button type="button"
                    x-show="!showReferralField"
                    @click="showReferralField = true"
                    class="text-sm text-primary hover:underline mb-4 font-medium">
                Have a referral code?
            </button>
            @endif

            <!-- Terms Agreement -->
            <div class="flex items-start space-x-2" :class="{ 'text-destructive': errors.terms }">
                <input type="checkbox"
                       id="terms"
                       x-model="termsAccepted"
                       @change="privacyAccepted = termsAccepted"
                       required
                       class="mt-1 h-4 w-4 rounded border-input text-primary focus:ring-ring">
                <label for="terms" class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    I agree to the
                    <a href="/terms" target="_blank" class="font-medium text-primary hover:underline" rel="noopener noreferrer">Terms of Service</a>
                    and
                    <a href="/privacy" target="_blank" class="font-medium text-primary hover:underline" rel="noopener noreferrer">Privacy Policy</a>
                </label>
            </div>
            <p x-show="errors.terms" x-text="errors.terms" class="text-sm text-destructive mt-1 ml-6" role="alert"></p>

            <!-- Marketing Consent -->
            <div class="flex items-start space-x-2 mt-4">
                <input type="checkbox"
                       id="marketing_consent"
                       name="marketing_consent"
                       x-model="marketingConsent"
                       class="mt-1 h-4 w-4 rounded border-input text-primary focus:ring-ring">
                <label for="marketing_consent" class="text-sm text-muted-foreground leading-snug">
                    Send me updates about new shifts and features (optional)
                </label>
            </div>

            <!-- Submit Button -->
            <x-ui.button type="submit"
                    class="w-full mt-6"
                    ::disabled="isSubmitting">
                <span x-show="!isSubmitting">Create Account</span>
                <span x-show="isSubmitting">Creating account...</span>
            </x-ui.button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t border-border"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-card px-2 text-muted-foreground">Or register with</span>
            </div>
        </div>

        <!-- Login Link -->
        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 w-full">
            Sign In Instead
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function workerRegisterForm() {
    return {
        registrationMethod: 'email',
        name: @json($invitationInfo['invitee_name'] ?? old('name') ?? ''),
        email: @json($invitationInfo['invitee_email'] ?? old('email') ?? ''),
        phone: @json($invitationInfo['invitee_phone'] ?? old('phone') ?? ''),
        phoneCountryCode: '+1',
        password: '',
        password_confirmation: '',
        referralCode: '{{ request()->query('ref', '') }}',
        showReferralField: {{ request()->has('ref') ? 'true' : 'false' }},
        referralValid: false,
        referralMessage: '',
        termsAccepted: false,
        privacyAccepted: false,
        marketingConsent: false,
        isSubmitting: false,
        errors: {},
        passwordStrength: '',
        passwordStrengthLabel: '',
        passwordStrengthColor: '#6b7280',

        init() {
            // Any initialization logic
        },

        validateName() {
            const name = this.name.trim();
            if (!name) {
                this.errors.name = 'Please enter your full name';
                return false;
            }
            if (name.length < 2) {
                this.errors.name = 'Name must be at least 2 characters';
                return false;
            }
            delete this.errors.name;
            return true;
        },

        validateEmail() {
            if (this.registrationMethod !== 'email') return true;

            const email = this.email.trim();
            if (!email) {
                this.errors.email = 'Please enter your email address';
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                this.errors.email = 'Please enter a valid email address';
                return false;
            }
            delete this.errors.email;
            return true;
        },

        validatePhone() {
            if (this.registrationMethod !== 'phone') return true;

            const phone = this.phone.replace(/\D/g, '');
            if (!phone) {
                this.errors.phone = 'Please enter your phone number';
                return false;
            }
            if (phone.length < 10) {
                this.errors.phone = 'Please enter a valid phone number';
                return false;
            }
            delete this.errors.phone;
            return true;
        },

        checkPasswordStrength() {
            const password = this.password;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;

            if (strength <= 1) {
                this.passwordStrength = 'weak';
                this.passwordStrengthLabel = 'Weak';
                this.passwordStrengthColor = '#ef4444';
            } else if (strength === 2) {
                this.passwordStrength = 'fair';
                this.passwordStrengthLabel = 'Fair';
                this.passwordStrengthColor = '#f59e0b';
            } else if (strength === 3) {
                this.passwordStrength = 'good';
                this.passwordStrengthLabel = 'Good';
                this.passwordStrengthColor = '#3b82f6';
            } else {
                this.passwordStrength = 'strong';
                this.passwordStrengthLabel = 'Strong';
                this.passwordStrengthColor = '#10b981';
            }
        },

        validatePassword() {
            if (!this.password) {
                this.errors.password = 'Please create a password';
                return false;
            }
            if (this.password.length < 8) {
                this.errors.password = 'Password must be at least 8 characters';
                return false;
            }
            if (!/[0-9]/.test(this.password)) {
                this.errors.password = 'Password must contain at least one number';
                return false;
            }
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(this.password)) {
                this.errors.password = 'Password must contain at least one special character';
                return false;
            }
            delete this.errors.password;
            return true;
        },

        validatePasswordConfirmation() {
            if (!this.password_confirmation) {
                this.errors.password_confirmation = 'Please confirm your password';
                return false;
            }
            if (this.password !== this.password_confirmation) {
                this.errors.password_confirmation = 'Passwords do not match';
                return false;
            }
            delete this.errors.password_confirmation;
            return true;
        },

        async validateReferralCode() {
            if (!this.referralCode) {
                this.referralValid = false;
                this.referralMessage = '';
                delete this.errors.referral_code;
                return true;
            }

            try {
                const response = await fetch('/api/worker/validate-referral', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ code: this.referralCode })
                });
                const data = await response.json();

                if (data.valid) {
                    this.referralValid = true;
                    this.referralMessage = `Valid! Earn $${data.data.referee_reward} after completing shifts`;
                    delete this.errors.referral_code;
                    return true;
                } else {
                    this.referralValid = false;
                    this.errors.referral_code = data.message;
                    return false;
                }
            } catch (error) {
                console.error('Error validating referral code:', error);
                return true; // Don't block registration for referral validation errors
            }
        },

        validateTerms() {
            if (!this.termsAccepted) {
                this.errors.terms = 'You must agree to the Terms of Service and Privacy Policy';
                return false;
            }
            delete this.errors.terms;
            return true;
        },

        validateAll() {
            const results = [
                this.validateName(),
                this.registrationMethod === 'email' ? this.validateEmail() : this.validatePhone(),
                this.validatePassword(),
                this.validatePasswordConfirmation(),
                this.validateTerms()
            ];
            return results.every(r => r);
        },

        async handleSubmit() {
            if (!this.validateAll()) {
                return;
            }

            this.isSubmitting = true;

            try {
                const formData = {
                    name: this.name,
                    password: this.password,
                    password_confirmation: this.password_confirmation,
                    terms_accepted: this.termsAccepted,
                    privacy_accepted: this.privacyAccepted,
                    marketing_consent: this.marketingConsent
                };

                if (this.registrationMethod === 'email') {
                    formData.email = this.email;
                } else {
                    formData.phone = this.phone;
                    formData.phone_country_code = this.phoneCountryCode;
                }

                if (this.referralCode) {
                    formData.referral_code = this.referralCode;
                }

                @if($invitationInfo)
                formData.agency_invitation_token = '{{ $invitationInfo['token'] }}';
                @endif

                const response = await fetch('/api/worker/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to verification page
                    window.location.href = data.data.redirect_url;
                } else {
                    // Show validation errors
                    if (data.errors) {
                        this.errors = {};
                        for (const [field, messages] of Object.entries(data.errors)) {
                            this.errors[field] = messages[0];
                        }
                    } else {
                        alert(data.message || 'Registration failed. Please try again.');
                    }
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush
