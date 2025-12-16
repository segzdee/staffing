<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * STAFF-REG-003: Worker Profile Photo Upload Request
 *
 * Validates profile photo uploads with comprehensive checks:
 * - File type validation (JPEG, PNG, WebP)
 * - File size limits (max 5MB)
 * - Image dimension requirements (minimum 200x200px)
 * - MIME type verification
 */
class UploadProfilePhotoRequest extends FormRequest
{
    /**
     * Maximum file size in kilobytes (5MB).
     */
    protected const MAX_FILE_SIZE = 5120;

    /**
     * Minimum image dimensions.
     */
    protected const MIN_WIDTH = 200;
    protected const MIN_HEIGHT = 200;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:' . self::MAX_FILE_SIZE,
                'dimensions:min_width=' . self::MIN_WIDTH . ',min_height=' . self::MIN_HEIGHT,
            ],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeMB = self::MAX_FILE_SIZE / 1024;

        return [
            'photo.required' => 'Please select a photo to upload.',
            'photo.file' => 'The uploaded file must be a valid file.',
            'photo.image' => 'The uploaded file must be an image.',
            'photo.mimes' => 'The photo must be a JPEG, PNG, or WebP image.',
            'photo.max' => "The photo size must not exceed {$maxSizeMB}MB.",
            'photo.dimensions' => 'The photo must be at least ' . self::MIN_WIDTH . 'x' . self::MIN_HEIGHT . ' pixels.',
        ];
    }

    /**
     * Get custom attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'photo' => 'profile photo',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('photo')) {
                $file = $this->file('photo');

                // Additional MIME type check for security
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    $validator->errors()->add('photo', 'The uploaded file type is not allowed.');
                }

                // Check for potential malicious content
                if ($file->getClientOriginalExtension() !== $file->guessExtension()) {
                    $validator->errors()->add('photo', 'The file extension does not match the file type.');
                }

                // Verify it's actually an image
                try {
                    $imageInfo = getimagesize($file->getRealPath());
                    if (!$imageInfo) {
                        $validator->errors()->add('photo', 'The uploaded file is not a valid image.');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('photo', 'Could not validate the uploaded image.');
                }
            }
        });
    }
}
