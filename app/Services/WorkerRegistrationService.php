<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\ReferralCode;
use App\Models\ReferralUsage;
use App\Models\AgencyInvitation;
use App\Models\AgencyWorker;
use App\Models\OnboardingProgress;
use App\Notifications\WorkerWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Service for handling worker registration logic.
 */
class WorkerRegistrationService
{
    protected VerificationService $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Create a new worker account.
     *
     * @param array $data Registration data
     * @return User The created user
     * @throws \Exception
     */
    public function createWorkerAccount(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Determine registration method
            $registrationMethod = $this->determineRegistrationMethod($data);

            // Create the user
            $user = $this->createUser($data, $registrationMethod);

            // Create worker profile
            $this->createWorkerProfile($user, $data);

            // Process referral if provided
            if (!empty($data['referral_code'])) {
                $this->processReferral($user, $data['referral_code']);
            }

            // Process agency invitation if provided
            if (!empty($data['agency_invitation_token'])) {
                $this->processAgencyInvitation($user, $data['agency_invitation_token']);
            }

            // Generate referral code for the new worker
            $this->generateWorkerReferralCode($user);

            // Send welcome notification
            $this->sendWelcomeNotification($user);

            // Send verification based on registration method
            $this->initiateVerification($user, $registrationMethod, $data);

            Log::info('Worker account created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'registration_method' => $registrationMethod,
            ]);

