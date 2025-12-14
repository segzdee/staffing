<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AdminSettings;

class SendTipRequest extends FormRequest
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
        $minTip = $settings->min_tip_amount ?? 1;
        $maxTip = $settings->max_tip_amount ?? 100000;

        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:' . $minTip, 'max:' . $maxTip],
            'payment_gateway' => ['required', Rule::in(['stripe', 'paypal', 'wallet', 'paystack', 'mollie', 'mercadopago', 'flutterwave'])],
            'message' => ['nullable', 'string', 'max:1000'],
            'anonymous' => ['sometimes', 'boolean'],
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
        $minTip = $settings->min_tip_amount ?? 1;

        return [
            'user_id.required' => 'Recipient is required.',
            'user_id.exists' => 'Recipient not found.',
            'amount.required' => 'Tip amount is required.',
            'amount.min' => 'Minimum tip amount is $' . $minTip . '.',
            'payment_gateway.required' => 'Payment method is required.',
            'payment_gateway.in' => 'Invalid payment method.',
        ];
    }
}
