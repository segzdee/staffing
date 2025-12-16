<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Add Date Override Request
 * STAFF-REG-009: Worker Availability Setup
 */
class AddDateOverrideRequest extends FormRequest
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
            'date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:available,unavailable,custom',
            'start_time' => 'nullable|required_if:type,custom|date_format:H:i',
            'end_time' => 'nullable|required_if:type,custom|date_format:H:i|after:start_time',
            'is_one_time' => 'boolean',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'nullable|integer|min:1|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Please select a date.',
            'date.after_or_equal' => 'Date must be today or in the future.',
            'type.required' => 'Please select an override type.',
            'type.in' => 'Invalid override type.',
            'start_time.required_if' => 'Start time is required for custom availability.',
            'end_time.required_if' => 'End time is required for custom availability.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }
}
