<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Form request for email verification.
 */
class VerifyEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required_without:token',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
            'token' => [
                'required_without:code',
                'string',
                'size:64',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required_without' => 'Please enter the verification code sent to your email.',
            'code.size' => 'The verification code must be exactly 6 digits.',
            'code.regex' => 'The verification code must contain only numbers.',
            'token.required_without' => 'Please provide a verification code or token.',
            'token.size' => 'Invalid verification token.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any spaces from the code
        if ($this->has('code')) {
            $this->merge([
                'code' => preg_replace('/\s+/', '', $this->code),
            ]);
        }
    }
}
