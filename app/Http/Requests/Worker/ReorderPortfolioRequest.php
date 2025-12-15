<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reorder Portfolio Items Request
 * WKR-010: Worker Portfolio & Showcase Features
 */
class ReorderPortfolioRequest extends FormRequest
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
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*' => [
                'required',
                'integer',
                'exists:worker_portfolio_items,id',
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
            'items.required' => 'Please provide the items to reorder.',
            'items.array' => 'Items must be provided as an array.',
            'items.min' => 'At least one item must be provided.',
            'items.*.exists' => 'One or more portfolio items do not exist.',
        ];
    }
}
