<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-005: UK Share Code Verification Request
 */
class VerifyShareCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isWorker() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'verification_id' => ['required', 'integer', 'exists:right_to_work_verifications,id'],
            'share_code' => [
                'required',
                'string',
                'size:9', // UK share codes are 9 characters
                'regex:/^[A-Z0-9]{9}$/',
            ],
            'date_of_birth' => ['required', 'date', 'before:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'verification_id.required' => 'Verification ID is required.',
            'verification_id.exists' => 'Verification not found.',
            'share_code.required' => 'Share code is required.',
            'share_code.size' => 'Share code must be exactly 9 characters.',
            'share_code.regex' => 'Share code format is invalid. It should contain only uppercase letters and numbers.',
            'date_of_birth.required' => 'Date of birth is required for verification.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->share_code) {
            // Normalize share code to uppercase and remove spaces
            $this->merge([
                'share_code' => strtoupper(str_replace(' ', '', $this->share_code)),
            ]);
        }
    }
}
