<?php

namespace App\Http\Requests\Admin;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SAF-002: Update Incident Status Form Request
 *
 * Validates incident status update data from admins.
 */
class UpdateIncidentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Incident::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Require notes when resolving
            if ($this->status === Incident::STATUS_RESOLVED && empty($this->notes)) {
                $validator->errors()->add('notes', 'Resolution notes are required when resolving an incident.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }
}
