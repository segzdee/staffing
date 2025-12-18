<?php

namespace App\Http\Requests\Admin;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ResolveDisputeRequest
 *
 * FIN-010: Validation for resolving a dispute.
 */
class ResolveDisputeRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolution' => [
                'required',
                'string',
                Rule::in([
                    Dispute::RESOLUTION_WORKER_FAVOR,
                    Dispute::RESOLUTION_BUSINESS_FAVOR,
                    Dispute::RESOLUTION_SPLIT,
                    Dispute::RESOLUTION_WITHDRAWN,
                    Dispute::RESOLUTION_EXPIRED,
                ]),
            ],
            'resolution_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
            'resolution_notes' => [
                'required',
                'string',
                'min:10',
                'max:5000',
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
            'resolution.required' => 'Please select a resolution outcome.',
            'resolution.in' => 'Invalid resolution outcome selected.',
            'resolution_amount.required' => 'Please enter the resolution amount.',
            'resolution_amount.numeric' => 'Resolution amount must be a number.',
            'resolution_amount.min' => 'Resolution amount cannot be negative.',
            'resolution_amount.max' => 'Resolution amount is too large.',
            'resolution_notes.required' => 'Please provide resolution notes.',
            'resolution_notes.min' => 'Resolution notes must be at least 10 characters.',
            'resolution_notes.max' => 'Resolution notes must not exceed 5000 characters.',
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
            'resolution' => 'resolution outcome',
            'resolution_amount' => 'resolution amount',
            'resolution_notes' => 'resolution notes',
        ];
    }
}
