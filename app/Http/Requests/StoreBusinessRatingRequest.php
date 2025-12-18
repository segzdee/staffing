<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * WKR-004: Form Request for worker rating business (4 categories).
 */
class StoreBusinessRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $min = config('ratings.min_rating', 1);
        $max = config('ratings.max_rating', 5);

        return [
            // Category ratings (all required for full weighted calculation)
            'punctuality_rating' => "required|integer|min:{$min}|max:{$max}",
            'communication_rating' => "required|integer|min:{$min}|max:{$max}",
            'professionalism_rating' => "required|integer|min:{$min}|max:{$max}",
            'payment_reliability_rating' => "required|integer|min:{$min}|max:{$max}",

            // Optional review text
            'review_text' => 'nullable|string|max:1000',

            // Legacy field (optional, calculated automatically if not provided)
            'overall' => "nullable|integer|min:{$min}|max:{$max}",

            // Would work again indicator
            'would_work_again' => 'nullable|boolean',
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
            'punctuality_rating.required' => 'Please rate the business\'s punctuality.',
            'punctuality_rating.min' => 'Punctuality rating must be at least :min.',
            'punctuality_rating.max' => 'Punctuality rating cannot exceed :max.',

            'communication_rating.required' => 'Please rate the business\'s communication.',
            'communication_rating.min' => 'Communication rating must be at least :min.',
            'communication_rating.max' => 'Communication rating cannot exceed :max.',

            'professionalism_rating.required' => 'Please rate the business\'s professionalism.',
            'professionalism_rating.min' => 'Professionalism rating must be at least :min.',
            'professionalism_rating.max' => 'Professionalism rating cannot exceed :max.',

            'payment_reliability_rating.required' => 'Please rate the business\'s payment reliability.',
            'payment_reliability_rating.min' => 'Payment reliability rating must be at least :min.',
            'payment_reliability_rating.max' => 'Payment reliability rating cannot exceed :max.',

            'review_text.max' => 'Review text cannot exceed 1000 characters.',
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
            'punctuality_rating' => 'punctuality',
            'communication_rating' => 'communication',
            'professionalism_rating' => 'professionalism',
            'payment_reliability_rating' => 'payment reliability',
            'review_text' => 'review',
        ];
    }

    /**
     * Get category ratings from request.
     *
     * @return array<string, int>
     */
    public function getCategoryRatings(): array
    {
        return [
            'punctuality' => (int) $this->input('punctuality_rating'),
            'communication' => (int) $this->input('communication_rating'),
            'professionalism' => (int) $this->input('professionalism_rating'),
            'payment_reliability' => (int) $this->input('payment_reliability_rating'),
        ];
    }
}
