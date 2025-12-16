<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Form request for worker registration validation.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Registration is open to all
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
            'marketing_consent' => ['nullable', 'boolean'],

            // Optional fields
            'referral_code' => ['nullable', 'string', 'max:20'],
            'agency_invitation_token' => ['nullable', 'string', 'max:64'],

            // Location fields (optional during registration)
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],

            // Registration method specific fields handled below
        ];

        // Email registration
        if ($this->has('email') || !$this->has('phone')) {
            $rules['email'] = [
                'required_without:phone',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ];
        }

        // Phone registration
        if ($this->has('phone')) {
            $rules['phone'] = [
                'required_without:email',
                'string',
                'min:10',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/',
                'unique:users,phone',
            ];
            $rules['phone_country_code'] = ['nullable', 'string', 'max:5'];
        }

        // Password is required for email/phone registration (not social)
        // SECURITY: Strengthened password policy - minimum 12 characters with complexity requirements
        if (!$this->has('social_provider')) {
            $rules['password'] = [
                'required',
                'string',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ];
        }

        // Social registration
        if ($this->has('social_provider')) {
            $rules['social_provider'] = ['required', 'string', 'in:google,apple,facebook'];
            $rules['social_id'] = ['required', 'string'];
            $rules['social_avatar'] = ['nullable', 'url'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'name.min' => 'Your name must be at least 2 characters.',
            'email.required_without' => 'Please enter your email address or phone number.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered. Please login instead.',
            'phone.required_without' => 'Please enter your phone number or email address.',
            'phone.regex' => 'Please enter a valid phone number.',
            'phone.unique' => 'This phone number is already registered. Please login instead.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'Your password must be at least 12 characters.',
            'terms_accepted.required' => 'You must accept the Terms of Service to register.',
            'terms_accepted.accepted' => 'You must accept the Terms of Service to register.',
            'privacy_accepted.required' => 'You must accept the Privacy Policy to register.',
            'privacy_accepted.accepted' => 'You must accept the Privacy Policy to register.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'phone' => 'phone number',
            'password' => 'password',
            'phone_country_code' => 'country code',
            'referral_code' => 'referral code',
            'terms_accepted' => 'terms of service',
            'privacy_accepted' => 'privacy policy',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        // Normalize phone number (remove spaces, dashes, etc.)
        if ($this->has('phone')) {
            $phone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge([
                'phone' => $phone,
            ]);
        }

        // Uppercase referral code
        if ($this->has('referral_code')) {
            $this->merge([
                'referral_code' => strtoupper(trim($this->referral_code)),
            ]);
        }
    }
}
