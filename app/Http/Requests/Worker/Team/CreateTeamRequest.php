<?php

namespace App\Http\Requests\Worker\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * WKR-014: Team Formation - Create Team Request Validation
 */
class CreateTeamRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:100',
            'description' => 'nullable|string|max:1000',
            'max_members' => 'nullable|integer|min:2|max:50',
            'is_public' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'member_ids' => 'nullable|array|max:49',
            'member_ids.*' => 'integer|exists:users,id|different:'.$this->user()->id,
            'specializations' => 'nullable|array',
            'specializations.*' => 'string|max:100',
            'preferred_industries' => 'nullable|array',
            'preferred_industries.*' => 'string|max:100',
            'min_reliability_score' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a team name.',
            'name.min' => 'Team name must be at least 3 characters.',
            'name.max' => 'Team name cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'max_members.min' => 'A team must allow at least 2 members.',
            'max_members.max' => 'A team cannot have more than 50 members.',
            'member_ids.max' => 'You can invite up to 49 members when creating a team.',
            'member_ids.*.exists' => 'One or more selected workers do not exist.',
            'member_ids.*.different' => 'You cannot invite yourself to the team.',
            'min_reliability_score.min' => 'Minimum reliability score cannot be negative.',
            'min_reliability_score.max' => 'Minimum reliability score cannot exceed 100.',
        ];
    }
}
