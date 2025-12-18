<?php

namespace App\Http\Requests\Privacy;

use App\Models\ConsentRecord;
use Illuminate\Foundation\Http\FormRequest;

/**
 * GLO-005: GDPR/CCPA Compliance - Consent Update Form Request
 */
class ConsentUpdateRequest extends FormRequest
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
        $consentTypes = array_keys(ConsentRecord::getTypes());

        $rules = [
            'consents' => ['required', 'array'],
        ];

        foreach ($consentTypes as $type) {
            $rules["consents.{$type}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'consents.required' => 'Please select at least one consent option.',
            'consents.array' => 'Invalid consent data format.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to booleans
        if ($this->has('consents')) {
            $consents = [];
            foreach ($this->input('consents', []) as $type => $value) {
                $consents[$type] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['consents' => $consents]);
        }
    }
}
