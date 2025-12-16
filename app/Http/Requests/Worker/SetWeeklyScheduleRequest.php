<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Set Weekly Schedule Request
 * STAFF-REG-009: Worker Availability Setup
 */
class SetWeeklyScheduleRequest extends FormRequest
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
            'schedule' => 'required|array',
            'schedule.monday' => 'nullable|array',
            'schedule.tuesday' => 'nullable|array',
            'schedule.wednesday' => 'nullable|array',
            'schedule.thursday' => 'nullable|array',
            'schedule.friday' => 'nullable|array',
            'schedule.saturday' => 'nullable|array',
            'schedule.sunday' => 'nullable|array',
            'schedule.*.*' => 'array',
            'schedule.*.*.start_time' => 'required|date_format:H:i',
            'schedule.*.*.end_time' => 'required|date_format:H:i|after:schedule.*.*.start_time',
            'schedule.*.*.is_available' => 'boolean',
            'schedule.*.*.preferred_shift_types' => 'nullable|array',
            'schedule.*.*.preferred_shift_types.*' => 'in:morning,afternoon,evening,overnight',
            'schedule.*.*.recurrence' => 'in:weekly,biweekly,monthly',
            'schedule.*.*.effective_from' => 'nullable|date|after_or_equal:today',
            'schedule.*.*.effective_until' => 'nullable|date|after:schedule.*.*.effective_from',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'schedule.required' => 'Please provide a schedule.',
            'schedule.*.*.start_time.required' => 'Start time is required for each time slot.',
            'schedule.*.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'schedule.*.*.end_time.required' => 'End time is required for each time slot.',
            'schedule.*.*.end_time.after' => 'End time must be after start time.',
            'schedule.*.*.preferred_shift_types.*.in' => 'Invalid shift type. Must be morning, afternoon, evening, or overnight.',
        ];
    }
}
