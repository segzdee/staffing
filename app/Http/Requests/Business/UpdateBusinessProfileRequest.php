<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Business Profile Request
 * BIZ-REG-003: Validates business profile update data
 */
class UpdateBusinessProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isBusiness();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic business info
            'business_name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],

            // Business categorization
            'business_category' => ['sometimes', 'string', 'max:50'],
            'industry' => ['sometimes', 'string', 'max:50'],

            // Company size
            'company_size' => ['nullable', Rule::in([
                'sole_proprietor', 'micro', 'small', 'medium', 'large', 'enterprise'
            ])],
            'employee_count' => ['nullable', 'string', 'max:50'],

            // Contact info
            'phone' => ['nullable', 'string', 'max:20'],
            'business_phone' => ['nullable', 'string', 'max:20'],

            // Location
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_city' => ['nullable', 'string', 'max:100'],
            'business_state' => ['nullable', 'string', 'max:100'],
            'business_country' => ['nullable', 'string', 'size:2'],

            // Currency and timezone
            'default_currency' => ['nullable', 'string', 'size:3'],
            'default_timezone' => ['nullable', 'string', 'max:50', 'timezone'],

            // Tax info
            'ein_tax_id' => ['nullable', 'string', 'max:50'],
            'business_registration_number' => ['nullable', 'string', 'max:50'],

            // Primary contact
            'primary_contact' => ['nullable', 'array'],
            'primary_contact.first_name' => ['required_with:primary_contact', 'string', 'max:100'],
            'primary_contact.last_name' => ['required_with:primary_contact', 'string', 'max:100'],
            'primary_contact.email' => ['required_with:primary_contact', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:20'],
            'primary_contact.job_title' => ['nullable', 'string', 'max:100'],

            // Billing contact
            'billing_contact' => ['nullable', 'array'],
            'billing_contact.first_name' => ['required_with:billing_contact', 'string', 'max:100'],
            'billing_contact.last_name' => ['required_with:billing_contact', 'string', 'max:100'],
            'billing_contact.email' => ['required_with:billing_contact', 'email', 'max:255'],
            'billing_contact.phone' => ['nullable', 'string', 'max:20'],
            'billing_contact.job_title' => ['nullable', 'string', 'max:100'],

            // Registered address
            'registered_address' => ['nullable', 'array'],
            'registered_address.address_line_1' => ['required_with:registered_address', 'string', 'max:255'],
            'registered_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'registered_address.city' => ['required_with:registered_address', 'string', 'max:100'],
            'registered_address.state_province' => ['required_with:registered_address', 'string', 'max:100'],
            'registered_address.postal_code' => ['required_with:registered_address', 'string', 'max:20'],
            'registered_address.country_code' => ['required_with:registered_address', 'string', 'size:2'],
            'registered_address.country_name' => ['nullable', 'string', 'max:100'],

            // Billing address
            'billing_address' => ['nullable', 'array'],
            'billing_address.address_line_1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.state_province' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
            'billing_address.country_name' => ['nullable', 'string', 'max:100'],

            // Operating address
            'operating_address' => ['nullable', 'array'],
            'operating_address.label' => ['nullable', 'string', 'max:100'],
            'operating_address.address_line_1' => ['required_with:operating_address', 'string', 'max:255'],
            'operating_address.address_line_2' => ['nullable', 'string', 'max:255'],
            'operating_address.city' => ['required_with:operating_address', 'string', 'max:100'],
            'operating_address.state_province' => ['required_with:operating_address', 'string', 'max:100'],
            'operating_address.postal_code' => ['required_with:operating_address', 'string', 'max:20'],
            'operating_address.country_code' => ['required_with:operating_address', 'string', 'size:2'],
            'operating_address.country_name' => ['nullable', 'string', 'max:100'],
            'operating_address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'operating_address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'business_name.min' => 'Business name must be at least 2 characters',
            'website.url' => 'Please enter a valid website URL',
            'default_timezone.timezone' => 'Please select a valid timezone',
            'business_country.size' => 'Country must be a 2-letter ISO code',
            'default_currency.size' => 'Currency must be a 3-letter ISO code',
            'primary_contact.email.email' => 'Please enter a valid email for primary contact',
            'billing_contact.email.email' => 'Please enter a valid email for billing contact',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'business_name' => 'business name',
            'legal_business_name' => 'legal business name',
            'trading_name' => 'trading name',
            'business_category' => 'business type',
            'company_size' => 'company size',
            'default_currency' => 'currency',
            'default_timezone' => 'timezone',
            'ein_tax_id' => 'tax ID',
            'primary_contact.first_name' => 'primary contact first name',
            'primary_contact.last_name' => 'primary contact last name',
            'primary_contact.email' => 'primary contact email',
            'billing_contact.first_name' => 'billing contact first name',
            'billing_contact.last_name' => 'billing contact last name',
            'billing_contact.email' => 'billing contact email',
            'registered_address.address_line_1' => 'registered address',
            'registered_address.city' => 'registered address city',
            'billing_address.address_line_1' => 'billing address',
            'operating_address.address_line_1' => 'operating address',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim string fields
        $data = [];

        if ($this->has('business_name')) {
            $data['business_name'] = trim($this->business_name);
        }

        if ($this->has('website') && $this->website) {
            $website = trim($this->website);
            // Add https:// if no protocol specified
            if (!preg_match('/^https?:\/\//', $website)) {
                $website = 'https://' . $website;
            }
            $data['website'] = $website;
        }

        if ($this->has('business_country')) {
            $data['business_country'] = strtoupper(trim($this->business_country));
        }

        if ($this->has('default_currency')) {
            $data['default_currency'] = strtoupper(trim($this->default_currency));
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }
}
