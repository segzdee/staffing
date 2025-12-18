<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BIZ-010: Validation for sending a template to workers.
 */
class SendTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isBusiness();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', 'exists:users,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'custom_variables' => ['nullable', 'array'],
            'custom_variables.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recipient_ids.required' => 'Please select at least one recipient.',
            'recipient_ids.min' => 'Please select at least one recipient.',
            'recipient_ids.*.exists' => 'One or more selected recipients are invalid.',
            'shift_id.exists' => 'The selected shift does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'recipient_ids' => 'recipients',
            'shift_id' => 'shift',
            'custom_variables' => 'custom variables',
        ];
    }
}
