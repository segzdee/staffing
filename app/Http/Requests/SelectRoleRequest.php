<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SelectRoleRequest
 *
 * Validates the role selection form during onboarding.
 * Ensures strict validation for user type selection.
 */
class SelectRoleRequest extends FormRequest
{
    /**
     * Allowed user types for role selection.
     */
    public const ALLOWED_ROLES = ['worker', 'business', 'agency'];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // User must be authenticated
        if (!$this->user()) {
            return false;
        }

        // User must not have already completed onboarding
        // (unless they're explicitly changing their role)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_type' => [
                'required',
                'string',
                Rule::in(self::ALLOWED_ROLES),
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
            'user_type.required' => 'Please select an account type to continue.',
            'user_type.string' => 'Invalid account type format.',
            'user_type.in' => 'Please select a valid account type: Worker, Business, or Agency.',
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
            'user_type' => 'account type',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Normalize the user_type to lowercase
        if ($this->has('user_type')) {
            $this->merge([
                'user_type' => strtolower(trim($this->user_type)),
            ]);
        }
    }
}
