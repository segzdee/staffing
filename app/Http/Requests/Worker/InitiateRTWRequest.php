<?php

namespace App\Http\Requests\Worker;

use App\Models\RightToWorkVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * STAFF-REG-005: Initiate RTW Verification Request
 */
class InitiateRTWRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isWorker() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'jurisdiction' => [
                'required',
                'string',
                Rule::in([
                    RightToWorkVerification::JURISDICTION_US,
                    RightToWorkVerification::JURISDICTION_UK,
                    RightToWorkVerification::JURISDICTION_EU,
                    RightToWorkVerification::JURISDICTION_AU,
                    RightToWorkVerification::JURISDICTION_UAE,
                    RightToWorkVerification::JURISDICTION_SG,
                ]),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'jurisdiction.required' => 'Please select your work jurisdiction.',
            'jurisdiction.in' => 'The selected jurisdiction is not supported.',
        ];
    }
}