            return $user;
        });
    }

    /**
     * Determine the registration method from the data.
     */
    protected function determineRegistrationMethod(array $data): string
    {
        if (!empty($data['social_provider'])) {
            return $data['social_provider'];
        }

        if (!empty($data['phone']) && empty($data['email'])) {
            return 'phone';
        }

        return 'email';
    }

    /**
     * Create the user record.
     */
    protected function createUser(array $data, string $registrationMethod): User
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'phone_country_code' => $data['phone_country_code'] ?? null,
            'password' => !empty($data['password']) ? Hash::make($data['password']) : null,
            'user_type' => 'worker',
            'role' => 'user',
            'status' => 'pending', // Will be 'active' after verification
            'registration_method' => $registrationMethod,
            'registration_ip' => $data['ip_address'] ?? request()->ip(),
            'registration_user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'terms_accepted_at' => !empty($data['terms_accepted']) ? now() : null,
            'privacy_accepted_at' => !empty($data['privacy_accepted']) ? now() : null,
            'marketing_consent' => $data['marketing_consent'] ?? false,
            'referred_by_code' => $data['referral_code'] ?? null,
            'agency_invitation_token' => $data['agency_invitation_token'] ?? null,
        ];

        // For social auth, add social fields
        if (!empty($data['social_provider'])) {
            $userData['social_provider'] = $data['social_provider'];
            $userData['social_id'] = $data['social_id'];
            $userData['social_avatar'] = $data['social_avatar'] ?? null;
            $userData['oauth_provider'] = $data['social_provider'];
            $userData['oauth_uid'] = $data['social_id'];

            // Social auth users get email verified immediately if email is provided
            if (!empty($data['email'])) {
                $userData['email_verified_at'] = now();
            }

            // They don't need a password initially
            $userData['requires_password_change'] = true;
        }

        return User::create($userData);
    }

    /**
     * Create the worker profile.
     */
    protected function createWorkerProfile(User $user, array $data): WorkerProfile
    {
        return WorkerProfile::create([
            'user_id' => $user->id,
            'phone' => $data['phone'] ?? null,
            'location_city' => $data['city'] ?? null,
            'location_state' => $data['state'] ?? null,
            'location_country' => $data['country'] ?? null,
            'transportation' => $data['transportation'] ?? 'own_vehicle',
            'max_commute_distance' => $data['max_commute_distance'] ?? 25,
            'subscription_tier' => 'bronze',
            'onboarding_completed' => false,
            'onboarding_step' => 1,
            'reliability_score' => 70.0, // Default starting score
        ]);
    }

    /**
     * Process a referral code during registration.
     */
    public function processReferral(User $user, string $code): ?ReferralUsage
    {
        $referralCode = ReferralCode::active()
            ->byCode(strtoupper($code))
            ->ofType('worker')
            ->first();

        if (!$referralCode) {
            Log::warning('Invalid referral code used', [
                'user_id' => $user->id,
                'code' => $code,
            ]);
            return null;
        }

        if (!$referralCode->isValid()) {
            Log::warning('Expired or maxed out referral code used', [
                'user_id' => $user->id,
                'code' => $code,
                'reason' => $referralCode->getInvalidReason(),
            ]);
            return null;
        }

        // Record the referral
        $usage = $referralCode->recordUsage(
            $user,
            request()->ip(),
            request()->userAgent()
        );

        // Update user's referrer info
        $user->update([
            'referred_by_user_id' => $referralCode->user_id,
        ]);

        Log::info('Referral processed', [
            'user_id' => $user->id,
            'referrer_id' => $referralCode->user_id,
            'code' => $code,
        ]);

        return $usage;
    }

    /**
     * Process an agency invitation during registration.
     */
    public function processAgencyInvitation(User $user, string $token): ?AgencyInvitation
    {
        $invitation = AgencyInvitation::byToken($token)
            ->valid()
            ->first();

        if (!$invitation) {
            Log::warning('Invalid agency invitation token used', [
                'user_id' => $user->id,
                'token' => substr($token, 0, 10) . '...',
            ]);
            return null;
        }

        // Accept the invitation
        $invitation->accept($user, request()->ip(), request()->userAgent());

        // Update user's agency info
        $user->update([
            'invited_by_agency_id' => $invitation->agency_id,
        ]);

        // Add worker to agency's pool
        AgencyWorker::create([
            'agency_id' => $invitation->agency_id,
            'worker_id' => $user->id,
            'status' => 'pending_verification',
            'commission_rate' => $invitation->preset_commission_rate ?? 15.00,
            'joined_via' => 'invitation',
            'invitation_id' => $invitation->id,
        ]);

        // Apply preset skills if provided
        if (!empty($invitation->preset_skills)) {
            $this->applyPresetSkills($user, $invitation->preset_skills);
        }

        Log::info('Agency invitation processed', [
            'user_id' => $user->id,
            'agency_id' => $invitation->agency_id,
            'invitation_id' => $invitation->id,
        ]);

        return $invitation;
    }

    /**
     * Apply preset skills from agency invitation.
     */
    protected function applyPresetSkills(User $user, array $skillIds): void
    {
        foreach ($skillIds as $skillId) {
            $user->skills()->attach($skillId, [
                'proficiency_level' => 'intermediate',
                'verified' => false,
            ]);
        }
    }

    /**
     * Generate a referral code for a new worker.
     */
    protected function generateWorkerReferralCode(User $user): ReferralCode
    {
        return ReferralCode::create([
            'user_id' => $user->id,
            'type' => 'worker',
            'is_active' => true,
            'referrer_reward_amount' => config('overtimestaff.referral.referrer_reward', 25.00),
            'referrer_reward_type' => 'cash',
            'referee_reward_amount' => config('overtimestaff.referral.referee_reward', 10.00),
            'referee_reward_type' => 'cash',
            'referee_shifts_required' => config('overtimestaff.referral.shifts_required', 3),
        ]);
    }

    /**
     * Send welcome notification.
     */
    protected function sendWelcomeNotification(User $user): void
    {
        try {
            $user->notify(new WorkerWelcomeNotification($user));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Initiate verification based on registration method.
     */
    protected function initiateVerification(User $user, string $method, array $data): void
    {
        // For social auth with verified email, skip email verification
        if (in_array($method, ['google', 'apple', 'facebook']) && $user->email_verified_at) {
            return;
        }

        if ($method === 'phone' || (!empty($data['phone']) && !empty($data['verify_phone']))) {
            $this->verificationService->sendSMSVerification(
                $data['phone'],
                $user->id,
                'registration'
            );
        }

        if ($method === 'email' || !empty($data['email'])) {
            $this->verificationService->sendEmailVerification(
                $user,
                'registration'
            );
        }
    }

    /**
     * Validate signup data before account creation.
     *
     * @param array $data
     * @return array Validated and normalized data
     * @throws ValidationException
     */
    public function validateSignupData(array $data): array
    {
        $errors = [];

        // Check email uniqueness
        if (!empty($data['email'])) {
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                $errors['email'] = ['This email address is already registered.'];
            }
        }

        // Check phone uniqueness
        if (!empty($data['phone'])) {
            $normalizedPhone = $this->normalizePhoneNumber($data['phone'], $data['phone_country_code'] ?? null);
            $existingUser = User::where('phone', $normalizedPhone)->first();
            if ($existingUser) {
                $errors['phone'] = ['This phone number is already registered.'];
            }
            $data['phone'] = $normalizedPhone;
        }

        // Validate referral code if provided
        if (!empty($data['referral_code'])) {
            $referralCode = ReferralCode::active()
                ->byCode(strtoupper($data['referral_code']))
                ->ofType('worker')
                ->first();

            if (!$referralCode) {
                $errors['referral_code'] = ['Invalid referral code.'];
            } elseif (!$referralCode->isValid()) {
                $errors['referral_code'] = [$referralCode->getInvalidReason()];
            }
        }

        // Validate agency invitation if provided
        if (!empty($data['agency_invitation_token'])) {
            $invitation = AgencyInvitation::byToken($data['agency_invitation_token'])
                ->valid()
                ->first();

            if (!$invitation) {
                $errors['agency_invitation_token'] = ['Invalid or expired invitation.'];
            }
        }

        // Validate password strength if provided
        if (!empty($data['password'])) {
            $passwordErrors = $this->validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                $errors['password'] = $passwordErrors;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * Validate password meets requirements.
     */
    protected function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        return $errors;
    }

    /**
     * Normalize phone number.
     */
    protected function normalizePhoneNumber(string $phone, ?string $countryCode = null): string
    {
        // Remove all non-numeric characters except +
        $normalized = preg_replace('/[^0-9+]/', '', $phone);

        // Add country code if not present and country code is provided
        if ($countryCode && !str_starts_with($normalized, '+')) {
            $normalized = '+' . ltrim($countryCode, '+') . ltrim($normalized, '0');
        }

        return $normalized;
    }

    /**
     * Create account from social provider data.
     */
    public function createFromSocialProvider(array $socialData, ?string $referralCode = null): User
    {
        $data = [
            'name' => $socialData['name'],
            'email' => $socialData['email'],
            'social_provider' => $socialData['provider'],
            'social_id' => $socialData['id'],
            'social_avatar' => $socialData['avatar'] ?? null,
            'terms_accepted' => true,
            'privacy_accepted' => true,
        ];

        if ($referralCode) {
            $data['referral_code'] = $referralCode;
        }

        return $this->createWorkerAccount($data);
    }

    /**
     * Link social account to existing user.
     */
    public function linkSocialAccount(User $user, array $socialData): User
    {
        $user->update([
            'social_provider' => $socialData['provider'],
            'social_id' => $socialData['id'],
            'social_avatar' => $socialData['avatar'] ?? $user->social_avatar,
            'oauth_provider' => $socialData['provider'],
            'oauth_uid' => $socialData['id'],
        ]);

        // If user's email matches and wasn't verified, verify it now
        if ($user->email === $socialData['email'] && !$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return $user;
    }

    /**
     * Find user by social provider.
     */
    public function findBySocialProvider(string $provider, string $socialId): ?User
    {
        return User::where('social_provider', $provider)
            ->where('social_id', $socialId)
            ->first();
    }

    /**
     * Check if email exists.
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Check if phone exists.
     */
    public function phoneExists(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    /**
     * Get registration statistics.
     */
    public function getRegistrationStats(\DateTime $startDate = null, \DateTime $endDate = null): array
    {
        $query = User::where('user_type', 'worker');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $users = $query->get();

        return [
            'total' => $users->count(),
            'by_method' => $users->groupBy('registration_method')
                ->map->count()
                ->toArray(),
            'verified' => $users->whereNotNull('email_verified_at')->count(),
            'with_referral' => $users->whereNotNull('referred_by_code')->count(),
            'from_agency' => $users->whereNotNull('invited_by_agency_id')->count(),
            'completed_onboarding' => $users->where('onboarding_completed', true)->count(),
        ];
    }
}
