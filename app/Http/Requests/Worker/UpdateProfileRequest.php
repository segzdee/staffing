<?php

namespace App\Http\Requests\Worker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * STAFF-REG-003: Worker Profile Update Request
 *
 * Validates profile data including personal information,
 * contact details, emergency contacts, and work preferences.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isWorker();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Personal Information
            'first_name' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[\p{L}\s\'-]+$/u'],
            'last_name' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[\p{L}\s\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:/^[\p{L}\s\'-]+$/u'],
            'preferred_name' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['sometimes', 'required', 'date', 'before:today', 'after:' . now()->subYears(100)->toDateString()],
            'gender' => ['nullable', Rule::in(['male', 'female', 'non_binary', 'prefer_not_to_say', 'other'])],

            // Contact Information
            'phone' => ['sometimes', 'required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'required', 'string', 'size:2', 'uppercase'],
            'zip_code' => ['nullable', 'string', 'max:20'],

            // Emergency Contact
            'emergency_contact_name' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\s\'-]+$/u'],
            'emergency_contact_phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{6,14}$/', 'max:20'],

            // Professional Information
            'bio' => ['nullable', 'string', 'max:2000', 'min:10'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            'industries' => ['nullable', 'array', 'max:10'],
            'industries.*' => ['string', 'max:100'],
            'preferred_industries' => ['nullable', 'array', 'max:10'],
            'preferred_industries.*' => ['string', 'max:100'],

            // Rate Preferences
            'hourly_rate_min' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'hourly_rate_max' => ['nullable', 'numeric', 'min:0', 'max:10000', 'gte:hourly_rate_min'],

            // Transportation & Commute
            'transportation' => ['nullable', Rule::in(['car', 'bike', 'public_transit', 'walking'])],
            'max_commute_distance' => ['nullable', 'integer', 'min:1', 'max:500'],

            // External Links
            'linkedin_url' => [
                'nullable',
                'url',
                'max:255',
                'regex:/^https?:\/\/(www\.)?linkedin\.com\/in\/[a-zA-Z0-9\-]+\/?$/',
            ],
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'middle_name' => 'middle name',
            'preferred_name' => 'preferred name',
            'date_of_birth' => 'date of birth',
            'phone' => 'phone number',
            'city' => 'city',
            'state' => 'state/province',
            'country' => 'country',
            'zip_code' => 'postal code',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'bio' => 'biography',
            'years_experience' => 'years of experience',
            'industries' => 'industries',
            'preferred_industries' => 'preferred industries',
            'hourly_rate_min' => 'minimum hourly rate',
            'hourly_rate_max' => 'maximum hourly rate',
            'transportation' => 'transportation method',
            'max_commute_distance' => 'maximum commute distance',
            'linkedin_url' => 'LinkedIn profile URL',
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.regex' => 'The middle name may only contain letters, spaces, hyphens, and apostrophes.',
            'emergency_contact_name.regex' => 'The emergency contact name may only contain letters, spaces, hyphens, and apostrophes.',
            'date_of_birth.before' => 'The date of birth must be a date before today.',
            'date_of_birth.after' => 'The date of birth must be within the last 100 years.',
            'phone.regex' => 'The phone number must be a valid international format (e.g., +1234567890).',
            'emergency_contact_phone.regex' => 'The emergency contact phone must be a valid international format.',
            'country.size' => 'The country must be a valid 2-letter country code (e.g., US, GB, CA).',
            'country.uppercase' => 'The country code must be in uppercase.',
            'hourly_rate_max.gte' => 'The maximum hourly rate must be greater than or equal to the minimum rate.',
            'linkedin_url.regex' => 'The LinkedIn URL must be a valid LinkedIn profile URL (e.g., https://linkedin.com/in/username).',
            'bio.min' => 'The biography must be at least 10 characters.',
            'bio.max' => 'The biography may not be greater than 2000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        $stringFields = [
            'first_name', 'last_name', 'middle_name', 'preferred_name',
            'phone', 'address', 'city', 'state', 'country', 'zip_code',
            'emergency_contact_name', 'emergency_contact_phone',
            'bio', 'linkedin_url',
        ];

        $data = [];

        foreach ($stringFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }

        // Uppercase country code
        if ($this->has('country') && is_string($this->input('country'))) {
            $data['country'] = strtoupper(trim($this->input('country')));
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation(): void
    {
        // Additional cross-field validation
        if ($this->filled(['hourly_rate_min', 'hourly_rate_max'])) {
            if ($this->input('hourly_rate_min') > $this->input('hourly_rate_max')) {
                $this->validator->errors()->add(
                    'hourly_rate_min',
                    'The minimum hourly rate cannot exceed the maximum rate.'
                );
            }
        }

        // Validate emergency contact completeness
        $hasName = $this->filled('emergency_contact_name');
        $hasPhone = $this->filled('emergency_contact_phone');

        if ($hasName && !$hasPhone) {
            $this->validator->errors()->add(
                'emergency_contact_phone',
                'Please provide a phone number for your emergency contact.'
            );
        }

        if ($hasPhone && !$hasName) {
            $this->validator->errors()->add(
                'emergency_contact_name',
                'Please provide a name for your emergency contact.'
            );
        }
    }
}
