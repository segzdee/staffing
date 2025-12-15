<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Portfolio Item Request
 * WKR-010: Worker Portfolio & Showcase Features
 */
class UpdatePortfolioItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('portfolioItem');

        return auth()->check()
            && auth()->user()->isWorker()
            && $item
            && $item->worker_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'is_visible' => [
                'sometimes',
                'boolean',
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
            'title.required' => 'Please provide a title for your portfolio item.',
            'title.min' => 'Title must be at least 3 characters.',
            'title.max' => 'Title cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
        ];
    }
}
