<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Business Registration Request
 * BIZ-REG-002: Validates business registration data
 */
class BusinessRegistrationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            // SECURITY: Strengthened password policy - minimum 12 characters with complexity requirements
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'company_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'referral_code' => [
                'nullable',
                'string',
                'size:8',
                'exists:business_referrals,referral_code',
            ],
            'terms_accepted' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name',
            'name.min' => 'Name must be at least 2 characters',
            'email.required' => 'Please enter your work email address',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'An account with this email already exists',
            'password.required' => 'Please create a password',
            'password.confirmed' => 'Password confirmation does not match',
            'company_name.required' => 'Please enter your company name',
            'company_name.min' => 'Company name must be at least 2 characters',
            'referral_code.size' => 'Referral code must be 8 characters',
            'referral_code.exists' => 'Invalid referral code',
            'terms_accepted.required' => 'You must accept the terms of service',
            'terms_accepted.accepted' => 'You must accept the terms of service',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'work email',
            'company_name' => 'company name',
            'referral_code' => 'referral code',
            'terms_accepted' => 'terms of service',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
            'name' => trim($this->name ?? ''),
            'company_name' => trim($this->company_name ?? ''),
            'referral_code' => strtoupper(trim($this->referral_code ?? '')),
        ]);
    }
}
