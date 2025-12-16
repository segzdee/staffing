<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-006: Respond to Adjudication Case Request
 */
class RespondToAdjudicationRequest extends FormRequest
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
            'response' => [
                'required',
                'string',
                'min:20',
                'max:5000',
            ],
            'documents' => [
                'nullable',
                'array',
                'max:5',
            ],
            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'response.required' => 'Please provide your response.',
            'response.min' => 'Your response must be at least 20 characters.',
            'response.max' => 'Your response cannot exceed 5000 characters.',
            'documents.max' => 'You can upload a maximum of 5 documents.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, PNG, DOC, or DOCX files.',
            'documents.*.max' => 'Each document must be less than 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'response' => 'your response',
            'documents.*' => 'supporting document',
        ];
    }
}
