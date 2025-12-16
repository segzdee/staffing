<?php

namespace App\Http\Requests\Business;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * BIZ-REG-006: Store Venue Request
 */
class StoreVenueRequest extends FormRequest
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
        return [
            // Basic Information
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('venues', 'name')
                    ->where('business_profile_id', Auth::user()->businessProfile?->id),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(Venue::TYPES))],
            'description' => ['nullable', 'string', 'max:2000'],

            // Address
            'address' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'timezone' => ['nullable', 'string', 'timezone'],

            // Coordinates
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Geofencing
            'geofence_radius' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'geofence_polygon' => ['nullable', 'array'],
            'geofence_polygon.*.lat' => ['required_with:geofence_polygon', 'numeric', 'between:-90,90'],
            'geofence_polygon.*.lng' => ['required_with:geofence_polygon', 'numeric', 'between:-180,180'],
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
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'geofence_radius.min' => 'Geofence radius must be at least 10 meters.',
            'geofence_radius.max' => 'Geofence radius cannot exceed 10,000 meters.',
            'operating_hours.*.open_time.required_if' => 'Open time is required when the venue is open on this day.',
            'operating_hours.*.close_time.required_if' => 'Close time is required when the venue is open on this day.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'venue name',
            'address' => 'street address',
            'address_line_2' => 'address line 2',
            'postal_code' => 'postal/ZIP code',
            'geofence_radius' => 'geofence radius',
            'gps_accuracy_required' => 'GPS accuracy requirement',
            'parking_instructions' => 'parking instructions',
            'entrance_instructions' => 'entrance instructions',
            'checkin_instructions' => 'check-in instructions',
            'dress_code' => 'dress code',
            'equipment_provided' => 'equipment provided',
            'equipment_required' => 'equipment required',
            'monthly_budget' => 'monthly budget',
            'default_hourly_rate' => 'default hourly rate',
            'auto_approve_favorites' => 'auto-approve favorites setting',
            'require_checkin_photo' => 'require check-in photo setting',
            'require_checkout_signature' => 'require checkout signature setting',
            'operating_hours.*.open_time' => 'opening time',
            'operating_hours.*.close_time' => 'closing time',
        ];
    }
}
