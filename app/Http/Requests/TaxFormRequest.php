<?php

namespace App\Http\Requests;

use App\Models\TaxForm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * GLO-002: Tax Form Submission Request Validation
 */
class TaxFormRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'form_type' => [
                'required',
                Rule::in([
                    TaxForm::TYPE_W9,
                    TaxForm::TYPE_W8BEN,
                    TaxForm::TYPE_W8BENE,
                    TaxForm::TYPE_P45,
                    TaxForm::TYPE_P60,
                    TaxForm::TYPE_SELF_ASSESSMENT,
                    TaxForm::TYPE_TAX_DECLARATION,
                ]),
            ],
            'legal_name' => ['required', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'country_code' => ['required', 'string', 'size:2'],
            'entity_type' => [
                'required',
                Rule::in([
                    TaxForm::ENTITY_INDIVIDUAL,
                    TaxForm::ENTITY_SOLE_PROPRIETOR,
                    TaxForm::ENTITY_LLC,
                    TaxForm::ENTITY_CORPORATION,
                    TaxForm::ENTITY_PARTNERSHIP,
                    TaxForm::ENTITY_TRUST,
                    TaxForm::ENTITY_ESTATE,
                ]),
            ],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB max
        ];

        // Form-type specific validation
        $formType = $this->input('form_type');

        switch ($formType) {
            case TaxForm::TYPE_W9:
                $rules['tax_id'] = ['required', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$|^\d{2}-\d{7}$/'];
                $rules['certification'] = ['required', 'accepted'];
                break;

            case TaxForm::TYPE_W8BEN:
                $rules['tax_id'] = ['nullable', 'string', 'max:50'];
                $rules['foreign_tax_id'] = ['nullable', 'string', 'max:50'];
                $rules['date_of_birth'] = ['required', 'date', 'before:today'];
                $rules['treaty_country'] = ['nullable', 'string', 'size:2'];
                $rules['treaty_article'] = ['nullable', 'string', 'max:50'];
                $rules['treaty_rate'] = ['nullable', 'numeric', 'min:0', 'max:100'];
                $rules['certification'] = ['required', 'accepted'];
                break;

            case TaxForm::TYPE_W8BENE:
                $rules['tax_id'] = ['nullable', 'string', 'max:50'];
                $rules['foreign_tax_id'] = ['nullable', 'string', 'max:50'];
                $rules['entity_name'] = ['required', 'string', 'max:255'];
                $rules['chapter3_status'] = ['required', 'string', 'max:100'];
                $rules['fatca_status'] = ['nullable', 'string', 'max:100'];
                $rules['certification'] = ['required', 'accepted'];
                break;

            case TaxForm::TYPE_P45:
            case TaxForm::TYPE_P60:
                $rules['tax_id'] = ['required', 'string', 'regex:/^[A-Z]{2}\d{6}[A-Z]$/'];
                $rules['employer_paye_reference'] = ['nullable', 'string', 'max:50'];
                $rules['tax_code'] = ['nullable', 'string', 'max:20'];
                break;

            case TaxForm::TYPE_SELF_ASSESSMENT:
                $rules['utr'] = ['nullable', 'string', 'regex:/^\d{10}$/'];
                $rules['tax_id'] = ['required', 'string', 'regex:/^[A-Z]{2}\d{6}[A-Z]$/'];
                $rules['is_self_employed'] = ['required', 'boolean'];
                break;

            case TaxForm::TYPE_TAX_DECLARATION:
                $rules['tax_id'] = ['required', 'string', 'max:50'];
                $rules['declaration_text'] = ['required', 'string'];
                $rules['acknowledged'] = ['required', 'accepted'];
                break;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'form_type.required' => 'Please select a tax form type.',
            'form_type.in' => 'Invalid tax form type selected.',
            'legal_name.required' => 'Your legal name is required.',
            'address.required' => 'Your address is required for tax purposes.',
            'country_code.required' => 'Please select your country of residence.',
            'country_code.size' => 'Invalid country code.',
            'entity_type.required' => 'Please select your entity type.',
            'tax_id.required' => 'Your Tax ID is required.',
            'tax_id.regex' => 'Please enter a valid Tax ID in the correct format.',
            'certification.required' => 'You must certify the accuracy of the information provided.',
            'certification.accepted' => 'You must accept the certification to submit this form.',
            'document.mimes' => 'The document must be a PDF or image file (JPG, PNG).',
            'document.max' => 'The document must not exceed 10MB.',
            'date_of_birth.required' => 'Date of birth is required for W-8BEN forms.',
            'date_of_birth.before' => 'Please enter a valid date of birth.',
            'utr.regex' => 'Please enter a valid 10-digit Unique Taxpayer Reference.',
            'is_self_employed.required' => 'Please confirm your self-employment status.',
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
            'tax_id' => 'Tax ID',
            'legal_name' => 'legal name',
            'business_name' => 'business name',
            'country_code' => 'country',
            'entity_type' => 'entity type',
            'foreign_tax_id' => 'foreign tax identification number',
            'date_of_birth' => 'date of birth',
            'treaty_country' => 'tax treaty country',
            'treaty_article' => 'treaty article',
            'treaty_rate' => 'treaty withholding rate',
            'utr' => 'Unique Taxpayer Reference (UTR)',
            'employer_paye_reference' => 'employer PAYE reference',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize country code to uppercase
        if ($this->has('country_code')) {
            $this->merge([
                'country_code' => strtoupper($this->input('country_code')),
            ]);
        }

        // Normalize treaty country to uppercase
        if ($this->has('treaty_country')) {
            $this->merge([
                'treaty_country' => strtoupper($this->input('treaty_country')),
            ]);
        }
    }
}
