<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && (Auth::user()->isBusiness() || Auth::user()->isAgency());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'industry' => 'required|in:hospitality,healthcare,retail,events,warehouse,professional',
            'location_address' => 'required|string',
            'location_city' => 'required|string',
            'location_state' => 'required|string',
            'location_country' => 'required|string',
            'shift_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'base_rate' => 'required|numeric|min:0',
            'required_workers' => 'required|integer|min:1|max:100',
            'urgency_level' => 'sometimes|in:normal,urgent,critical',
            'venue_id' => 'nullable|exists:venues,id',
            'requirements' => 'sometimes|array',
            'dress_code' => 'sometimes|string|max:255',
            'parking_info' => 'sometimes|string',
            'break_info' => 'sometimes|string',
            'special_instructions' => 'sometimes|string',
        ];
    }
}
