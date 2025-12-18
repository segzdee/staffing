<?php

namespace App\Http\Requests\Business;

use App\Models\CommunicationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * BIZ-010: Validation for Communication Template create/update.
 */
class CommunicationTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(CommunicationTemplate::getTypes()))],
            'channel' => ['required', 'string', Rule::in(array_keys(CommunicationTemplate::getChannels()))],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a name for your template.',
            'name.max' => 'Template name cannot exceed 255 characters.',
            'type.required' => 'Please select a template type.',
            'type.in' => 'Invalid template type selected.',
            'channel.required' => 'Please select a communication channel.',
            'channel.in' => 'Invalid channel selected.',
            'subject.max' => 'Subject line cannot exceed 255 characters.',
            'body.required' => 'Please provide the template message body.',
            'body.max' => 'Template body cannot exceed 10,000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'template name',
            'type' => 'template type',
            'channel' => 'communication channel',
            'subject' => 'email subject',
            'body' => 'message body',
        ];
    }
}
