<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Add Blackout Date Request
 * STAFF-REG-009: Worker Availability Setup
 */
class AddBlackoutDateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'type' => 'nullable|in:vacation,personal,medical,other',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select a start date.',
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.required' => 'Please select an end date.',
            'end_date.after_or_equal' => 'End date must be the same as or after the start date.',
            'type.in' => 'Invalid blackout type.',
        ];
    }
}
