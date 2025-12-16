<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AGY-REG-004: Individual Worker Invitation Request Validation
 *
 * Validates individual worker invitation requests.
 */
class InviteWorkerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAgency();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/',
            ],
            'commission_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'skills' => [
                'nullable',
                'array',
            ],
            'skills.*' => [
                'integer',
                'exists:skills,id',
            ],
            'certifications' => [
                'nullable',
                'array',
            ],
            'certifications.*' => [
                'integer',
                'exists:certifications,id',
            ],
            'personal_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'send_email' => [
                'nullable',
                'boolean',
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
            'email.required' => 'Please enter the worker\'s email address.',
            'email.email' => 'Please enter a valid email address.',
            'phone.regex' => 'Please enter a valid phone number.',
            'commission_rate.numeric' => 'Commission rate must be a number.',
            'commission_rate.min' => 'Commission rate cannot be negative.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
            'skills.*.exists' => 'One or more selected skills are invalid.',
            'certifications.*.exists' => 'One or more selected certifications are invalid.',
            'personal_message.max' => 'Personal message cannot exceed 1000 characters.',
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
            'email' => 'email address',
            'name' => 'worker name',
            'phone' => 'phone number',
            'commission_rate' => 'commission rate',
            'skills' => 'skills',
            'certifications' => 'certifications',
            'personal_message' => 'personal message',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        if ($this->has('phone')) {
            // Normalize phone number
            $phone = preg_replace('/[^\+0-9]/', '', $this->phone);
            $this->merge(['phone' => $phone]);
        }
    }
}
