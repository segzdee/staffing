<?php

namespace App\Http\Requests\Worker;

use App\Models\BackgroundCheckConsent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * STAFF-REG-006: Submit Background Check Consent Request
 */
class SubmitConsentRequest extends FormRequest
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
            'check_id' => [
                'required',
                'integer',
                'exists:background_checks,id',
            ],
            'consent_type' => [
                'required',
                'string',
                Rule::in([
                    BackgroundCheckConsent::TYPE_FCRA_DISCLOSURE,
                    BackgroundCheckConsent::TYPE_FCRA_AUTHORIZATION,
                    BackgroundCheckConsent::TYPE_DBS_CONSENT,
                    BackgroundCheckConsent::TYPE_GENERAL_CONSENT,
                    BackgroundCheckConsent::TYPE_DATA_PROCESSING,
                ]),
            ],
            'signature_type' => [
                'required',
                'string',
                Rule::in([
                    BackgroundCheckConsent::SIGNATURE_TYPED,
                    BackgroundCheckConsent::SIGNATURE_DRAWN,
                    BackgroundCheckConsent::SIGNATURE_CHECKBOX,
                ]),
            ],
            'signature_data' => [
                'required_if:signature_type,drawn',
                'nullable',
                'string',
                'max:65535', // Base64 encoded signature image
            ],
            'signatory_name' => [
                'required_if:signature_type,typed',
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
            'acknowledged' => [
                'required_if:signature_type,checkbox',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'check_id.required' => 'Background check ID is required.',
            'check_id.exists' => 'Background check not found.',
            'consent_type.required' => 'Consent type is required.',
            'consent_type.in' => 'Invalid consent type.',
            'signature_type.required' => 'Please select how you want to sign.',
            'signature_type.in' => 'Invalid signature type.',
            'signature_data.required_if' => 'Please provide your drawn signature.',
            'signatory_name.required_if' => 'Please type your full legal name.',
            'signatory_name.min' => 'Name must be at least 2 characters.',
            'acknowledged.required_if' => 'You must acknowledge the disclosure.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate checkbox acknowledgment
            if ($this->signature_type === BackgroundCheckConsent::SIGNATURE_CHECKBOX) {
                if (!$this->boolean('acknowledged')) {
                    $validator->errors()->add(
                        'acknowledged',
                        'You must acknowledge and accept the disclosure to proceed.'
                    );
                }
            }

            // Validate typed signature matches user's name
            if ($this->signature_type === BackgroundCheckConsent::SIGNATURE_TYPED) {
                $userName = $this->user()?->name;
                $signatoryName = $this->signatory_name;

                // Simple check - names should be similar (not exact due to formatting)
                if ($signatoryName && $userName) {
                    $similarity = 0;
                    similar_text(
                        strtolower($userName),
                        strtolower($signatoryName),
                        $similarity
                    );

                    if ($similarity < 50) {
                        $validator->errors()->add(
                            'signatory_name',
                            'The typed name should match your registered name.'
                        );
                    }
                }
            }
        });
    }
}
