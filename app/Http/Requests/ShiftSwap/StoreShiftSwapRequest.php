<?php

namespace App\Http\Requests\ShiftSwap;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftSwapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'The reason cannot exceed 500 characters.',
            'notes.max' => 'The notes cannot exceed 1000 characters.',
        ];
    }
}

