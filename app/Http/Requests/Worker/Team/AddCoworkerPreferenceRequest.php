<?php

namespace App\Http\Requests\Worker\Team;

use App\Models\WorkerRelationship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * WKR-014: Team Formation - Add Coworker Preference Request Validation
 */
class AddCoworkerPreferenceRequest extends FormRequest
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
            'coworker_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:'.$this->user()->id,
            ],
            'preference_type' => [
                'required',
                'string',
                Rule::in([
                    WorkerRelationship::TYPE_PREFERRED,
                    WorkerRelationship::TYPE_AVOIDED,
                ]),
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'coworker_id.required' => 'Please select a worker.',
            'coworker_id.exists' => 'The selected worker does not exist.',
            'coworker_id.different' => 'You cannot add yourself.',
            'preference_type.required' => 'Please specify the preference type.',
            'preference_type.in' => 'Invalid preference type.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
