<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload Document Request
 *
 * AGY-REG-002: Validates document uploads for agency registration and application.
 */
class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow during registration (guest) or for authenticated agency users
        $user = $this->user();

        if (!$user) {
            // Guest uploading during registration - allowed
            return true;
        }

        // Authenticated user must be an agency
        return $user->isAgency();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240', // 10MB max
            ],
            'document_type' => [
                'required',
                'string',
                'in:business_license,insurance_certificate,tax_id,w9_form,certificate_of_incorporation,proof_of_address,bank_statement,other',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Please select a document to upload.',
            'document.file' => 'The uploaded item must be a file.',
            'document.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file.',
            'document.max' => 'Document size must not exceed 10MB.',
            'document_type.required' => 'Please specify the document type.',
            'document_type.in' => 'Invalid document type selected.',
            'description.max' => 'Description must not exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'document' => 'document file',
            'document_type' => 'document type',
            'description' => 'document description',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'Document upload validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
