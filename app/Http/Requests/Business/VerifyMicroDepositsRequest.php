<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * VerifyMicroDepositsRequest
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Validates micro-deposit verification amounts for ACH bank accounts.
 */
class VerifyMicroDepositsRequest extends FormRequest
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
            'payment_method_id' => ['required', 'integer', 'exists:business_payment_methods,id'],
            'amount_1' => ['required', 'integer', 'min:1', 'max:99'],
            'amount_2' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Payment method ID is required.',
            'payment_method_id.exists' => 'Payment method not found.',
            'amount_1.required' => 'First deposit amount is required.',
            'amount_1.integer' => 'First amount must be a whole number in cents.',
            'amount_1.min' => 'First amount must be at least 1 cent.',
            'amount_1.max' => 'First amount cannot exceed 99 cents.',
            'amount_2.required' => 'Second deposit amount is required.',
            'amount_2.integer' => 'Second amount must be a whole number in cents.',
            'amount_2.min' => 'Second amount must be at least 1 cent.',
            'amount_2.max' => 'Second amount cannot exceed 99 cents.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'amount_1' => 'first deposit amount',
            'amount_2' => 'second deposit amount',
        ];
    }
}
