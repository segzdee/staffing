<?php

namespace App\Http\Requests\Worker;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * OpenDisputeRequest
 *
 * FIN-010: Validation for opening a new dispute.
 */
class OpenDisputeRequest extends FormRequest
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
        $minAmount = config('disputes.min_amount', 5.00);

        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    Dispute::TYPE_PAYMENT,
                    Dispute::TYPE_HOURS,
                    Dispute::TYPE_DEDUCTION,
                    Dispute::TYPE_BONUS,
                    Dispute::TYPE_EXPENSES,
                    Dispute::TYPE_OTHER,
                ]),
            ],
            'disputed_amount' => [
                'required',
                'numeric',
                "min:{$minAmount}",
                'max:999999.99',
            ],
            'worker_description' => [
                'required',
                'string',
                'min:20',
                'max:5000',
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
        $minAmount = config('disputes.min_amount', 5.00);

        return [
            'type.required' => 'Please select a dispute type.',
            'type.in' => 'Invalid dispute type selected.',
            'disputed_amount.required' => 'Please enter the disputed amount.',
            'disputed_amount.numeric' => 'The disputed amount must be a number.',
            'disputed_amount.min' => "The disputed amount must be at least \${$minAmount}.",
            'disputed_amount.max' => 'The disputed amount is too large.',
            'worker_description.required' => 'Please describe your dispute.',
            'worker_description.min' => 'Please provide a more detailed description (at least 20 characters).',
            'worker_description.max' => 'Description is too long (maximum 5000 characters).',
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
            'type' => 'dispute type',
            'disputed_amount' => 'disputed amount',
            'worker_description' => 'description',
        ];
    }
}
