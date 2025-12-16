<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\BusinessOnboarding;
use App\Models\BusinessReferral;
use App\Models\BusinessContact;
use App\Notifications\Business\BusinessWelcomeNotification;
use App\Notifications\Business\BusinessEmailVerificationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BusinessRegistrationService
 * BIZ-REG-002: Handles business account creation and registration
 */
class BusinessRegistrationService
{
    /**
     * List of blocked personal email domains
     */
    protected array $blockedEmailDomains = [
        // Major free email providers
        'gmail.com',
        'googlemail.com',
        'yahoo.com',
        'yahoo.co.uk',
        'yahoo.co.in',
        'yahoo.fr',
        'yahoo.de',
        'yahoo.es',
        'yahoo.it',
        'yahoo.ca',
        'yahoo.com.au',
        'yahoo.com.br',
        'ymail.com',
        'hotmail.com',
        'hotmail.co.uk',
        'hotmail.fr',
        'hotmail.de',
        'hotmail.es',
        'hotmail.it',
        'outlook.com',
        'outlook.co.uk',
        'live.com',
        'live.co.uk',
        'msn.com',
        'aol.com',
        'aim.com',
        'icloud.com',
        'me.com',
        'mac.com',
        'protonmail.com',
        'protonmail.ch',
        'proton.me',
        'pm.me',
        'zoho.com',
        'zohomail.com',
        'mail.com',
        'email.com',
        'usa.com',
        'gmx.com',
        'gmx.de',
        'gmx.net',
        'web.de',
        'yandex.com',
        'yandex.ru',
        'mail.ru',
        'inbox.com',
        'fastmail.com',
        'fastmail.fm',
        'tutanota.com',
        'tutanota.de',
        'tutamail.com',
        'hushmail.com',
        'rediffmail.com',
        'att.net',
        'sbcglobal.net',
        'verizon.net',
        'comcast.net',
        'cox.net',
        'charter.net',
        'earthlink.net',
        'bellsouth.net',
        'rocketmail.com',
        // Temporary email services
        'tempmail.com',
        'guerrillamail.com',
        'mailinator.com',
        '10minutemail.com',
        'throwaway.email',
        'trashmail.com',
        'fakeinbox.com',
        'sharklasers.com',
        'getnada.com',
        'temp-mail.org',
    ];

