<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\BusinessOnboarding;
use App\Models\BusinessContact;
use App\Models\BusinessAddress;
use App\Models\Industry;
use App\Notifications\Business\ProfileSetupReminderNotification;
use App\Notifications\Business\OnboardingProgressNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Intervention\Image\Facades\Image;

/**
 * BusinessProfileService
 * BIZ-REG-003: Handles business profile management
 */
class BusinessProfileService
{
    /**
     * Required fields with their weights for completion calculation
     */
    protected array $requiredFields = [
        'business_name' => 10,
        'legal_business_name' => 5,
        'business_category' => 8,
        'industry' => 8,
        'work_email_verified' => 10,
        'business_phone' => 5,
        'website' => 3,
        'description' => 5,
        'company_size' => 5,
        'default_currency' => 5,
        'default_timezone' => 5,
    ];

    /**
     * Optional fields with their weights
     */
    protected array $optionalFields = [
        'logo_url' => 5,
        'trading_name' => 3,
        'ein_tax_id' => 5,
        'business_registration_number' => 3,
    ];

    /**
     * Related data completion weights
     */
    protected array $relatedDataWeights = [
        'primary_contact' => 8,
        'billing_contact' => 3,
        'registered_address' => 8,
        'billing_address' => 3,
        'operating_address' => 5,
    ];

    /**
     * Logo requirements
     */
    protected array $logoRequirements = [
        'min_width' => 200,
        'min_height' => 200,
        'max_width' => 2000,
        'max_height' => 2000,
        'max_size_mb' => 5,
        'allowed_formats' => ['jpg', 'jpeg', 'png', 'svg'],
        'max_aspect_ratio_deviation' => 0.5, // How much it can deviate from square
    ];

    /**
     * Timezone mapping by country
     */
    protected array $countryTimezones = [
        'US' => 'America/New_York',
        'GB' => 'Europe/London',
        'CA' => 'America/Toronto',
        'AU' => 'Australia/Sydney',
        'DE' => 'Europe/Berlin',
        'FR' => 'Europe/Paris',
        'JP' => 'Asia/Tokyo',
        'IN' => 'Asia/Kolkata',
        'BR' => 'America/Sao_Paulo',
        'MX' => 'America/Mexico_City',
        'NG' => 'Africa/Lagos',
        'ZA' => 'Africa/Johannesburg',
        'AE' => 'Asia/Dubai',
        'SG' => 'Asia/Singapore',
    ];

    /**
     * Currency mapping by country
     */
    protected array $countryCurrencies = [
        'US' => 'USD',
        'GB' => 'GBP',
        'CA' => 'CAD',
        'AU' => 'AUD',
        'DE' => 'EUR',
        'FR' => 'EUR',
        'ES' => 'EUR',
        'IT' => 'EUR',
        'NL' => 'EUR',
        'JP' => 'JPY',
        'IN' => 'INR',
        'BR' => 'BRL',
        'MX' => 'MXN',
        'NG' => 'NGN',
        'ZA' => 'ZAR',
        'AE' => 'AED',
        'SG' => 'SGD',
    ];

