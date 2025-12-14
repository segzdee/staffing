<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AdminSettings;

class WithdrawRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = auth()->user();

        // Only verified users with sufficient balance can withdraw
        return $user && $user->verified_id == 'yes' && $user->balance > 0;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $settings = AdminSettings::first();
        $minWithdraw = $settings->min_withdrawal_amount ?? 10;
        $maxWithdraw = auth()->user()->balance ?? 0;

        return [
            'amount' => ['required', 'numeric', 'min:' . $minWithdraw, 'max:' . $maxWithdraw],
            'payment_method' => ['required', Rule::in(['bank', 'paypal', 'zelle', 'payoneer', 'crypto'])],

            // Bank transfer details
            'bank_name' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:100'],
            'bank_account' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'bank_routing' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:20'],
            'account_holder' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:100'],

            // PayPal
            'paypal_email' => ['required_if:payment_method,paypal', 'nullable', 'email', 'max:255'],

            // Crypto
            'crypto_address' => ['required_if:payment_method,crypto', 'nullable', 'string', 'max:255'],
            'crypto_type' => ['required_if:payment_method,crypto', 'nullable', Rule::in(['bitcoin', 'ethereum', 'usdt'])],
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
        $minWithdraw = $settings->min_withdrawal_amount ?? 10;

        return [
            'amount.required' => 'Withdrawal amount is required.',
            'amount.min' => 'Minimum withdrawal amount is $' . $minWithdraw . '.',
            'amount.max' => 'Withdrawal amount cannot exceed your available balance.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method.',
            'bank_name.required_if' => 'Bank name is required for bank transfers.',
            'bank_account.required_if' => 'Bank account number is required for bank transfers.',
            'paypal_email.required_if' => 'PayPal email is required for PayPal withdrawals.',
            'crypto_address.required_if' => 'Cryptocurrency wallet address is required.',
        ];
    }
}
