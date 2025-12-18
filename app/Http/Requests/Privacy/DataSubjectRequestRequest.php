<?php

namespace App\Http\Requests\Privacy;

use App\Models\DataSubjectRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GLO-005: GDPR/CCPA Compliance - Data Subject Request Form Request
 */
class DataSubjectRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public access for DSR submissions
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'type' => [
                'required',
                Rule::in([
                    DataSubjectRequest::TYPE_ACCESS,
                    DataSubjectRequest::TYPE_RECTIFICATION,
                    DataSubjectRequest::TYPE_ERASURE,
                    DataSubjectRequest::TYPE_PORTABILITY,
                    DataSubjectRequest::TYPE_RESTRICTION,
                    DataSubjectRequest::TYPE_OBJECTION,
                ]),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'g-recaptcha-response' => ['nullable', 'string'], // Optional reCAPTCHA
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'type.required' => 'Please select a request type.',
            'type.in' => 'Invalid request type selected.',
            'description.max' => 'Description cannot exceed 2000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'type' => 'request type',
            'description' => 'additional information',
        ];
    }
}
