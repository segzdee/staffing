<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload Business Logo Request
 * BIZ-REG-003: Validates logo upload
 */
class UploadBusinessLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isBusiness();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,svg',
                'max:5120', // 5MB in KB
                'dimensions:min_width=200,min_height=200,max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Please select a logo file to upload',
            'logo.file' => 'The upload must be a valid file',
            'logo.image' => 'The file must be an image',
            'logo.mimes' => 'Logo must be a JPG, PNG, or SVG file',
            'logo.max' => 'Logo file size must be less than 5MB',
            'logo.dimensions' => 'Logo must be between 200x200 and 2000x2000 pixels',
        ];
    }
}
