<?php

namespace App\Http\Requests\Business;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * BIZ-REG-006: Update Venue Request
 */
class UpdateVenueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->user_type === 'business';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $venueId = $this->route('id') ?? $this->route('venue');

        return [
            // Basic Information
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('venues', 'name')
                    ->where('business_profile_id', Auth::user()->businessProfile?->id)
                    ->ignore($venueId),
            ],
            'type' => ['sometimes', 'string', Rule::in(array_keys(Venue::TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],

            // Address
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'timezone' => ['nullable', 'string', 'timezone'],

            // Coordinates
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Geofencing
            'geofence_radius' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'geofence_polygon' => ['nullable', 'array'],
            'gps_accuracy_required' => ['nullable', 'integer', 'min:5', 'max:500'],

            // Contact Information
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_phone' => ['nullable', 'string', 'max:20'],
            'manager_email' => ['nullable', 'email', 'max:255'],

            // Worker Instructions
            'parking_instructions' => ['nullable', 'string', 'max:1000'],
            'entrance_instructions' => ['nullable', 'string', 'max:1000'],
            'checkin_instructions' => ['nullable', 'string', 'max:1000'],
            'dress_code' => ['nullable', 'string', Rule::in(array_keys(Venue::DRESS_CODES))],
            'equipment_provided' => ['nullable', 'string', 'max:1000'],
            'equipment_required' => ['nullable', 'string', 'max:1000'],

            // Budget and Settings
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'auto_approve_favorites' => ['nullable', 'boolean'],
            'require_checkin_photo' => ['nullable', 'boolean'],
            'require_checkout_signature' => ['nullable', 'boolean'],

            // Operating Hours
            'operating_hours' => ['nullable', 'array'],
            'operating_hours.*.day_of_week' => ['required_with:operating_hours', 'integer', 'between:0,6'],
            'operating_hours.*.is_open' => ['required_with:operating_hours', 'boolean'],
            'operating_hours.*.open_time' => ['required_if:operating_hours.*.is_open,true', 'date_format:H:i'],
            'operating_hours.*.close_time' => ['required_if:operating_hours.*.is_open,true', 'date_format:H:i'],
            'operating_hours.*.notes' => ['nullable', 'string', 'max:255'],

            // Managers
            'manager_ids' => ['nullable', 'array'],
            'manager_ids.*' => ['integer', 'exists:team_members,id'],

            // Image
            'image_url' => ['nullable', 'url', 'max:500'],

            // Status
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(array_keys(Venue::STATUSES))],

            // Additional Settings
            'settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A venue with this name already exists for your business.',
            'type.in' => 'Please select a valid venue type.',
        ];
    }
}
