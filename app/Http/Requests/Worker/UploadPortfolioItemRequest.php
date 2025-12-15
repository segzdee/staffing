<?php

namespace App\Http\Requests\Worker;

use App\Models\WorkerPortfolioItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload Portfolio Item Request
 * WKR-010: Worker Portfolio & Showcase Features
 */
class UploadPortfolioItemRequest extends FormRequest
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
        $type = $this->input('type', 'photo');

        // Get max file size based on type
        $maxSize = match ($type) {
            'video' => 50 * 1024, // 50MB in KB
            'document', 'certification' => 5 * 1024, // 5MB in KB
            default => 10 * 1024, // 10MB in KB
        };

        // Get allowed MIME types based on type
        $allowedMimes = match ($type) {
            'video' => 'mp4,mov,webm',
            'document' => 'pdf',
            'certification' => 'pdf,jpg,jpeg,png',
            default => 'jpg,jpeg,png,webp',
        };

        return [
            'type' => [
                'required',
                'string',
                Rule::in(WorkerPortfolioItem::TYPES),
            ],
            'title' => [
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
            'file' => [
                'required',
                'file',
                'mimes:' . $allowedMimes,
                'max:' . $maxSize,
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
            'type.required' => 'Please select the type of portfolio item.',
            'type.in' => 'Invalid portfolio item type.',
            'title.required' => 'Please provide a title for your portfolio item.',
            'title.min' => 'Title must be at least 3 characters.',
            'title.max' => 'Title cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'file.required' => 'Please upload a file.',
            'file.mimes' => 'Invalid file type for the selected category.',
            'file.max' => 'File size exceeds the maximum limit.',
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
            'type' => 'item type',
            'title' => 'title',
            'description' => 'description',
            'file' => 'uploaded file',
        ];
    }
}
