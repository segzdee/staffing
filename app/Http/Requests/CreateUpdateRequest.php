<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AdminSettings;

class CreateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $settings = AdminSettings::first();
        $maxFileSize = $settings->file_size_allowed ?? 5120; // KB

        return [
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['nullable', 'numeric', 'min:' . ($settings->min_subscription_amount ?? 1), 'max:99999'],
            'token' => ['sometimes', 'required', 'string'],

            // Media validation
            'photo' => ['nullable', 'array'],
            'photo.*' => ['image', 'mimes:jpg,gif,png,jpe,jpeg,webp', 'max:' . $maxFileSize],

            'video' => ['nullable', 'array'],
            'video.*' => ['mimes:mp4,mov,wmv,avi,mkv,webm', 'max:' . ($settings->file_size_allowed_verify_account ?? 102400)],

            'music' => ['nullable', 'array'],
            'music.*' => ['mimes:mp3,ogg,wav,flac', 'max:' . $maxFileSize],

            // Locked content
            'locked' => ['sometimes', 'in:yes,no'],
            'price_post' => ['required_if:locked,yes', 'nullable', 'numeric', 'min:' . ($settings->min_ppv_amount ?? 1), 'max:99999'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'photo.*.image' => 'The file must be an image.',
            'photo.*.mimes' => 'Only JPG, PNG, GIF, and WebP images are allowed.',
            'photo.*.max' => 'Image file size exceeds maximum allowed size.',
            'video.*.mimes' => 'Only MP4, MOV, WMV, AVI, MKV, and WebM videos are allowed.',
            'video.*.max' => 'Video file size exceeds maximum allowed size.',
            'music.*.mimes' => 'Only MP3, OGG, WAV, and FLAC audio files are allowed.',
            'price_post.required_if' => 'Price is required for locked content.',
        ];
    }
}
