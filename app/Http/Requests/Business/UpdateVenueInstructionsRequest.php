<?php

namespace App\Http\Requests\Business;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * BIZ-REG-006: Update Venue Instructions Request
 */
class UpdateVenueInstructionsRequest extends FormRequest
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
            'parking_instructions' => ['nullable', 'string', 'max:1000'],
            'entrance_instructions' => ['nullable', 'string', 'max:1000'],
            'checkin_instructions' => ['nullable', 'string', 'max:1000'],
            'dress_code' => ['nullable', 'string', Rule::in(array_keys(Venue::DRESS_CODES))],
            'equipment_provided' => ['nullable', 'string', 'max:1000'],
            'equipment_required' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'parking_instructions' => 'parking instructions',
            'entrance_instructions' => 'entrance instructions',
            'checkin_instructions' => 'check-in instructions',
            'dress_code' => 'dress code',
            'equipment_provided' => 'equipment provided',
            'equipment_required' => 'equipment required',
        ];
    }
}
