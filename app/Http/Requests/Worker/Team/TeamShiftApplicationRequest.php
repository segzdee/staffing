<?php

namespace App\Http\Requests\Worker\Team;

use App\Models\WorkerTeam;
use Illuminate\Foundation\Http\FormRequest;

/**
 * WKR-014: Team Formation - Team Shift Application Request Validation
 */
class TeamShiftApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->user() || ! $this->user()->isWorker()) {
            return false;
        }

        // Check if user is a leader of the team
        $team = WorkerTeam::find($this->input('team_id'));
        if (! $team) {
            return false;
        }

        return $team->hasLeader($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'team_id' => 'required|integer|exists:worker_teams,id',
            'shift_id' => 'required|integer|exists:shifts,id',
            'members_needed' => 'nullable|integer|min:1',
            'application_message' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'team_id.required' => 'Please select a team.',
            'team_id.exists' => 'The selected team does not exist.',
            'shift_id.required' => 'Please select a shift.',
            'shift_id.exists' => 'The selected shift does not exist.',
            'members_needed.min' => 'At least one member is needed.',
            'application_message.max' => 'Application message cannot exceed 1000 characters.',
        ];
    }
}
