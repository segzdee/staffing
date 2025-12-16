<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateShiftFromWizardRequest
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Validates the final shift creation from the wizard.
 */
class CreateShiftFromWizardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isBusiness();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Optional overrides (wizard progress data is used by default)
            'venue_id' => ['sometimes', 'integer', 'exists:venues,id'],
            'role' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'hourly_rate' => ['sometimes', 'integer', 'min:100'],
            'workers_needed' => ['sometimes', 'integer', 'min:1', 'max:50'],

            // Optional details
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'required_skills' => ['sometimes', 'nullable', 'array'],
            'dress_code' => ['sometimes', 'nullable', 'string', 'max:500'],
            'special_instructions' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'parking_info' => ['sometimes', 'nullable', 'string', 'max:500'],
            'break_info' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Template option
            'save_as_template' => ['sometimes', 'boolean'],
            'template_name' => ['required_if:save_as_template,true', 'nullable', 'string', 'max:255'],

            // Confirmation
            'confirm' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'venue_id.exists' => 'Selected venue not found.',
            'date.after_or_equal' => 'Shift date must be today or in the future.',
            'hourly_rate.min' => 'Hourly rate must be at least $1.00.',
            'workers_needed.min' => 'At least one worker is required.',
            'workers_needed.max' => 'Maximum of 50 workers per shift.',
            'template_name.required_if' => 'Please provide a name for your template.',
            'confirm.accepted' => 'Please confirm the shift details.',
        ];
    }
}
