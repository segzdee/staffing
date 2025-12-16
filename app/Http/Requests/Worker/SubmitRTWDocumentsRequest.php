<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-005: Submit RTW Documents Request
 */
class SubmitRTWDocumentsRequest extends FormRequest
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

            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // 10MB
            ],

            'document_types' => ['required', 'array', 'min:1'],
            'document_types.*' => ['required', 'string', 'max:50'],

            // Optional metadata arrays (must match documents count)
            'document_numbers' => ['sometimes', 'array'],
            'document_numbers.*' => ['nullable', 'string', 'max:100'],

            'issuing_countries' => ['sometimes', 'array'],
            'issuing_countries.*' => ['nullable', 'string', 'size:3'], // ISO 3166-1 alpha-3

            'issuing_authorities' => ['sometimes', 'array'],
            'issuing_authorities.*' => ['nullable', 'string', 'max:200'],

            'issue_dates' => ['sometimes', 'array'],
            'issue_dates.*' => ['nullable', 'date', 'before_or_equal:today'],

            'expiry_dates' => ['sometimes', 'array'],
            'expiry_dates.*' => ['nullable', 'date', 'after:today'],
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
            'documents.required' => 'At least one document is required.',
            'documents.min' => 'At least one document is required.',
            'documents.max' => 'Maximum 5 documents can be uploaded at once.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, or PNG files.',
            'documents.*.max' => 'Each document must be less than 10MB.',
            'document_types.required' => 'Document types are required.',
            'document_types.*.required' => 'Each document must have a type specified.',
            'issue_dates.*.before_or_equal' => 'Issue date cannot be in the future.',
            'expiry_dates.*.after' => 'Expiry date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure arrays have matching counts
        $documentCount = count($this->file('documents', []));

        // Pad arrays to match document count if needed
        $this->merge([
            'document_numbers' => array_pad($this->document_numbers ?? [], $documentCount, null),
            'issuing_countries' => array_pad($this->issuing_countries ?? [], $documentCount, null),
            'issuing_authorities' => array_pad($this->issuing_authorities ?? [], $documentCount, null),
            'issue_dates' => array_pad($this->issue_dates ?? [], $documentCount, null),
            'expiry_dates' => array_pad($this->expiry_dates ?? [], $documentCount, null),
        ]);
    }
}