    /**
     * Validate if email is a work email (not personal)
     */
    public function validateBusinessEmail(string $email): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'domain' => null,
        ];

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['valid'] = false;
            $result['errors'][] = 'Invalid email format';
            return $result;
        }

        // Extract domain
        $parts = explode('@', strtolower($email));
        $domain = $parts[1] ?? null;
        $result['domain'] = $domain;

        if (!$domain) {
            $result['valid'] = false;
            $result['errors'][] = 'Unable to extract email domain';
            return $result;
        }

        // Check against blocked domains
        if (in_array($domain, $this->blockedEmailDomains)) {
            $result['valid'] = false;
            $result['errors'][] = 'Personal email addresses are not allowed. Please use your work email.';
            return $result;
        }

        // Check for common personal email patterns
        if ($this->isLikelyPersonalDomain($domain)) {
            $result['valid'] = false;
            $result['errors'][] = 'This email domain appears to be a personal email provider. Please use your work email.';
            return $result;
        }

        // Validate MX records
        if (!$this->validateMxRecords($domain)) {
            $result['valid'] = false;
            $result['errors'][] = 'Email domain does not have valid mail server records';
            return $result;
        }

        return $result;
    }

    /**
     * Check if domain is likely a personal email provider
     */
    protected function isLikelyPersonalDomain(string $domain): bool
    {
        // Check for common patterns in personal email domains
        $personalPatterns = [
            '/^mail\d*\./',
            '/^email\./',
            '/^inbox\./',
            '/free/',
            '/temp/',
            '/disposable/',
        ];

        foreach ($personalPatterns as $pattern) {
            if (preg_match($pattern, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate MX records for email domain
     */
    protected function validateMxRecords(string $domain): bool
    {
        // Check if MX records exist
        return checkdnsrr($domain, 'MX');
    }

    /**
     * Check for duplicate business by domain or email
     */
    public function checkDuplicateBusiness(string $email, ?string $domain = null): array
    {
        $result = [
            'exists' => false,
            'duplicate_type' => null,
            'message' => null,
        ];

        $domain = $domain ?: strtolower(explode('@', $email)[1] ?? '');

        // Check for exact email match
        $existingUser = User::where('email', strtolower($email))->first();
        if ($existingUser) {
            $result['exists'] = true;
            $result['duplicate_type'] = 'email';
            $result['message'] = 'An account with this email already exists';
            return $result;
        }

        // Check for existing business with same work email
        $existingBusiness = BusinessProfile::where('work_email', strtolower($email))->first();
        if ($existingBusiness) {
            $result['exists'] = true;
            $result['duplicate_type'] = 'work_email';
            $result['message'] = 'A business account with this email already exists';
            return $result;
        }

        // Check for same domain (optional warning, not blocking)
        $sameDomainBusinesses = BusinessProfile::where('work_email_domain', $domain)
            ->where('work_email_verified', true)
            ->count();

        if ($sameDomainBusinesses > 0) {
            $result['same_domain_count'] = $sameDomainBusinesses;
            $result['warning'] = "There are already {$sameDomainBusinesses} verified business(es) with this email domain";
        }

        return $result;
    }

    /**
     * Create business account with all related records
     */
    public function createBusinessAccount(array $data): array
    {
        // Validate email
        $emailValidation = $this->validateBusinessEmail($data['email']);
        if (!$emailValidation['valid']) {
            return [
                'success' => false,
                'errors' => $emailValidation['errors'],
            ];
        }

        // Check for duplicates
        $duplicateCheck = $this->checkDuplicateBusiness($data['email']);
        if ($duplicateCheck['exists']) {
            return [
                'success' => false,
                'errors' => [$duplicateCheck['message']],
            ];
        }

        try {
            return DB::transaction(function () use ($data, $emailValidation) {
                // Create user account
                $user = $this->createPrimaryAdmin($data);

                // Create business profile
                $businessProfile = $this->createBusinessProfile($user, $data, $emailValidation['domain']);

                // Initialize onboarding
                $onboarding = $this->initializeOnboarding($businessProfile, $user, $data);

                // Create primary contact
                $this->createPrimaryContact($businessProfile, $user, $data);

                // Handle referral if present
                if (!empty($data['referral_code'])) {
                    $this->processReferral($data['referral_code'], $businessProfile, $user);
                }

                // Send verification email
                $this->sendVerificationEmail($businessProfile, $user);

                // Send welcome notification
                $user->notify(new BusinessWelcomeNotification($businessProfile));

                return [
                    'success' => true,
                    'user' => $user,
                    'business_profile' => $businessProfile,
                    'onboarding' => $onboarding,
                    'message' => 'Business account created successfully. Please check your email to verify your account.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Business registration failed', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['An error occurred during registration. Please try again.'],
                'exception' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Create primary admin user
     */
    public function createPrimaryAdmin(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'user_type' => 'business',
            'role' => 'user',
            'status' => 'pending', // Will be active after email verification
            'is_verified_business' => false,
            'onboarding_step' => 'email_verification',
            'onboarding_completed' => false,
        ]);
    }

    /**
     * Create business profile
     */
    protected function createBusinessProfile(User $user, array $data, string $emailDomain): BusinessProfile
    {
        return BusinessProfile::create([
            'user_id' => $user->id,
            'primary_admin_user_id' => $user->id,
            'business_name' => $data['company_name'],
            'business_type' => 'small_business', // Default, will be updated during profile setup
            'work_email' => strtolower($data['email']),
            'work_email_domain' => $emailDomain,
            'work_email_verified' => false,
            'registration_source' => $data['registration_source'] ?? 'self_service',
            'sales_rep_name' => $data['sales_rep_name'] ?? null,
            'sales_rep_email' => $data['sales_rep_email'] ?? null,
            'referral_code_used' => $data['referral_code'] ?? null,
            'profile_completion_percentage' => 10, // Basic info only
        ]);
    }

    /**
     * Initialize onboarding tracking
     */
    public function initializeOnboarding(BusinessProfile $businessProfile, User $user, array $data): BusinessOnboarding
    {
        // Determine signup source
        $source = BusinessOnboarding::SOURCE_ORGANIC;
        if (!empty($data['referral_code'])) {
            $source = BusinessOnboarding::SOURCE_REFERRAL;
        } elseif (!empty($data['sales_rep_id'])) {
            $source = BusinessOnboarding::SOURCE_SALES_ASSISTED;
        }

        $onboarding = BusinessOnboarding::initializeForBusiness($businessProfile, $user, [
            'source' => $source,
            'referral_code' => $data['referral_code'] ?? null,
            'sales_rep_id' => $data['sales_rep_id'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'email_domain' => strtolower(explode('@', $data['email'])[1] ?? ''),
        ]);

        // Mark account creation step as complete
        $onboarding->completeStep(BusinessOnboarding::STEP_ACCOUNT_CREATED);

        return $onboarding;
    }

    /**
     * Create primary contact from registration data
     */
    protected function createPrimaryContact(BusinessProfile $businessProfile, User $user, array $data): BusinessContact
    {
        $nameParts = explode(' ', $data['name'], 2);

        return BusinessContact::create([
            'business_profile_id' => $businessProfile->id,
            'contact_type' => BusinessContact::TYPE_PRIMARY,
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? '',
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'receives_shift_notifications' => true,
            'receives_billing_notifications' => true,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Process referral code
     */
    protected function processReferral(string $code, BusinessProfile $businessProfile, User $user): void
    {
        $referral = BusinessReferral::byCode($code)->active()->first();

        if ($referral) {
            $referral->recordRegistration($businessProfile, $user);

            // Update onboarding with referrer info
            $businessProfile->onboarding?->update([
                'referred_by_business_id' => $referral->referrer_business_id,
            ]);
        }
    }

    /**
     * Send email verification
     */
    public function sendVerificationEmail(BusinessProfile $businessProfile, User $user): void
    {
        $token = $businessProfile->generateEmailVerificationToken();
        $user->notify(new BusinessEmailVerificationNotification($businessProfile, $token));
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token, string $email): array
    {
        $businessProfile = BusinessProfile::where('email_verification_token', $token)
            ->where('work_email', strtolower($email))
            ->first();

        if (!$businessProfile) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token',
            ];
        }

        // Check if token is expired (24 hours)
        if ($businessProfile->email_verification_sent_at &&
            $businessProfile->email_verification_sent_at->addHours(24)->isPast()) {
            return [
                'success' => false,
                'message' => 'Verification token has expired. Please request a new one.',
            ];
        }

        DB::transaction(function () use ($businessProfile) {
            // Verify the email
            $businessProfile->update([
                'work_email_verified' => true,
                'work_email_verified_at' => now(),
                'email_verification_token' => null,
            ]);

            // Update user status
            $businessProfile->user->update([
                'status' => 'active',
                'email_verified_at' => now(),
                'onboarding_step' => 'company_info',
            ]);

            // Update onboarding
            $businessProfile->onboarding?->completeStep(BusinessOnboarding::STEP_EMAIL_VERIFIED);
            $businessProfile->onboarding?->update(['email_verified' => true]);
        });

        return [
            'success' => true,
            'message' => 'Email verified successfully',
            'business_profile' => $businessProfile->fresh(),
        ];
    }

    /**
     * Resend verification email
     */
    public function resendVerification(string $email): array
    {
        $businessProfile = BusinessProfile::where('work_email', strtolower($email))
            ->where('work_email_verified', false)
            ->first();

        if (!$businessProfile) {
            return [
                'success' => false,
                'message' => 'Business account not found or already verified',
            ];
        }

        // Check rate limiting (max 3 per hour)
        $recentAttempts = $businessProfile->email_verification_sent_at &&
            $businessProfile->email_verification_sent_at->addHour()->isFuture();

        if ($recentAttempts) {
            $minutesRemaining = now()->diffInMinutes($businessProfile->email_verification_sent_at->addHour());
            return [
                'success' => false,
                'message' => "Please wait {$minutesRemaining} minutes before requesting another verification email",
            ];
        }

        $this->sendVerificationEmail($businessProfile, $businessProfile->user);

        return [
            'success' => true,
            'message' => 'Verification email sent successfully',
        ];
    }

    /**
     * Sales-assisted signup
     */
    public function createSalesAssistedAccount(array $data, string $salesRepId, string $salesRepName, ?string $salesRepEmail = null): array
    {
        $data['registration_source'] = 'sales_assisted';
        $data['sales_rep_id'] = $salesRepId;
        $data['sales_rep_name'] = $salesRepName;
        $data['sales_rep_email'] = $salesRepEmail;

        return $this->createBusinessAccount($data);
    }

    /**
     * Get blocked email domains list
     */
    public function getBlockedEmailDomains(): array
    {
        return $this->blockedEmailDomains;
    }

    /**
     * Add custom blocked domain
     */
    public function addBlockedDomain(string $domain): void
    {
        $domain = strtolower(trim($domain));
        if (!in_array($domain, $this->blockedEmailDomains)) {
            $this->blockedEmailDomains[] = $domain;
        }
    }

    /**
     * Check if domain is blocked
     */
    public function isDomainBlocked(string $domain): bool
    {
        return in_array(strtolower($domain), $this->blockedEmailDomains);
    }
}
