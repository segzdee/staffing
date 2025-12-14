<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
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
        return [
            'id' => ['required', 'exists:users,id'],
            'interval' => ['sometimes', 'required', Rule::in(['monthly', 'quarterly', 'biannually', 'yearly'])],
            'payment_gateway' => ['sometimes', 'required', Rule::in(['stripe', 'paypal', 'wallet', 'bank', 'paystack', 'mollie', 'mercadopago', 'flutterwave'])],
            'agree_terms' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => 'Creator ID is required.',
            'id.exists' => 'Creator not found.',
            'interval.in' => 'Invalid subscription interval.',
            'payment_gateway.in' => 'Invalid payment gateway.',
            'agree_terms.accepted' => 'You must agree to the terms and conditions.',
        ];
    }
}
