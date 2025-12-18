<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;

/**
 * SubmitDisputeEvidenceRequest
 *
 * FIN-010: Validation for submitting dispute evidence.
 */
class SubmitDisputeEvidenceRequest extends FormRequest
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
        $maxFiles = config('disputes.max_evidence_files', 10);
        $maxSize = config('disputes.max_evidence_file_size_mb', 10) * 1024; // Convert to KB
        $allowedTypes = config('disputes.allowed_evidence_types', [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'txt', 'csv',
        ]);

        $mimes = implode(',', $allowedTypes);

        return [
            'files' => [
                'required',
                'array',
                'min:1',
                "max:{$maxFiles}",
            ],
            'files.*' => [
                'required',
                'file',
                "max:{$maxSize}",
                "mimes:{$mimes}",
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
        $maxFiles = config('disputes.max_evidence_files', 10);
        $maxSize = config('disputes.max_evidence_file_size_mb', 10);

        return [
            'files.required' => 'Please select at least one file to upload.',
            'files.array' => 'Invalid file upload format.',
            'files.min' => 'Please select at least one file.',
            'files.max' => "You can upload a maximum of {$maxFiles} files at once.",
            'files.*.required' => 'File upload failed.',
            'files.*.file' => 'Invalid file format.',
            'files.*.max' => "Each file must be less than {$maxSize}MB.",
            'files.*.mimes' => 'File type not allowed. Please upload images, PDFs, or document files.',
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
            'files' => 'evidence files',
            'files.*' => 'evidence file',
        ];
    }
}