    /**
     * Update business profile
     */
    public function updateBusinessProfile(BusinessProfile $businessProfile, array $data): array
    {
        try {
            DB::transaction(function () use ($businessProfile, $data) {
                // Update basic info
                $businessProfile->update($this->filterProfileData($data));

                // Update contacts if provided
                if (isset($data['primary_contact'])) {
                    $this->updateOrCreateContact($businessProfile, 'primary', $data['primary_contact']);
                }

                if (isset($data['billing_contact'])) {
                    $this->updateOrCreateContact($businessProfile, 'billing', $data['billing_contact']);
                }

                // Update addresses if provided
                if (isset($data['registered_address'])) {
                    $this->updateOrCreateAddress($businessProfile, 'registered', $data['registered_address']);
                }

                if (isset($data['billing_address'])) {
                    $this->updateOrCreateAddress($businessProfile, 'billing', $data['billing_address']);
                }

                if (isset($data['operating_address'])) {
                    $this->updateOrCreateAddress($businessProfile, 'operating', $data['operating_address']);
                }

                // Determine jurisdiction if country changed
                if (isset($data['country']) || isset($data['business_country'])) {
                    $country = $data['country'] ?? $data['business_country'] ?? null;
                    if ($country) {
                        $this->determineJurisdiction($businessProfile, $country);
                    }
                }

                // Recalculate profile completion
                $this->calculateProfileCompletion($businessProfile);

                // Check and update onboarding progress
                $this->updateOnboardingProgress($businessProfile);
            });

            return [
                'success' => true,
                'business_profile' => $businessProfile->fresh(['contacts', 'addresses', 'onboarding']),
                'completion' => $this->getProfileCompletion($businessProfile),
            ];
        } catch (\Exception $e) {
            Log::error('Business profile update failed', [
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'errors' => ['An error occurred while updating the profile'],
            ];
        }
    }

    /**
     * Filter data for profile update
     */
    protected function filterProfileData(array $data): array
    {
        $allowedFields = [
            'business_name',
            'legal_business_name',
            'trading_name',
            'business_category',
            'industry',
            'company_size',
            'employee_count',
            'description',
            'website',
            'phone',
            'business_phone',
            'business_address',
            'business_city',
            'business_state',
            'business_country',
            'default_currency',
            'default_timezone',
            'ein_tax_id',
            'business_registration_number',
        ];

        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Update or create contact
     */
    protected function updateOrCreateContact(BusinessProfile $businessProfile, string $type, array $data): BusinessContact
    {
        $contact = $businessProfile->contacts()
            ->where('contact_type', $type)
            ->where('is_primary', true)
            ->first();

        $contactData = array_merge($data, [
            'contact_type' => $type,
            'is_primary' => true,
            'is_active' => true,
        ]);

        if ($contact) {
            $contact->update($contactData);
            return $contact;
        }

        return $businessProfile->contacts()->create($contactData);
    }

    /**
     * Update or create address
     */
    protected function updateOrCreateAddress(BusinessProfile $businessProfile, string $type, array $data): BusinessAddress
    {
        $address = $businessProfile->addresses()
            ->where('address_type', $type)
            ->where('is_primary', true)
            ->first();

        $addressData = array_merge($data, [
            'address_type' => $type,
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Determine timezone from country if not set
        if (!isset($addressData['timezone']) && isset($addressData['country_code'])) {
            $addressData['timezone'] = $this->getDefaultTimezone($addressData['country_code']);
        }

        if ($address) {
            $address->update($addressData);
            return $address;
        }

        return $businessProfile->addresses()->create($addressData);
    }

    /**
     * Validate company logo
     */
    public function validateCompanyLogo(UploadedFile $file): array
    {
        $errors = [];

        // Check file size
        $maxSizeBytes = $this->logoRequirements['max_size_mb'] * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            $errors[] = "Logo file size must be less than {$this->logoRequirements['max_size_mb']}MB";
        }

        // Check format
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->logoRequirements['allowed_formats'])) {
            $formats = implode(', ', $this->logoRequirements['allowed_formats']);
            $errors[] = "Logo must be in one of these formats: {$formats}";
        }

        // Check dimensions (skip for SVG)
        if ($extension !== 'svg') {
            try {
                $image = Image::make($file);
                $width = $image->width();
                $height = $image->height();

                if ($width < $this->logoRequirements['min_width'] || $height < $this->logoRequirements['min_height']) {
                    $errors[] = "Logo must be at least {$this->logoRequirements['min_width']}x{$this->logoRequirements['min_height']} pixels";
                }

                if ($width > $this->logoRequirements['max_width'] || $height > $this->logoRequirements['max_height']) {
                    $errors[] = "Logo must be no larger than {$this->logoRequirements['max_width']}x{$this->logoRequirements['max_height']} pixels";
                }

                // Check aspect ratio (should be close to square)
                $aspectRatio = $width / $height;
                if (abs($aspectRatio - 1) > $this->logoRequirements['max_aspect_ratio_deviation']) {
                    $errors[] = 'Logo should be square or close to square';
                }
            } catch (\Exception $e) {
                $errors[] = 'Unable to process image. Please ensure it is a valid image file.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Upload company logo
     */
    public function uploadLogo(BusinessProfile $businessProfile, UploadedFile $file): array
    {
        // Validate first
        $validation = $this->validateCompanyLogo($file);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        try {
            // Delete old logo if exists
            if ($businessProfile->logo_public_id) {
                try {
                    Cloudinary::destroy($businessProfile->logo_public_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old logo', ['error' => $e->getMessage()]);
                }
            }

            // Upload to Cloudinary
            $result = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'business-logos',
                'public_id' => 'business_' . $businessProfile->id . '_' . time(),
                'transformation' => [
                    'width' => 400,
                    'height' => 400,
                    'crop' => 'fill',
                    'gravity' => 'face',
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                ],
            ]);

            $businessProfile->update([
                'logo_url' => $result->getSecurePath(),
                'logo_public_id' => $result->getPublicId(),
            ]);

            // Recalculate profile completion
            $this->calculateProfileCompletion($businessProfile);

            return [
                'success' => true,
                'logo_url' => $result->getSecurePath(),
                'message' => 'Logo uploaded successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Logo upload failed', [
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'errors' => ['Failed to upload logo. Please try again.'],
            ];
        }
    }

    /**
     * Determine jurisdiction based on country and state
     */
    public function determineJurisdiction(BusinessProfile $businessProfile, string $countryCode, ?string $stateCode = null): void
    {
        $countryCode = strtoupper($countryCode);

        $businessProfile->update([
            'jurisdiction_country' => $countryCode,
            'jurisdiction_state' => $stateCode,
            'default_currency' => $this->getDefaultCurrency($countryCode),
            'default_timezone' => $this->getDefaultTimezone($countryCode),
            'tax_jurisdiction' => $this->determineTaxJurisdiction($countryCode, $stateCode),
        ]);
    }

    /**
     * Get default currency for country
     */
    public function getDefaultCurrency(string $countryCode): string
    {
        return $this->countryCurrencies[strtoupper($countryCode)] ?? 'USD';
    }

    /**
     * Get default timezone for country
     */
    public function getDefaultTimezone(string $countryCode): string
    {
        return $this->countryTimezones[strtoupper($countryCode)] ?? 'UTC';
    }

    /**
     * Determine tax jurisdiction
     */
    protected function determineTaxJurisdiction(string $countryCode, ?string $stateCode): string
    {
        $countryCode = strtoupper($countryCode);
        $stateCode = $stateCode ? strtoupper($stateCode) : null;

        // For US, use state-based jurisdiction
        if ($countryCode === 'US' && $stateCode) {
            return "US-{$stateCode}";
        }

        // For CA, use province-based
        if ($countryCode === 'CA' && $stateCode) {
            return "CA-{$stateCode}";
        }

        // For EU, use country
        $euCountries = ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT', 'PT', 'IE', 'FI', 'SE', 'DK', 'PL', 'CZ', 'GR'];
        if (in_array($countryCode, $euCountries)) {
            return "EU-{$countryCode}";
        }

        return $countryCode;
    }

    /**
     * Calculate profile completion percentage
     */
    public function calculateProfileCompletion(BusinessProfile $businessProfile): array
    {
        $totalWeight = 0;
        $earnedWeight = 0;
        $completedFields = [];
        $missingFields = [];

        // Check required fields
        foreach ($this->requiredFields as $field => $weight) {
            $totalWeight += $weight;
            $value = $businessProfile->{$field};

            if ($this->fieldHasValue($value)) {
                $earnedWeight += $weight;
                $completedFields[] = $field;
            } else {
                $missingFields[] = [
                    'field' => $field,
                    'weight' => $weight,
                    'required' => true,
                ];
            }
        }

        // Check optional fields
        foreach ($this->optionalFields as $field => $weight) {
            $totalWeight += $weight;
            $value = $businessProfile->{$field};

            if ($this->fieldHasValue($value)) {
                $earnedWeight += $weight;
                $completedFields[] = $field;
            } else {
                $missingFields[] = [
                    'field' => $field,
                    'weight' => $weight,
                    'required' => false,
                ];
            }
        }

        // Check related data
        $businessProfile->loadMissing(['contacts', 'addresses']);

        // Primary contact
        $totalWeight += $this->relatedDataWeights['primary_contact'];
        if ($businessProfile->primaryContact) {
            $earnedWeight += $this->relatedDataWeights['primary_contact'];
            $completedFields[] = 'primary_contact';
        } else {
            $missingFields[] = [
                'field' => 'primary_contact',
                'weight' => $this->relatedDataWeights['primary_contact'],
                'required' => true,
            ];
        }

        // Billing contact
        $totalWeight += $this->relatedDataWeights['billing_contact'];
        if ($businessProfile->billingContact) {
            $earnedWeight += $this->relatedDataWeights['billing_contact'];
            $completedFields[] = 'billing_contact';
        } else {
            $missingFields[] = [
                'field' => 'billing_contact',
                'weight' => $this->relatedDataWeights['billing_contact'],
                'required' => false,
            ];
        }

        // Registered address
        $totalWeight += $this->relatedDataWeights['registered_address'];
        if ($businessProfile->registeredAddress) {
            $earnedWeight += $this->relatedDataWeights['registered_address'];
            $completedFields[] = 'registered_address';
        } else {
            $missingFields[] = [
                'field' => 'registered_address',
                'weight' => $this->relatedDataWeights['registered_address'],
                'required' => true,
            ];
        }

        // Billing address
        $totalWeight += $this->relatedDataWeights['billing_address'];
        if ($businessProfile->billingAddress) {
            $earnedWeight += $this->relatedDataWeights['billing_address'];
            $completedFields[] = 'billing_address';
        } else {
            $missingFields[] = [
                'field' => 'billing_address',
                'weight' => $this->relatedDataWeights['billing_address'],
                'required' => false,
            ];
        }

        // Calculate percentage
        $percentage = $totalWeight > 0 ? round(($earnedWeight / $totalWeight) * 100, 2) : 0;

        // Update business profile
        $businessProfile->update([
            'profile_completion_percentage' => $percentage,
            'profile_completion_details' => [
                'completed_fields' => $completedFields,
                'missing_fields' => $missingFields,
                'total_weight' => $totalWeight,
                'earned_weight' => $earnedWeight,
                'calculated_at' => now()->toISOString(),
            ],
        ]);

        // Update onboarding
        $businessProfile->onboarding?->updateProfileCompletionScore(
            $percentage,
            array_map(fn($f) => $f['field'], array_filter($missingFields, fn($f) => $f['required']))
        );

        return [
            'percentage' => $percentage,
            'completed_fields' => $completedFields,
            'missing_fields' => $missingFields,
            'meets_minimum' => $percentage >= 80,
        ];
    }

    /**
     * Check if field has a value
     */
    protected function fieldHasValue($value): bool
    {
        if (is_bool($value)) {
            return $value === true;
        }

        if (is_array($value)) {
            return !empty($value);
        }

        return !empty($value);
    }

    /**
     * Get profile completion summary
     */
    public function getProfileCompletion(BusinessProfile $businessProfile): array
    {
        // Calculate fresh if not available
        if (!$businessProfile->profile_completion_details) {
            return $this->calculateProfileCompletion($businessProfile);
        }

        $details = $businessProfile->profile_completion_details;

        return [
            'percentage' => $businessProfile->profile_completion_percentage,
            'completed_fields' => $details['completed_fields'] ?? [],
            'missing_fields' => $details['missing_fields'] ?? [],
            'meets_minimum' => $businessProfile->profile_completion_percentage >= 80,
            'can_activate' => $businessProfile->canBeActivated(),
        ];
    }

    /**
     * Update onboarding progress based on profile state
     */
    protected function updateOnboardingProgress(BusinessProfile $businessProfile): void
    {
        $onboarding = $businessProfile->onboarding;
        if (!$onboarding) {
            return;
        }

        // Check company info step
        if ($businessProfile->business_name && $businessProfile->business_category && $businessProfile->industry) {
            $onboarding->completeStep(BusinessOnboarding::STEP_COMPANY_INFO);
        }

        // Check contact info step
        if ($businessProfile->primaryContact) {
            $onboarding->completeStep(BusinessOnboarding::STEP_CONTACT_INFO);
        }

        // Check address info step
        if ($businessProfile->registeredAddress) {
            $onboarding->completeStep(BusinessOnboarding::STEP_ADDRESS_INFO);
        }

        // Send progress notification if significant milestone
        $completion = $businessProfile->profile_completion_percentage;
        if (in_array($completion, [25, 50, 75, 100])) {
            $businessProfile->user->notify(new OnboardingProgressNotification($businessProfile, $completion));
        }
    }

    /**
     * Get businesses needing profile setup reminders
     */
    public function getBusinessesNeedingReminders(): \Illuminate\Database\Eloquent\Collection
    {
        return BusinessProfile::with(['user', 'onboarding'])
            ->whereHas('onboarding', function ($query) {
                $query->where('status', BusinessOnboarding::STATUS_IN_PROGRESS)
                    ->where('is_activated', false)
                    ->where(function ($q) {
                        $q->whereNull('next_reminder_at')
                            ->orWhere('next_reminder_at', '<=', now());
                    })
                    ->where('reminders_sent_count', '<', 5);
            })
            ->where('profile_completion_percentage', '<', 80)
            ->get();
    }

    /**
     * Send profile setup reminder
     */
    public function sendProfileSetupReminder(BusinessProfile $businessProfile): void
    {
        $completion = $this->getProfileCompletion($businessProfile);

        $businessProfile->user->notify(new ProfileSetupReminderNotification(
            $businessProfile,
            $completion['percentage'],
            $completion['missing_fields']
        ));

        $businessProfile->onboarding?->recordReminderSent();
    }

    /**
     * Get profile field labels for UI
     */
    public function getFieldLabels(): array
    {
        return [
            'business_name' => 'Business Name',
            'legal_business_name' => 'Legal Business Name',
            'trading_name' => 'Trading Name / DBA',
            'business_category' => 'Business Type',
            'industry' => 'Industry',
            'company_size' => 'Company Size',
            'employee_count' => 'Number of Employees',
            'description' => 'Business Description',
            'website' => 'Website',
            'phone' => 'Phone Number',
            'business_phone' => 'Business Phone',
            'work_email_verified' => 'Email Verification',
            'default_currency' => 'Default Currency',
            'default_timezone' => 'Timezone',
            'logo_url' => 'Company Logo',
            'ein_tax_id' => 'Tax ID / EIN',
            'business_registration_number' => 'Business Registration Number',
            'primary_contact' => 'Primary Contact',
            'billing_contact' => 'Billing Contact',
            'registered_address' => 'Registered Address',
            'billing_address' => 'Billing Address',
            'operating_address' => 'Operating Address',
        ];
    }

    /**
     * Get profile suggestions for improvement
     */
    public function getProfileSuggestions(BusinessProfile $businessProfile): array
    {
        $suggestions = [];
        $completion = $this->getProfileCompletion($businessProfile);

        // Get missing required fields
        $missingRequired = array_filter(
            $completion['missing_fields'],
            fn($field) => $field['required']
        );

        // Get missing optional fields
        $missingOptional = array_filter(
            $completion['missing_fields'],
            fn($field) => !$field['required']
        );

        // Critical suggestions (required fields)
        foreach ($missingRequired as $field) {
            $fieldName = $field['field'];
            $label = $this->getFieldLabels()[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));

            $suggestions[] = [
                'type' => 'required',
                'field' => $fieldName,
                'label' => $label,
                'message' => "Complete your {$label} to activate your account",
                'priority' => 'high',
                'weight' => $field['weight'],
                'action' => $this->getSuggestionAction($fieldName),
            ];
        }

        // Email verification
        if (!$businessProfile->hasVerifiedWorkEmail()) {
            $suggestions[] = [
                'type' => 'verification',
                'field' => 'work_email',
                'label' => 'Email Verification',
                'message' => 'Verify your work email to activate your account',
                'priority' => 'high',
                'weight' => 10,
                'action' => [
                    'type' => 'verify_email',
                    'url' => route('business.verification.resend'),
                ],
            ];
        }

        // Logo upload
        if (!$businessProfile->logo_url) {
            $suggestions[] = [
                'type' => 'optional',
                'field' => 'logo_url',
                'label' => 'Company Logo',
                'message' => 'Add your company logo to build trust with workers',
                'priority' => 'medium',
                'weight' => 5,
                'action' => [
                    'type' => 'upload',
                    'url' => '/api/business/profile/logo',
                    'method' => 'POST',
                ],
            ];
        }

        // Business description
        if (!$businessProfile->description || strlen($businessProfile->description) < 100) {
            $suggestions[] = [
                'type' => 'optional',
                'field' => 'description',
                'label' => 'Business Description',
                'message' => 'Add a detailed description to attract quality workers',
                'priority' => 'medium',
                'weight' => 5,
                'action' => [
                    'type' => 'edit',
                    'field' => 'description',
                    'min_length' => 100,
                ],
            ];
        }

        // Payment method
        if (!$businessProfile->has_payment_method) {
            $suggestions[] = [
                'type' => 'payment',
                'field' => 'payment_method',
                'label' => 'Payment Method',
                'message' => 'Add a payment method to start posting shifts',
                'priority' => 'high',
                'weight' => 10,
                'action' => [
                    'type' => 'redirect',
                    'url' => route('business.payment.methods'),
                ],
            ];
        }

        // Terms acceptance
        if ($businessProfile->onboarding && !$businessProfile->onboarding->terms_accepted) {
            $suggestions[] = [
                'type' => 'legal',
                'field' => 'terms',
                'label' => 'Terms of Service',
                'message' => 'Accept the Terms of Service to activate your account',
                'priority' => 'high',
                'weight' => 10,
                'action' => [
                    'type' => 'accept_terms',
                    'url' => '/api/business/accept-terms',
                    'method' => 'POST',
                ],
            ];
        }

        // Optional improvements
        foreach ($missingOptional as $field) {
            $fieldName = $field['field'];
            $label = $this->getFieldLabels()[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));

            // Skip if already covered above
            if (in_array($fieldName, ['logo_url', 'description'])) {
                continue;
            }

            $suggestions[] = [
                'type' => 'optional',
                'field' => $fieldName,
                'label' => $label,
                'message' => "Add {$label} to improve your profile",
                'priority' => 'low',
                'weight' => $field['weight'],
                'action' => $this->getSuggestionAction($fieldName),
            ];
        }

        // Sort by priority and weight
        usort($suggestions, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            $priorityCompare = $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];

            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return $b['weight'] <=> $a['weight'];
        });

        return $suggestions;
    }

