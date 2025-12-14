<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AdminSettings;

class SendMessageRequest extends FormRequest
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
        $maxFileSize = $settings->file_size_allowed ?? 5120;

        return [
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required_without_all:photo,video,music,file', 'nullable', 'string', 'max:5000'],

            // Media attachments
            'photo' => ['nullable', 'array', 'max:10'],
            'photo.*' => ['image', 'mimes:jpg,gif,png,jpe,jpeg,webp', 'max:' . $maxFileSize],

            'video' => ['nullable', 'array', 'max:5'],
            'video.*' => ['mimes:mp4,mov,wmv,avi,mkv,webm', 'max:' . ($maxFileSize * 10)],

            'music' => ['nullable', 'array', 'max:5'],
            'music.*' => ['mimes:mp3,ogg,wav,flac', 'max:' . $maxFileSize],

            'file' => ['nullable', 'array', 'max:5'],
            'file.*' => ['file', 'mimes:pdf,doc,docx,txt,zip,rar', 'max:' . ($maxFileSize * 2)],

            // Pay-per-view message
            'price' => ['nullable', 'numeric', 'min:' . ($settings->min_ppv_amount ?? 1), 'max:99999'],
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
            'user_id.required' => 'Recipient is required.',
            'user_id.exists' => 'Recipient not found.',
            'message.required_without_all' => 'Message content or media attachment is required.',
            'photo.max' => 'Maximum 10 photos allowed per message.',
            'video.max' => 'Maximum 5 videos allowed per message.',
        ];
    }
}
