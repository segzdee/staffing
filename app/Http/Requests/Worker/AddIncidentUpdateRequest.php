<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SAF-002: Add Incident Update Form Request
 *
 * Validates incident update/comment data from workers.
 */
class AddIncidentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        // User must be the reporter and incident must be open
        return auth()->check()
            && $incident
            && $incident->reported_by === auth()->id()
            && $incident->isOpen();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,mov,pdf', 'max:10240'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please enter your update or comment.',
            'content.min' => 'The update must be at least 5 characters.',
            'content.max' => 'The update cannot exceed 2000 characters.',
            'attachments.max' => 'You can upload a maximum of 5 attachments.',
            'attachments.*.max' => 'Each attachment must not exceed 10MB.',
            'attachments.*.mimes' => 'Attachments must be images, videos, or PDFs.',
        ];
    }
}
