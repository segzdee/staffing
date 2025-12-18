<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SAF-002: Resolve Incident Form Request
 *
 * Validates incident resolution data from admins.
 */
class ResolveIncidentRequest extends FormRequest
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
            'resolution_notes' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Please provide resolution notes explaining how this incident was resolved.',
            'resolution_notes.min' => 'Resolution notes must be at least 20 characters.',
            'resolution_notes.max' => 'Resolution notes cannot exceed 5000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'resolution_notes' => 'resolution notes',
        ];
    }
}
