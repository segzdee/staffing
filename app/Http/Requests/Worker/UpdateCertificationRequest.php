<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-007: Update Certification Request Validation
 */
class UpdateCertificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'certification_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_state' => 'nullable|string|max:100',
            'issuing_country' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'certification_number.max' => 'Certification number cannot exceed 100 characters.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after the issue date.',
            'issuing_authority.max' => 'Issuing authority name cannot exceed 255 characters.',
        ];
    }
}