    /**
     * Get suggestion action for a field
     */
    protected function getSuggestionAction(string $fieldName): array
    {
        // Map fields to actions
        $actionMap = [
            'logo_url' => ['type' => 'upload', 'url' => '/api/business/profile/logo', 'method' => 'POST'],
            'primary_contact' => ['type' => 'form', 'section' => 'contact_info'],
            'billing_contact' => ['type' => 'form', 'section' => 'contact_info'],
            'registered_address' => ['type' => 'form', 'section' => 'address_info'],
            'billing_address' => ['type' => 'form', 'section' => 'address_info'],
            'operating_address' => ['type' => 'form', 'section' => 'address_info'],
        ];

        return $actionMap[$fieldName] ?? [
            'type' => 'edit',
            'field' => $fieldName,
        ];
    }

    /**
     * Get profile strength analysis
     */
    public function getProfileStrength(BusinessProfile $businessProfile): array
    {
        $completion = $this->getProfileCompletion($businessProfile);
        $percentage = $completion['percentage'];

        // Determine strength level
        if ($percentage >= 90) {
            $strength = 'excellent';
            $color = 'green';
            $message = 'Your profile is excellent! You are attracting quality workers.';
        } elseif ($percentage >= 75) {
            $strength = 'good';
            $color = 'blue';
            $message = 'Your profile is good. Complete a few more fields to maximize visibility.';
        } elseif ($percentage >= 50) {
            $strength = 'fair';
            $color = 'yellow';
            $message = 'Your profile needs improvement. Complete more fields to attract workers.';
        } elseif ($percentage >= 25) {
            $strength = 'weak';
            $color = 'orange';
            $message = 'Your profile is incomplete. Workers may not trust your business.';
        } else {
            $strength = 'poor';
            $color = 'red';
            $message = 'Your profile needs significant work. Complete essential fields first.';
        }

        return [
            'strength' => $strength,
            'percentage' => $percentage,
            'color' => $color,
            'message' => $message,
            'can_activate' => $completion['can_activate'],
            'meets_minimum' => $completion['meets_minimum'],
        ];
    }
}
