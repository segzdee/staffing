<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sign Agreement Request
 *
 * AGY-REG-002: Validates e-signature submission for agency partnership agreement.
 */
class SignAgreementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // Must be authenticated and be an agency user
        if (!$user || !$user->isAgency()) {
            return false;
        }

        // Must have an agency profile
        if (!$user->agencyProfile) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'title' => [
                'required',
                'string',
                'max:100',
            ],
            'signature' => [
                'required',
                'string',
                // Base64 encoded image - basic validation
                'regex:/^data:image\/(png|jpeg|jpg);base64,/',
            ],
            'agree_terms' => [
                'required',
                'accepted',
            ],
            'agree_privacy' => [
                'required',
                'accepted',
            ],
            'agree_commercial' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Please enter your full legal name.',
            'full_name.min' => 'Full name must be at least 2 characters.',
            'title.required' => 'Please enter your job title.',
            'signature.required' => 'Please provide your signature.',
            'signature.regex' => 'Invalid signature format. Please draw your signature again.',
            'agree_terms.required' => 'You must agree to the Terms of Service.',
            'agree_terms.accepted' => 'You must agree to the Terms of Service.',
            'agree_privacy.required' => 'You must agree to the Privacy Policy.',
            'agree_privacy.accepted' => 'You must agree to the Privacy Policy.',
            'agree_commercial.required' => 'You must agree to the Commercial Terms.',
            'agree_commercial.accepted' => 'You must agree to the Commercial Terms.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'title' => 'job title',
            'signature' => 'signature',
            'agree_terms' => 'terms of service agreement',
            'agree_privacy' => 'privacy policy agreement',
            'agree_commercial' => 'commercial terms agreement',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('full_name')) {
            $this->merge([
                'full_name' => trim($this->full_name ?? ''),
            ]);
        }

        if ($this->has('title')) {
            $this->merge([
                'title' => trim($this->title ?? ''),
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'Please complete all required fields.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
