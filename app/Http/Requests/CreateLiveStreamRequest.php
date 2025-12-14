<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLiveStreamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Only verified creators can create live streams
        return auth()->check() && auth()->user()->verified_id == 'yes';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['sometimes', Rule::in(['active', 'scheduled', 'ended'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'Live stream title is required.',
            'title.min' => 'Title must be at least 3 characters.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
        ];
    }
}
