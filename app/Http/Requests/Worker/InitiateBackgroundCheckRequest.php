<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * STAFF-REG-006: Initiate Background Check Request
 */
class InitiateBackgroundCheckRequest extends FormRequest
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
                Rule::in(['US', 'UK', 'AU', 'EU', 'UAE', 'SG']),
            ],
            'check_type' => [
                'required',
                'string',
                'max:50',
            ],
            'billed_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'jurisdiction.required' => 'Please select your jurisdiction.',
            'jurisdiction.in' => 'The selected jurisdiction is not supported.',
            'check_type.required' => 'Please select a background check type.',
            'billed_to.exists' => 'Invalid billing account.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate check_type is valid for the jurisdiction
            $jurisdiction = strtoupper($this->jurisdiction ?? '');
            $checkType = $this->check_type;

            $validTypes = $this->getValidCheckTypes($jurisdiction);

            if (!in_array($checkType, $validTypes)) {
                $validator->errors()->add(
                    'check_type',
                    "The selected check type is not available for {$jurisdiction}."
                );
            }
        });
    }

    /**
     * Get valid check types for a jurisdiction.
     */
    protected function getValidCheckTypes(string $jurisdiction): array
    {
        $types = [
            'US' => ['basic', 'standard', 'professional', 'comprehensive'],
            'UK' => ['dbs_basic', 'dbs_standard', 'dbs_enhanced', 'dbs_enhanced_barred'],
            'AU' => ['police_check', 'working_with_children'],
            'EU' => ['standard'],
            'UAE' => ['standard'],
            'SG' => ['standard'],
        ];

        return $types[$jurisdiction] ?? ['standard'];
    }
}
