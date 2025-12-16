<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AGY-REG-004: Bulk Import Request Validation
 *
 * Validates CSV file uploads for worker bulk import.
 */
class BulkImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAgency();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:5120', // 5MB
            ],
            'send_invitations' => 'nullable|boolean',
            'skip_existing' => 'nullable|boolean',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
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
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file' => 'The uploaded file is invalid.',
            'csv_file.mimes' => 'The file must be a CSV file.',
            'csv_file.max' => 'The file size must not exceed 5MB.',
            'default_commission_rate.numeric' => 'Commission rate must be a number.',
            'default_commission_rate.min' => 'Commission rate cannot be negative.',
            'default_commission_rate.max' => 'Commission rate cannot exceed 100%.',
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
            'csv_file' => 'CSV file',
            'send_invitations' => 'send invitations',
            'skip_existing' => 'skip existing',
            'default_commission_rate' => 'default commission rate',
        ];
    }
}
