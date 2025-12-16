<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\UpdateProfileRequest;
use App\Http\Requests\Worker\UploadProfilePhotoRequest;
use App\Models\WorkerProfile;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Worker Profile Controller - STAFF-REG-003
 *
 * Handles worker profile creation, updates, and completion tracking.
 */
class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get the authenticated worker's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access this resource.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            // Create profile if it doesn't exist
            $profile = WorkerProfile::create([
                'user_id' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $this->formatProfileResponse($profile),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified' => $user->hasVerifiedEmail(),
                ],
                'completion' => $this->profileService->calculateProfileCompletion($profile),
                'suggestions' => $this->profileService->getProfileSuggestions($profile),
            ],
        ]);
    }

    /**
     * Update the worker's profile.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can update their profile.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            $profile = WorkerProfile::create([
                'user_id' => $user->id,
            ]);
        }

        try {
            DB::beginTransaction();

            // Validate profile data
            $validationErrors = $this->profileService->validateProfileData($request->validated());

            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validationErrors,
                ], 422);
            }

            // Update profile
            $profile = $this->profileService->validateAndUpdateProfile($profile, $request->validated());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'data' => [
                    'profile' => $this->formatProfileResponse($profile),
                    'completion' => $this->profileService->calculateProfileCompletion($profile),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }

    /**
     * Upload a profile photo.
     *
     * @param UploadProfilePhotoRequest $request
     * @return JsonResponse
     */
    public function uploadPhoto(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can upload profile photos.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            $profile = WorkerProfile::create([
                'user_id' => $user->id,
            ]);
        }

        $result = $this->profileService->uploadProfilePhoto($profile, $request->file('photo'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'photo_url' => $result['photo_url'],
                'face_detected' => $result['face_detected'],
                'verified' => $result['verified'],
                'completion' => $this->profileService->calculateProfileCompletion($profile->fresh()),
            ],
        ]);
    }

    /**
     * Get profile completion status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompletion(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access this resource.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_percentage' => 0,
                    'is_complete' => false,
                    'missing_required' => WorkerProfile::getRequiredFields(),
                    'sections' => [],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->profileService->calculateProfileCompletion($profile),
        ]);
    }

    /**
     * Get profile suggestions for improvement.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access this resource.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => [
                    [
                        'type' => 'required',
                        'field' => 'profile',
                        'message' => 'Create your profile to start receiving shift opportunities.',
                        'priority' => 'high',
                    ],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $this->profileService->getProfileSuggestions($profile),
        ]);
    }

    /**
     * Verify age for minimum working requirements.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAge(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can verify their age.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Please create your profile first.',
            ], 400);
        }

        if (!$profile->date_of_birth) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide your date of birth.',
            ], 400);
        }

        $countryCode = $request->input('country_code', $profile->country ?? 'US');
        $meetsRequirement = $profile->verifyMinimumAge($countryCode);

        $minimumAge = WorkerProfile::getMinimumWorkingAge($countryCode);

        return response()->json([
            'success' => true,
            'data' => [
                'meets_requirement' => $meetsRequirement,
                'minimum_age' => $minimumAge,
                'country_code' => $countryCode,
                'current_age' => $profile->getAge(),
                'message' => $meetsRequirement
                    ? 'You meet the minimum working age requirement.'
                    : "You must be at least $minimumAge years old to work in this jurisdiction.",
            ],
        ]);
    }

    /**
     * Geocode the worker's location.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function geocodeLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can geocode their location.',
            ], 403);
        }

        $profile = $user->workerProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Please create your profile first.',
            ], 400);
        }

        if (!$profile->city) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide your city.',
            ], 400);
        }

        $success = $this->profileService->geocodeLocation($profile);

        if ($success) {
            $profile->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Location geocoded successfully.',
                'data' => [
                    'latitude' => $profile->location_lat,
                    'longitude' => $profile->location_lng,
                    'geocoded_address' => $profile->geocoded_address,
                    'timezone' => $profile->timezone,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Could not geocode location. Please check your address.',
        ], 422);
    }

    /**
     * Get required and optional fields for profile.
     *
     * @return JsonResponse
     */
    public function getFields(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'required_fields' => WorkerProfile::getRequiredFields(),
                'optional_fields' => WorkerProfile::getOptionalFields(),
                'gender_options' => [
                    'male' => 'Male',
                    'female' => 'Female',
                    'non_binary' => 'Non-binary',
                    'prefer_not_to_say' => 'Prefer not to say',
                    'other' => 'Other',
                ],
                'transportation_options' => [
                    'car' => 'Personal Car',
                    'bike' => 'Bicycle/Motorcycle',
                    'public_transit' => 'Public Transit',
                    'walking' => 'Walking',
                ],
            ],
        ]);
    }

    /**
     * Format profile data for API response.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function formatProfileResponse(WorkerProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,

            // Personal Information
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'middle_name' => $profile->middle_name,
            'preferred_name' => $profile->preferred_name,
            'full_name' => $profile->full_name,
            'display_name' => $profile->display_name,
            'date_of_birth' => $profile->date_of_birth?->format('Y-m-d'),
            'gender' => $profile->gender,
            'bio' => $profile->bio,

            // Contact Information
            'phone' => $profile->phone,
            'address' => $profile->address,
            'city' => $profile->city,
            'state' => $profile->state,
            'country' => $profile->country,
            'zip_code' => $profile->zip_code,

            // Emergency Contact
            'emergency_contact_name' => $profile->emergency_contact_name,
            'emergency_contact_phone' => $profile->emergency_contact_phone,

            // Profile Media
            'profile_photo_url' => $profile->profile_photo_url,
            'profile_photo_verified' => $profile->profile_photo_verified,
            'linkedin_url' => $profile->linkedin_url,
            'resume_url' => $profile->resume_url,

            // Work Preferences
            'hourly_rate_min' => $profile->hourly_rate_min,
            'hourly_rate_max' => $profile->hourly_rate_max,
            'years_experience' => $profile->years_experience,
            'industries' => $profile->industries,
            'preferred_industries' => $profile->preferred_industries,
            'transportation' => $profile->transportation,
            'max_commute_distance' => $profile->max_commute_distance,

            // Location
            'location_lat' => $profile->location_lat,
            'location_lng' => $profile->location_lng,
            'location_city' => $profile->location_city,
            'location_state' => $profile->location_state,
            'location_country' => $profile->location_country,
            'timezone' => $profile->timezone,

            // Verification Status
            'identity_verified' => $profile->identity_verified,
            'identity_verified_at' => $profile->identity_verified_at?->toIso8601String(),
            'kyc_status' => $profile->kyc_status,
            'kyc_level' => $profile->kyc_level,
            'kyc_expires_at' => $profile->kyc_expires_at?->toIso8601String(),
            'age_verified' => $profile->age_verified,
            'minimum_working_age_met' => $profile->minimum_working_age_met,
            'background_check_status' => $profile->background_check_status,

            // Profile Completion
            'profile_completion_percentage' => $profile->profile_completion_percentage,
            'is_complete' => $profile->is_complete,
            'onboarding_completed' => $profile->onboarding_completed,

            // Statistics
            'rating_average' => $profile->rating_average,
            'total_shifts_completed' => $profile->total_shifts_completed,
            'reliability_score' => $profile->reliability_score,

            // Timestamps
            'created_at' => $profile->created_at?->toIso8601String(),
            'updated_at' => $profile->updated_at?->toIso8601String(),
            'profile_last_updated_at' => $profile->profile_last_updated_at?->toIso8601String(),
        ];
    }
}
