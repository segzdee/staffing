<?php

namespace App\Http\Requests\ShiftSwap;

use Illuminate\Foundation\Http\FormRequest;

class RejectShiftSwapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isBusiness() || $this->user()->isAgency();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'The rejection reason cannot exceed 500 characters.',
        ];
    }
}

