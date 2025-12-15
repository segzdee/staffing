<?php

namespace App\Http\Requests\Worker;

use App\Models\WorkerFeaturedStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Purchase Featured Status Request
 * WKR-010: Worker Portfolio & Showcase Features
 */
class PurchaseFeaturedStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tier' => [
                'required',
                'string',
                Rule::in(array_keys(WorkerFeaturedStatus::TIERS)),
            ],
            'payment_method_id' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tier.required' => 'Please select a featured tier.',
            'tier.in' => 'Invalid featured tier selected.',
            'payment_method_id.required' => 'Please select a payment method.',
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
            'tier' => 'featured tier',
            'payment_method_id' => 'payment method',
        ];
    }
}
