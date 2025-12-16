<?php

namespace App\Http\Requests\Business;

use App\Models\TeamPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * BIZ-REG-008: Team Invitation Request
 */
class TeamInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->user_type === 'business';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => [
                'required',
                'string',
                Rule::in(array_keys(TeamPermission::getInvitableRoles())),
            ],
            'venue_access' => ['nullable', 'array'],
            'venue_access.*' => ['integer', 'exists:venues,id'],
            'message' => ['nullable', 'string', 'max:500'],
            'custom_permissions' => ['nullable', 'array'],
            'custom_permissions.can_post_shifts' => ['nullable', 'boolean'],
            'custom_permissions.can_edit_shifts' => ['nullable', 'boolean'],
            'custom_permissions.can_cancel_shifts' => ['nullable', 'boolean'],
            'custom_permissions.can_approve_applications' => ['nullable', 'boolean'],
            'custom_permissions.can_manage_workers' => ['nullable', 'boolean'],
            'custom_permissions.can_view_payments' => ['nullable', 'boolean'],
            'custom_permissions.can_manage_venues' => ['nullable', 'boolean'],
            'custom_permissions.can_view_analytics' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please enter an email address.',
            'email.email' => 'Please enter a valid email address.',
            'role.required' => 'Please select a role for the team member.',
            'role.in' => 'Please select a valid role.',
            'venue_access.*.exists' => 'One or more selected venues are invalid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'email address',
            'role' => 'role',
            'venue_access' => 'venue access',
            'message' => 'personal message',
        ];
    }
}
