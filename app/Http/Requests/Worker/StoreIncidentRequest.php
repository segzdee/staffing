<?php

namespace App\Http\Requests\Worker;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SAF-002: Store Incident Form Request
 *
 * Validates incident report data from workers.
 */
class StoreIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'involves_user_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', Rule::in(Incident::TYPES)],
            'severity' => ['required', Rule::in(Incident::SEVERITIES)],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'incident_time' => ['required', 'date', 'before_or_equal:now'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,mov,pdf', 'max:20480'],
            'witnesses' => ['nullable', 'array', 'max:5'],
            'witnesses.*.name' => ['required_with:witnesses', 'string', 'max:255'],
            'witnesses.*.phone' => ['nullable', 'string', 'max:50'],
            'witnesses.*.email' => ['nullable', 'email', 'max:255'],
            'witnesses.*.statement' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select the type of incident.',
            'type.in' => 'Invalid incident type selected.',
            'severity.required' => 'Please indicate the severity of the incident.',
            'severity.in' => 'Invalid severity level selected.',
            'description.required' => 'Please provide a detailed description of the incident.',
            'description.min' => 'The description must be at least 20 characters.',
            'description.max' => 'The description cannot exceed 5000 characters.',
            'incident_time.required' => 'Please specify when the incident occurred.',
            'incident_time.before_or_equal' => 'The incident time cannot be in the future.',
            'evidence.max' => 'You can upload a maximum of 10 evidence files.',
            'evidence.*.max' => 'Each evidence file must not exceed 20MB.',
            'evidence.*.mimes' => 'Evidence files must be images (jpg, png, gif), videos (mp4, mov), or PDFs.',
            'witnesses.max' => 'You can add a maximum of 5 witnesses.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'shift_id' => 'related shift',
            'venue_id' => 'venue',
            'involves_user_id' => 'involved person',
            'incident_time' => 'incident time',
            'location_description' => 'location description',
        ];
    }
}
