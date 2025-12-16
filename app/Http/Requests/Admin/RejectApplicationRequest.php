<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for rejecting an agency application
 * AGY-REG-003
 */
class RejectApplicationRequest extends FormRequest
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
            'rejection_reason' => 'required|string|min:20|max:2000',
            'rejection_details' => 'nullable|array',
            'rejection_details.*.category' => 'nullable|string|max:100',
            'rejection_details.*.description' => 'nullable|string|max:500',
            'allow_resubmission' => 'boolean',
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
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.min' => 'The rejection reason must be at least 20 characters to provide adequate explanation.',
            'rejection_reason.max' => 'The rejection reason cannot exceed 2000 characters.',
            'rejection_details.array' => 'Rejection details must be provided as a list.',
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
            'rejection_reason' => 'rejection reason',
            'rejection_details' => 'rejection details',
            'allow_resubmission' => 'allow resubmission flag',
        ];
    }
}
