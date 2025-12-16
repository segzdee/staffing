<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * WizardStepRequest
 *
 * BIZ-REG-009: First Shift Wizard
 *
 * Validates step data for the first shift wizard.
 * Rules vary by step number.
 */
class WizardStepRequest extends FormRequest
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
        $step = (int) $this->route('step');

        return match($step) {
            1 => $this->step1Rules(),
            2 => $this->step2Rules(),
            3 => $this->step3Rules(),
            4 => $this->step4Rules(),
            5 => $this->step5Rules(),
            6 => $this->step6Rules(),
            default => [],
        };
    }

    /**
     * Step 1: Select Venue
     */
    protected function step1Rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
        ];
    }

    /**
     * Step 2: Choose Role
     */
    protected function step2Rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:255'],
            'role_category' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Step 3: Set Schedule
     */
    protected function step3Rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'workers_needed' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Step 4: Set Pay Rate
     */
    protected function step4Rules(): array
    {
        return [
            'hourly_rate' => ['required', 'integer', 'min:100'], // At least $1.00 in cents
        ];
    }

    /**
     * Step 5: Add Details (all optional)
     */
    protected function step5Rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'required_skills' => ['sometimes', 'nullable', 'array'],
            'required_skills.*' => ['string', 'max:100'],
            'dress_code' => ['sometimes', 'nullable', 'string', 'max:500'],
            'special_instructions' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'parking_info' => ['sometimes', 'nullable', 'string', 'max:500'],
            'break_info' => ['sometimes', 'nullable', 'string', 'max:500'],
            'save_as_template' => ['sometimes', 'boolean'],
            'template_name' => ['required_if:save_as_template,true', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Step 6: Review (confirmation)
     */
    protected function step6Rules(): array
    {
        return [
            'confirmed' => ['sometimes', 'boolean'],
            'save_as_template' => ['sometimes', 'boolean'],
            'template_name' => ['required_if:save_as_template,true', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Step 1
            'venue_id.required' => 'Please select a venue for this shift.',
            'venue_id.exists' => 'Selected venue not found.',

            // Step 2
            'role.required' => 'Please select or enter a role/position.',
            'role.max' => 'Role name is too long.',

            // Step 3
            'date.required' => 'Please select a date for this shift.',
            'date.after_or_equal' => 'Shift date must be today or in the future.',
            'start_time.required' => 'Please set a start time.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'Please set an end time.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'workers_needed.min' => 'At least one worker is required.',
            'workers_needed.max' => 'Maximum of 50 workers per shift.',

            // Step 4
            'hourly_rate.required' => 'Please set an hourly rate.',
            'hourly_rate.min' => 'Hourly rate must be at least $1.00.',

            // Step 5
            'description.max' => 'Description is too long (max 2000 characters).',
            'template_name.required_if' => 'Please provide a name for your template.',
        ];
    }
}
