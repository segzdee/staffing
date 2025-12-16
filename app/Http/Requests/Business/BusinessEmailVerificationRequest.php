<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Business Email Verification Request
 * BIZ-REG-002: Validates email verification data
 */
class BusinessEmailVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'size:64', // 32 bytes as hex
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'token.required' => 'Verification token is required',
            'token.size' => 'Invalid verification token',
            'email.required' => 'Email address is required',
            'email.email' => 'Invalid email address',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim($this->email ?? '')),
            'token' => trim($this->token ?? ''),
        ]);
    }
}
