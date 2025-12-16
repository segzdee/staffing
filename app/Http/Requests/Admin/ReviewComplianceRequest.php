<?php

namespace App\Http\Requests\Admin;

use App\Models\AgencyComplianceCheck;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for reviewing agency application compliance checks
 * AGY-REG-003
 */
class ReviewComplianceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => 'required|string|in:run_check,update_status,run_all',
            'check_type' => [
                'required_if:action,run_check',
                'nullable',
                'string',
                'in:' . implode(',', AgencyComplianceCheck::CHECK_TYPES),
            ],
            'check_id' => [
                'required_if:action,update_status',
                'nullable',
                'integer',
                'exists:agency_compliance_checks,id',
            ],
            'status' => [
                'required_if:action,update_status',
                'nullable',
                'string',
                'in:' . implode(',', AgencyComplianceCheck::STATUSES),
            ],
            'notes' => 'nullable|string|max:2000',
            'failure_reason' => 'required_if:status,failed|nullable|string|max:1000',
            'risk_level' => [
                'nullable',
                'string',
                'in:' . implode(',', AgencyComplianceCheck::RISK_LEVELS),
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
            'action.required' => 'An action must be specified.',
            'action.in' => 'Invalid action. Must be run_check, update_status, or run_all.',
            'check_type.required_if' => 'Check type is required when running a specific check.',
            'check_type.in' => 'Invalid compliance check type.',
            'check_id.required_if' => 'Check ID is required when updating status.',
            'check_id.exists' => 'The selected compliance check does not exist.',
            'status.required_if' => 'Status is required when updating a check.',
            'status.in' => 'Invalid status value.',
            'failure_reason.required_if' => 'A failure reason is required when marking a check as failed.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
            'failure_reason.max' => 'Failure reason cannot exceed 1000 characters.',
            'risk_level.in' => 'Invalid risk level. Must be low, medium, high, or critical.',
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
            'check_type' => 'compliance check type',
            'check_id' => 'compliance check ID',
            'failure_reason' => 'failure reason',
            'risk_level' => 'risk level',
        ];
    }
}
