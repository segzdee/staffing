<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AdminSettings;

class AddFundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $settings = AdminSettings::first();
        $minDeposit = $settings->min_deposits_amount ?? 5;
        $maxDeposit = $settings->max_deposits_amount ?? 10000;

        return [
            'amount' => ['required', 'numeric', 'min:' . $minDeposit, 'max:' . $maxDeposit],
            'payment_gateway' => ['required', Rule::in(['stripe', 'paypal', 'bank', 'paystack', 'mollie', 'mercadopago', 'flutterwave', 'coinpayments'])],
            'agree_terms' => ['sometimes', 'accepted'],

            // Bank transfer specific
            'bank_proof' => ['required_if:payment_gateway,bank', 'nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        $settings = AdminSettings::first();
        $minDeposit = $settings->min_deposits_amount ?? 5;

        return [
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Minimum deposit amount is $' . $minDeposit . '.',
            'payment_gateway.required' => 'Payment method is required.',
            'payment_gateway.in' => 'Invalid payment method.',
            'bank_proof.required_if' => 'Bank transfer proof is required for bank deposits.',
            'agree_terms.accepted' => 'You must agree to the terms and conditions.',
        ];
    }
}
