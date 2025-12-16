<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AGY-REG-004: External Worker Sync Request Validation
 *
 * Validates API requests for syncing workers from external systems.
 */
class SyncWorkersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAgency();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workers' => [
                'required',
                'array',
                'min:1',
                'max:500', // Limit batch size
            ],
            'workers.*.email' => [
                'required',
                'email:rfc',
                'max:255',
            ],
            'workers.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'workers.*.phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'workers.*.commission_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'workers.*.skills' => [
                'nullable',
                'string', // Comma-separated or array
            ],
            'workers.*.certifications' => [
                'nullable',
                'string', // Comma-separated or array
            ],
            'workers.*.notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'workers.*.personal_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'workers.*.external_id' => [
                'nullable',
                'string',
                'max:100',
            ],
            'default_commission_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'send_invitations' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workers.required' => 'Please provide at least one worker to sync.',
            'workers.array' => 'Workers must be provided as an array.',
            'workers.min' => 'Please provide at least one worker.',
            'workers.max' => 'Cannot sync more than 500 workers at once.',
            'workers.*.email.required' => 'Each worker must have an email address.',
            'workers.*.email.email' => 'Worker email addresses must be valid.',
            'workers.*.commission_rate.numeric' => 'Commission rates must be numbers.',
            'workers.*.commission_rate.min' => 'Commission rates cannot be negative.',
            'workers.*.commission_rate.max' => 'Commission rates cannot exceed 100%.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'workers' => 'workers list',
            'workers.*.email' => 'worker email',
            'workers.*.name' => 'worker name',
            'workers.*.phone' => 'worker phone',
            'workers.*.commission_rate' => 'worker commission rate',
            'default_commission_rate' => 'default commission rate',
            'send_invitations' => 'send invitations',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('workers') && is_array($this->workers)) {
            $workers = array_map(function ($worker) {
                if (isset($worker['email'])) {
                    $worker['email'] = strtolower(trim($worker['email']));
                }
                return $worker;
            }, $this->workers);

            $this->merge(['workers' => $workers]);
        }
    }
}
