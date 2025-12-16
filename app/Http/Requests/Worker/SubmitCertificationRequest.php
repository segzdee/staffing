<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-007: Submit Certification Request Validation
 */
class SubmitCertificationRequest extends FormRequest
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
            'certification_type_id' => 'required|integer|exists:certification_types,id',
            'certification_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'issuing_state' => 'nullable|string|max:100',
            'issuing_country' => 'nullable|string|max:100',
            'document' => 'nullable|file|mimes:jpeg,jpg,png,gif,pdf|max:10240',
            'is_renewal' => 'nullable|boolean',
            'renewal_of_certification_id' => 'nullable|integer|exists:worker_certifications,id',
            'is_primary' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'certification_type_id.required' => 'Please select a certification type.',
            'certification_type_id.exists' => 'The selected certification type is invalid.',
            'certification_number.max' => 'Certification number cannot exceed 100 characters.',
            'issue_date.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after the issue date.',
            'document.mimes' => 'Document must be a JPEG, PNG, GIF, or PDF file.',
            'document.max' => 'Document size cannot exceed 10MB.',
            'issuing_authority.max' => 'Issuing authority name cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'certification_type_id' => 'certification type',
            'certification_number' => 'certificate number',
            'issue_date' => 'issue date',
            'expiry_date' => 'expiry date',
            'issuing_authority' => 'issuing authority',
            'issuing_state' => 'issuing state',
            'issuing_country' => 'issuing country',
        ];
    }
}
