<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0|max:1000',
            'photo' => 'nullable|string', // base64 encoded
            'device_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'early_departure_reason' => 'nullable|string|max:500',
            'timezone' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'latitude.between' => 'Invalid latitude value.',
            'longitude.between' => 'Invalid longitude value.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
            'early_departure_reason.max' => 'Early departure reason cannot exceed 500 characters.',
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
