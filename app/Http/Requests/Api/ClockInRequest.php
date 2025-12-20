<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0|max:1000',
            'photo' => 'nullable|string', // base64 encoded face photo
            'device_id' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:20',
            'qr_code' => 'nullable|string|max:100',
            'supervisor_code' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Location data is required for clock-in.',
            'latitude.between' => 'Invalid latitude value.',
            'longitude.required' => 'Location data is required for clock-in.',
            'longitude.between' => 'Invalid longitude value.',
            'accuracy.max' => 'GPS accuracy is too low. Please move to an area with better signal.',
        ];
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert location object if sent as nested object
        if ($this->has('location') && is_array($this->location)) {
            $this->merge([
                'latitude' => $this->location['latitude'] ?? null,
                'longitude' => $this->location['longitude'] ?? null,
                'accuracy' => $this->location['accuracy'] ?? null,
            ]);
        }
    }
}
