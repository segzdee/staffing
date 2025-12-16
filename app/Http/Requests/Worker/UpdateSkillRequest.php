<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-007: Update Skill Request Validation
 */
class UpdateSkillRequest extends FormRequest
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
            'experience_level' => 'nullable|string|in:entry,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'experience_notes' => 'nullable|string|max:500',
            'last_used_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'years_experience.min' => 'Years of experience cannot be negative.',
            'years_experience.max' => 'Years of experience seems too high. Please verify.',
            'experience_notes.max' => 'Experience notes cannot exceed 500 characters.',
            'last_used_date.before_or_equal' => 'Last used date cannot be in the future.',
        ];
    }
}
