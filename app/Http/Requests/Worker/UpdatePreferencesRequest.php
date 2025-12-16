<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Preferences Request
 * STAFF-REG-009: Worker Availability Setup
 */
class UpdatePreferencesRequest extends FormRequest
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
            'max_hours_per_week' => 'nullable|integer|min:1|max:168',
            'max_shifts_per_day' => 'nullable|integer|min:1|max:5',
            'min_hours_per_shift' => 'nullable|numeric|min:0.5|max:24',
            'max_travel_distance' => 'nullable|integer|min:1|max:500',
            'distance_unit' => 'nullable|in:km,miles',
            'preferred_shift_types' => 'nullable|array',
            'preferred_shift_types.*' => 'in:morning,afternoon,evening,overnight',
            'min_hourly_rate' => 'nullable|numeric|min:0',
            'preferred_currency' => 'nullable|string|size:3',
            'preferred_industries' => 'nullable|array',
            'preferred_industries.*' => 'string|max:100',
            'preferred_roles' => 'nullable|array',
            'preferred_roles.*' => 'string|max:100',
            'excluded_businesses' => 'nullable|array',
            'excluded_businesses.*' => 'integer|exists:users,id',
            'notify_new_shifts' => 'boolean',
            'notify_matching_shifts' => 'boolean',
            'notify_urgent_shifts' => 'boolean',
            'advance_notice_hours' => 'nullable|integer|min:0|max:168',
            'auto_accept_invitations' => 'boolean',
            'auto_accept_recurring' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'max_hours_per_week.max' => 'Maximum hours per week cannot exceed 168 (total hours in a week).',
            'max_shifts_per_day.max' => 'Maximum shifts per day cannot exceed 5.',
            'min_hours_per_shift.min' => 'Minimum hours per shift must be at least 0.5 hours.',
            'max_travel_distance.max' => 'Maximum travel distance cannot exceed 500.',
            'distance_unit.in' => 'Distance unit must be either km or miles.',
            'preferred_shift_types.*.in' => 'Invalid shift type selected.',
            'excluded_businesses.*.exists' => 'One or more excluded businesses are invalid.',
        ];
    }
}
