<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Agency Registration Step Request
 *
 * AGY-REG-002: Validates agency registration step data.
 * Rules vary by step number.
 */
class RegistrationStepRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Registration is open to all
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $step = (int) $this->route('step');

        return match ($step) {
            1 => $this->step1Rules(),
            2 => $this->step2Rules(),
            3 => $this->step3Rules(),
            4 => $this->step4Rules(),
            5 => $this->step5Rules(),
            6 => $this->step6Rules(),
            7 => $this->step7Rules(),
            8 => $this->step8Rules(),
            default => [],
        };
    }

    /**
     * Step 1: Business Information
     */
    protected function step1Rules(): array
    {
        return [
            'business_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'registration_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'agency_type' => [
                'required',
                'string',
                'in:staffing_agency,temp_agency,recruitment_firm,healthcare_staffing,hospitality_staffing,industrial_staffing,it_staffing,other',
            ],
            'years_in_business' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'business_description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Step 2: Contact Details
     */
    protected function step2Rules(): array
    {
        return [
            'contact_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'contact_email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'contact_phone' => [
                'required',
                'string',
                'max:20',
            ],
            // SECURITY: Strengthened password policy - minimum 12 characters with complexity requirements
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'address' => [
                'required',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'state' => [
                'required',
                'string',
                'max:100',
            ],
            'postal_code' => [
                'required',
                'string',
                'max:20',
            ],
            'country' => [
                'required',
                'string',
                'max:100',
            ],
            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
        ];
    }

    /**
     * Step 3: Document Upload
     * Document uploads are handled separately via AJAX.
     * This validates the confirmation that documents are uploaded.
     */
    protected function step3Rules(): array
    {
        return [
            'documents_confirmed' => [
                'required',
                'boolean',
                'accepted',
            ],
            'license_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'license_state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'insurance_policy_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'insurance_expiry' => [
                'nullable',
                'date',
                'after:today',
            ],
            'tax_id' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Step 4: Partnership Tier Selection
     */
    protected function step4Rules(): array
    {
        return [
            'partnership_tier' => [
                'required',
                'string',
                'in:standard,professional,enterprise',
            ],
            'billing_cycle' => [
                'nullable',
                'string',
                'in:monthly,annual',
            ],
            'promo_code' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    /**
     * Step 5: Worker Pool Details
     */
    protected function step5Rules(): array
    {
        return [
            'existing_workers_count' => [
                'required',
                'string',
                'in:1-10,11-50,51-100,101-500,500+',
            ],
            'industries' => [
                'required',
                'array',
                'min:1',
            ],
            'industries.*' => [
                'string',
                'max:100',
            ],
            'service_areas' => [
                'nullable',
                'array',
            ],
            'service_areas.*' => [
                'string',
                'max:100',
            ],
            'worker_types' => [
                'nullable',
                'array',
            ],
            'worker_types.*' => [
                'string',
                'in:full_time,part_time,temporary,seasonal,contract',
            ],
            'average_placements_monthly' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Step 6: Business References
     */
    protected function step6Rules(): array
    {
        return [
            'references' => [
                'required',
                'array',
                'min:2',
                'max:3',
            ],
            'references.*.company_name' => [
                'required',
                'string',
                'max:255',
            ],
            'references.*.contact_name' => [
                'required',
                'string',
                'max:255',
            ],
            'references.*.contact_email' => [
                'required',
                'email',
                'max:255',
            ],
            'references.*.contact_phone' => [
                'required',
                'string',
                'max:20',
            ],
            'references.*.relationship' => [
                'required',
                'string',
                'in:client,partner,vendor,other',
            ],
            'references.*.years_known' => [
                'nullable',
                'integer',
                'min:0',
                'max:50',
            ],
        ];
    }

    /**
     * Step 7: Commercial Terms Review
     */
    protected function step7Rules(): array
    {
        return [
            'terms_accepted' => [
                'required',
                'accepted',
            ],
            'privacy_accepted' => [
                'required',
                'accepted',
            ],
            'commercial_terms_accepted' => [
                'required',
                'accepted',
            ],
            'data_processing_accepted' => [
                'nullable',
                'accepted',
            ],
            'marketing_consent' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Step 8: Final Review & Submit
     */
    protected function step8Rules(): array
    {
        return [
            'final_confirmation' => [
                'required',
                'accepted',
            ],
            'accuracy_confirmed' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Step 1
            'business_name.required' => 'Please enter your agency name.',
            'business_name.min' => 'Agency name must be at least 2 characters.',
            'agency_type.required' => 'Please select your agency type.',
            'agency_type.in' => 'Please select a valid agency type.',

            // Step 2
            'contact_name.required' => 'Please enter a contact name.',
            'contact_email.required' => 'Please enter your email address.',
            'contact_email.email' => 'Please enter a valid email address.',
            'contact_email.unique' => 'An account with this email already exists.',
            'contact_phone.required' => 'Please enter a contact phone number.',
            'password.required' => 'Please create a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'address.required' => 'Please enter your business address.',
            'city.required' => 'Please enter your city.',
            'state.required' => 'Please enter your state/province.',
            'postal_code.required' => 'Please enter your postal code.',
            'country.required' => 'Please select your country.',
            'website.url' => 'Please enter a valid website URL.',

            // Step 3
            'documents_confirmed.required' => 'Please confirm you have uploaded the required documents.',
            'documents_confirmed.accepted' => 'Please upload all required documents before continuing.',
            'insurance_expiry.after' => 'Insurance expiry date must be in the future.',

            // Step 4
            'partnership_tier.required' => 'Please select a partnership tier.',
            'partnership_tier.in' => 'Please select a valid partnership tier.',

            // Step 5
            'existing_workers_count.required' => 'Please select your current worker count range.',
            'existing_workers_count.in' => 'Please select a valid worker count range.',
            'industries.required' => 'Please select at least one industry.',
            'industries.min' => 'Please select at least one industry.',

            // Step 6
            'references.required' => 'Please provide business references.',
            'references.min' => 'Please provide at least 2 business references.',
            'references.max' => 'You can provide a maximum of 3 business references.',
            'references.*.company_name.required' => 'Reference company name is required.',
            'references.*.contact_name.required' => 'Reference contact name is required.',
            'references.*.contact_email.required' => 'Reference contact email is required.',
            'references.*.contact_email.email' => 'Please enter a valid email for the reference.',
            'references.*.contact_phone.required' => 'Reference contact phone is required.',
            'references.*.relationship.required' => 'Please select the relationship type.',
            'references.*.relationship.in' => 'Please select a valid relationship type.',

            // Step 7
            'terms_accepted.required' => 'You must accept the Terms of Service.',
            'terms_accepted.accepted' => 'You must accept the Terms of Service.',
            'privacy_accepted.required' => 'You must accept the Privacy Policy.',
            'privacy_accepted.accepted' => 'You must accept the Privacy Policy.',
            'commercial_terms_accepted.required' => 'You must accept the Commercial Terms.',
            'commercial_terms_accepted.accepted' => 'You must accept the Commercial Terms.',

            // Step 8
            'final_confirmation.required' => 'Please confirm you are ready to submit.',
            'final_confirmation.accepted' => 'Please confirm you are ready to submit.',
            'accuracy_confirmed.required' => 'Please confirm the accuracy of your information.',
            'accuracy_confirmed.accepted' => 'Please confirm the accuracy of your information.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'business_name' => 'agency name',
            'registration_number' => 'registration number',
            'agency_type' => 'agency type',
            'contact_name' => 'contact name',
            'contact_email' => 'email address',
            'contact_phone' => 'phone number',
            'partnership_tier' => 'partnership tier',
            'existing_workers_count' => 'worker count',
            'references.*.company_name' => 'company name',
            'references.*.contact_name' => 'contact name',
            'references.*.contact_email' => 'contact email',
            'references.*.contact_phone' => 'contact phone',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $step = (int) $this->route('step');

        // Clean up common fields
        if ($this->has('contact_email')) {
            $this->merge([
                'contact_email' => strtolower(trim($this->contact_email ?? '')),
            ]);
        }

        if ($this->has('business_name')) {
            $this->merge([
                'business_name' => trim($this->business_name ?? ''),
            ]);
        }

        if ($this->has('contact_name')) {
            $this->merge([
                'contact_name' => trim($this->contact_name ?? ''),
            ]);
        }

        // Clean up reference emails
        if ($this->has('references') && is_array($this->references)) {
            $references = $this->references;
            foreach ($references as $key => $reference) {
                if (isset($reference['contact_email'])) {
                    $references[$key]['contact_email'] = strtolower(trim($reference['contact_email']));
                }
            }
            $this->merge(['references' => $references]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new \Illuminate\Validation\ValidationException($validator, response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
