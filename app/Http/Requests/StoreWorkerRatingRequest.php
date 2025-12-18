<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * WKR-004: Form Request for business rating worker (4 categories).
 */
class StoreWorkerRatingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isBusiness();
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
            'quality_rating' => "required|integer|min:{$min}|max:{$max}",
            'professionalism_rating' => "required|integer|min:{$min}|max:{$max}",
            'reliability_rating' => "required|integer|min:{$min}|max:{$max}",

            // Optional review text
            'review_text' => 'nullable|string|max:1000',

            // Legacy field (optional, calculated automatically if not provided)
            'overall' => "nullable|integer|min:{$min}|max:{$max}",

            // Would hire again indicator
            'would_hire_again' => 'nullable|boolean',
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
            'punctuality_rating.required' => 'Please rate the worker\'s punctuality.',
            'punctuality_rating.min' => 'Punctuality rating must be at least :min.',
            'punctuality_rating.max' => 'Punctuality rating cannot exceed :max.',

            'quality_rating.required' => 'Please rate the quality of work.',
            'quality_rating.min' => 'Quality rating must be at least :min.',
            'quality_rating.max' => 'Quality rating cannot exceed :max.',

            'professionalism_rating.required' => 'Please rate the worker\'s professionalism.',
            'professionalism_rating.min' => 'Professionalism rating must be at least :min.',
            'professionalism_rating.max' => 'Professionalism rating cannot exceed :max.',

            'reliability_rating.required' => 'Please rate the worker\'s reliability.',
            'reliability_rating.min' => 'Reliability rating must be at least :min.',
            'reliability_rating.max' => 'Reliability rating cannot exceed :max.',

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
            'quality_rating' => 'work quality',
            'professionalism_rating' => 'professionalism',
            'reliability_rating' => 'reliability',
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
            'quality' => (int) $this->input('quality_rating'),
            'professionalism' => (int) $this->input('professionalism_rating'),
            'reliability' => (int) $this->input('reliability_rating'),
        ];
    }
}
