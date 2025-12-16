<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * BIZ-REG-006: Update Venue Settings Request
 */
class UpdateVenueSettingsRequest extends FormRequest
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
            'default_hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'auto_approve_favorites' => ['nullable', 'boolean'],
            'require_checkin_photo' => ['nullable', 'boolean'],
            'require_checkout_signature' => ['nullable', 'boolean'],
            'gps_accuracy_required' => ['nullable', 'integer', 'min:5', 'max:500'],
            'geofence_radius' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'settings' => ['nullable', 'array'],
            'settings.notify_budget_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'settings.allow_overtime' => ['nullable', 'boolean'],
            'settings.max_workers_per_shift' => ['nullable', 'integer', 'min:1', 'max:100'],
            'settings.min_notice_hours' => ['nullable', 'integer', 'min:0', 'max:168'],
            'settings.cancellation_policy' => ['nullable', 'string', 'in:flexible,standard,strict'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'default_hourly_rate' => 'default hourly rate',
            'auto_approve_favorites' => 'auto-approve favorites',
            'require_checkin_photo' => 'require check-in photo',
            'require_checkout_signature' => 'require checkout signature',
            'gps_accuracy_required' => 'GPS accuracy requirement',
            'geofence_radius' => 'geofence radius',
            'monthly_budget' => 'monthly budget',
            'settings.notify_budget_threshold' => 'budget notification threshold',
            'settings.allow_overtime' => 'allow overtime',
            'settings.max_workers_per_shift' => 'maximum workers per shift',
            'settings.min_notice_hours' => 'minimum notice hours',
            'settings.cancellation_policy' => 'cancellation policy',
        ];
    }
}
