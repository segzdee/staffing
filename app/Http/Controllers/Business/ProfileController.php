<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateBusinessProfileRequest;
use App\Http\Requests\Business\UploadBusinessLogoRequest;
use App\Services\BusinessProfileService;
use App\Models\BusinessProfile;
use App\Models\BusinessType;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Business Profile Controller
 * BIZ-REG-003: Handles business profile management
 */
class ProfileController extends Controller
{
    protected BusinessProfileService $profileService;

    public function __construct(BusinessProfileService $profileService)
    {
        $this->profileService = $profileService;
        $this->middleware('auth');
        $this->middleware('business');
    }

    /**
     * Get business profile
     *
     * GET /api/business/profile
     */
    public function getProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $businessProfile->load([
            'contacts',
            'addresses',
            'onboarding',
            'primaryContact',
            'billingContact',
            'registeredAddress',
            'billingAddress',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $businessProfile,
                'completion' => $this->profileService->getProfileCompletion($businessProfile),
                'field_labels' => $this->profileService->getFieldLabels(),
            ],
        ]);
    }

    /**
     * Update business profile
     *
     * PUT /api/business/profile
     */
    public function updateProfile(UpdateBusinessProfileRequest $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $result = $this->profileService->updateBusinessProfile(
            $businessProfile,
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'profile' => $result['business_profile'],
                'completion' => $result['completion'],
            ],
        ]);
    }

    /**
     * Upload company logo
     *
     * POST /api/business/profile/logo
     */
    public function uploadLogo(UploadBusinessLogoRequest $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $result = $this->profileService->uploadLogo(
            $businessProfile,
            $request->file('logo')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo',
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'logo_url' => $result['logo_url'],
            ],
        ]);
    }

    /**
     * Get profile completion status
     *
     * GET /api/business/profile/completion
     */
    public function getProfileCompletion(Request $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $completion = $this->profileService->calculateProfileCompletion($businessProfile);

        return response()->json([
            'success' => true,
            'data' => [
                'percentage' => $completion['percentage'],
                'completed_fields' => $completion['completed_fields'],
                'missing_fields' => $completion['missing_fields'],
                'meets_minimum' => $completion['meets_minimum'],
                'can_activate' => $businessProfile->canBeActivated(),
                'field_labels' => $this->profileService->getFieldLabels(),
            ],
        ]);
    }

    /**
     * Show profile setup page
     */
    public function showSetup(Request $request)
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return redirect()->route('business.register');
        }

        $businessProfile->load([
            'contacts',
            'addresses',
            'onboarding',
        ]);

        $completion = $this->profileService->getProfileCompletion($businessProfile);

        return view('business.profile.setup', [
            'profile' => $businessProfile,
            'completion' => $completion,
            'business_types' => BusinessType::forSelect(),
            'industries' => Industry::forSelect(),
            'company_sizes' => $this->getCompanySizes(),
            'field_labels' => $this->profileService->getFieldLabels(),
        ]);
    }

    /**
     * Get business types list
     */
    public function getBusinessTypes(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BusinessType::active()->ordered()->get(['code', 'name', 'description', 'icon']),
        ]);
    }

    /**
     * Get industries list
     */
    public function getIndustries(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Industry::active()->topLevel()->ordered()->with('children')->get([
                'id', 'code', 'name', 'description', 'icon', 'parent_id'
            ]),
        ]);
    }

    /**
     * Get company size options
     */
    protected function getCompanySizes(): array
    {
        return [
            'sole_proprietor' => '1 person (Sole Proprietor)',
            'micro' => '2-9 employees (Micro)',
            'small' => '10-49 employees (Small)',
            'medium' => '50-249 employees (Medium)',
            'large' => '250-999 employees (Large)',
            'enterprise' => '1000+ employees (Enterprise)',
        ];
    }

    /**
     * Get timezones list
     */
    public function getTimezones(Request $request): JsonResponse
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $formattedTimezones = [];

        foreach ($timezones as $timezone) {
            $tz = new \DateTimeZone($timezone);
            $offset = $tz->getOffset(new \DateTime());
            $offsetHours = $offset / 3600;
            $offsetString = ($offsetHours >= 0 ? '+' : '') . $offsetHours;

            $formattedTimezones[] = [
                'value' => $timezone,
                'label' => "(UTC{$offsetString}) " . str_replace('_', ' ', $timezone),
                'offset' => $offset,
            ];
        }

        // Sort by offset
        usort($formattedTimezones, fn($a, $b) => $a['offset'] <=> $b['offset']);

        return response()->json([
            'success' => true,
            'data' => $formattedTimezones,
        ]);
    }

    /**
     * Get currencies list
     */
    public function getCurrencies(Request $request): JsonResponse
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '?'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '?'],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'CA$'],
            ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '?'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '?'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => 'MX$'],
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '?'],
            ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R'],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => '?.?'],
            ['code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$'],
        ];

        return response()->json([
            'success' => true,
            'data' => $currencies,
        ]);
    }

    /**
     * Activate business account (after meeting requirements)
     */
    public function activate(Request $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        if (!$businessProfile->canBeActivated()) {
            $completion = $this->profileService->getProfileCompletion($businessProfile);

            return response()->json([
                'success' => false,
                'message' => 'Cannot activate account. Please complete the required steps.',
                'data' => [
                    'email_verified' => $businessProfile->hasVerifiedWorkEmail(),
                    'profile_completion' => $completion['percentage'],
                    'terms_accepted' => $businessProfile->onboarding?->terms_accepted ?? false,
                    'missing_fields' => $completion['missing_fields'],
                ],
            ], 422);
        }

        // Activate the business
        $businessProfile->onboarding->activate();
        $businessProfile->onboarding->markComplete();

        // Update user status
        $user->update([
            'onboarding_completed' => true,
            'onboarding_step' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business account activated successfully!',
            'data' => [
                'redirect_url' => route('dashboard'),
            ],
        ]);
    }

    /**
     * Accept terms of service
     */
    public function acceptTerms(Request $request): JsonResponse
    {
        $request->validate([
            'terms_version' => 'required|string',
            'accepted' => 'required|boolean|accepted',
        ]);

        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile || !$businessProfile->onboarding) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $businessProfile->onboarding->acceptTerms($request->terms_version);

        return response()->json([
            'success' => true,
            'message' => 'Terms accepted successfully',
        ]);
    }

    /**
     * Get profile suggestions for improvement
     *
     * GET /api/business/profile/suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        $suggestions = $this->profileService->getProfileSuggestions($businessProfile);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get profile fields configuration (required/optional)
     *
     * GET /api/business/profile/fields
     */
    public function getFields(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'required_fields' => [
                    'business_name',
                    'legal_business_name',
                    'business_category',
                    'industry',
                    'work_email_verified',
                    'business_phone',
                    'company_size',
                    'default_currency',
                    'default_timezone',
                    'primary_contact',
                    'registered_address',
                ],
                'optional_fields' => [
                    'logo_url',
                    'trading_name',
                    'website',
                    'description',
                    'ein_tax_id',
                    'business_registration_number',
                    'billing_contact',
                    'billing_address',
                    'operating_address',
                ],
                'field_labels' => $this->profileService->getFieldLabels(),
                'business_types' => $this->getCompanySizes(),
            ],
        ]);
    }

    /**
     * Validate profile data without saving
     *
     * POST /api/business/profile/validate
     */
    public function validateProfile(UpdateBusinessProfileRequest $request): JsonResponse
    {
        // If we reach here, validation passed
        return response()->json([
            'success' => true,
            'message' => 'Profile data is valid',
            'data' => [
                'validated_fields' => array_keys($request->validated()),
            ],
        ]);
    }

    /**
     * Delete logo
     *
     * DELETE /api/business/profile/logo
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Business profile not found',
            ], 404);
        }

        if (!$businessProfile->logo_url) {
            return response()->json([
                'success' => false,
                'message' => 'No logo to delete',
            ], 404);
        }

        try {
            // Delete from Cloudinary if public_id exists
            if ($businessProfile->logo_public_id) {
                \CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary::destroy($businessProfile->logo_public_id);
            }

            // Clear logo fields
            $businessProfile->update([
                'logo_url' => null,
                'logo_public_id' => null,
            ]);

            // Recalculate profile completion
            $this->profileService->calculateProfileCompletion($businessProfile);

            return response()->json([
                'success' => true,
                'message' => 'Logo deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Logo deletion failed', [
                'business_profile_id' => $businessProfile->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete logo',
            ], 500);
        }
    }

    /**
     * Get company size options with details
     *
     * GET /api/business/profile/company-sizes
     */
    public function getCompanySizesDetails(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'value' => 'sole_proprietor',
                    'label' => '1 person (Sole Proprietor)',
                    'min_employees' => 1,
                    'max_employees' => 1,
                    'description' => 'One-person operation',
                ],
                [
                    'value' => 'micro',
                    'label' => '2-9 employees (Micro)',
                    'min_employees' => 2,
                    'max_employees' => 9,
                    'description' => 'Very small team',
                ],
                [
                    'value' => 'small',
                    'label' => '10-49 employees (Small)',
                    'min_employees' => 10,
                    'max_employees' => 49,
                    'description' => 'Small business',
                ],
                [
                    'value' => 'medium',
                    'label' => '50-249 employees (Medium)',
                    'min_employees' => 50,
                    'max_employees' => 249,
                    'description' => 'Medium-sized business',
                ],
                [
                    'value' => 'large',
                    'label' => '250-999 employees (Large)',
                    'min_employees' => 250,
                    'max_employees' => 999,
                    'description' => 'Large corporation',
                ],
                [
                    'value' => 'enterprise',
                    'label' => '1000+ employees (Enterprise)',
                    'min_employees' => 1000,
                    'max_employees' => null,
                    'description' => 'Enterprise-level organization',
                ],
            ],
        ]);
    }
}
