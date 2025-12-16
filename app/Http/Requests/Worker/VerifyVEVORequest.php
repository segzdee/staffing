<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-005: Australian VEVO Verification Request
 */
class VerifyVEVORequest extends FormRequest
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
            'passport_number' => [
                'required',
                'string',
                'min:5',
                'max:20',
            ],
            'country_of_passport' => [
                'required',
                'string',
                'size:3', // ISO 3166-1 alpha-3
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
            'passport_number.required' => 'Passport number is required.',
            'passport_number.min' => 'Passport number must be at least 5 characters.',
            'passport_number.max' => 'Passport number cannot exceed 20 characters.',
            'country_of_passport.required' => 'Country of passport is required.',
            'country_of_passport.size' => 'Country code must be 3 characters (ISO format).',
            'date_of_birth.required' => 'Date of birth is required for verification.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->passport_number) {
            // Normalize passport number - uppercase and remove spaces
            $this->merge([
                'passport_number' => strtoupper(str_replace(' ', '', $this->passport_number)),
            ]);
        }

        if ($this->country_of_passport) {
            // Normalize country code to uppercase
            $this->merge([
                'country_of_passport' => strtoupper($this->country_of_passport),
            ]);
        }
    }
}
