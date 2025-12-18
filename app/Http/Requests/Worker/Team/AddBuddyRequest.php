<?php

namespace App\Http\Requests\Worker\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * WKR-014: Team Formation - Add Buddy Request Validation
 */
class AddBuddyRequest extends FormRequest
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
            'buddy_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:'.$this->user()->id,
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
            'buddy_id.required' => 'Please select a worker to add as a buddy.',
            'buddy_id.exists' => 'The selected worker does not exist.',
            'buddy_id.different' => 'You cannot add yourself as a buddy.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
