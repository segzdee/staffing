<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for reviewing agency application documents
 * AGY-REG-003
 */
class ReviewDocumentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => 'required|array|min:1',
            'documents.*.id' => 'required|integer|exists:agency_documents,id',
            'documents.*.status' => 'required|string|in:pending,verified,rejected',
            'documents.*.notes' => 'nullable|string|max:1000',
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
            'documents.required' => 'At least one document must be reviewed.',
            'documents.*.id.required' => 'Document ID is required.',
            'documents.*.id.exists' => 'The selected document does not exist.',
            'documents.*.status.required' => 'Document status is required.',
            'documents.*.status.in' => 'Invalid document status. Must be pending, verified, or rejected.',
            'documents.*.notes.max' => 'Document notes cannot exceed 1000 characters.',
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
            'documents.*.id' => 'document ID',
            'documents.*.status' => 'document status',
            'documents.*.notes' => 'document notes',
        ];
    }
}
