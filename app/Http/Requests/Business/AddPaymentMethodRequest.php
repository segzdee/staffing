<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AddPaymentMethodRequest
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Validates adding a new payment method via Stripe SetupIntent.
 */
class AddPaymentMethodRequest extends FormRequest
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
            'setup_intent_id' => ['required', 'string', 'starts_with:seti_'],

            // Billing details (optional but recommended)
            'billing_details' => ['sometimes', 'array'],
            'billing_details.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_details.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'billing_details.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'billing_details.address' => ['sometimes', 'nullable', 'array'],
            'billing_details.address.line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_details.address.line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_details.address.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_details.address.state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'billing_details.address.postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'billing_details.address.country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'billing_details.currency' => ['sometimes', 'nullable', 'string', 'size:3'],

            // Optional nickname
            'nickname' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'setup_intent_id.required' => 'Payment setup incomplete. Please try again.',
            'setup_intent_id.starts_with' => 'Invalid payment setup ID.',
            'billing_details.email.email' => 'Please provide a valid billing email address.',
            'billing_details.address.country.size' => 'Country must be a 2-letter code (e.g., US, GB).',
        ];
    }
}
