<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\EmailVerificationNotification;
use App\Notifications\SMSVerificationNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Service for handling email and SMS verification.
 */
class VerificationService
{
    /**
     * Rate limit settings.
     */
    protected const RATE_LIMIT_ATTEMPTS = 5;
    protected const RATE_LIMIT_DECAY_MINUTES = 60;

    /**
     * Send email verification.
     *
     * @param User $user
     * @param string $purpose
     * @return VerificationCode
     * @throws \Exception
     */
    public function sendEmailVerification(User $user, string $purpose = 'verification'): VerificationCode
    {
        // Check rate limit
        $this->checkRateLimit('email', $user->email);

        // Generate verification code
        $verificationCode = VerificationCode::generate(
            $user->email,
            'email',
            $user->id,
            request()->ip(),
            request()->userAgent(),
            $purpose
        );

        // Send notification
        try {
            $user->notify(new EmailVerificationNotification($verificationCode));

            Log::info('Email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'purpose' => $purpose,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send email verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $verificationCode;
    }

    /**
     * Send SMS verification.
     *
     * @param string $phone
     * @param int|null $userId
     * @param string $purpose
     * @return VerificationCode
     * @throws \Exception
     */
    public function sendSMSVerification(string $phone, ?int $userId = null, string $purpose = 'verification'): VerificationCode
    {
        // Check rate limit
        $this->checkRateLimit('phone', $phone);

        // Generate verification code
        $verificationCode = VerificationCode::generate(
            $phone,
            'phone',
            $userId,
            request()->ip(),
            request()->userAgent(),
            $purpose
        );

        // Send SMS
        try {
            $this->sendSMS($phone, $verificationCode->code);

            Log::info('SMS verification sent', [
                'user_id' => $userId,
                'phone' => substr($phone, 0, 5) . '****',
                'purpose' => $purpose,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SMS verification', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $verificationCode;
    }

    /**
     * Verify email with code.
     *
     * @param string $email
     * @param string $code
     * @return User|null
     */
    public function verifyEmailCode(string $email, string $code): ?User
    {
        $verificationCode = VerificationCode::findValidCode($email, 'email', $code);

        if (!$verificationCode) {
            Log::warning('Invalid email verification code', [
                'email' => $email,
                'code' => $code,
            ]);
            return null;
        }

        if (!$verificationCode->verify($code)) {
            return null;
        }

        // Mark user's email as verified
        $user = $verificationCode->user;
        if ($user) {
            $user->update([
                'email_verified_at' => now(),
                'status' => $user->phone_verified_at ? 'active' : $user->status,
            ]);

            // Update onboarding progress if applicable
            $this->updateOnboardingProgress($user, 'email_verified');

            Log::info('Email verified', [
                'user_id' => $user->id,
                'email' => $email,
            ]);
        }

        return $user;
    }

    /**
     * Verify email with token (for link-based verification).
     *
     * @param string $token
     * @return User|null
     */
    public function verifyEmailToken(string $token): ?User
    {
        $verificationCode = VerificationCode::findValidToken($token, 'email');

        if (!$verificationCode) {
            Log::warning('Invalid email verification token', [
                'token' => substr($token, 0, 10) . '...',
            ]);
            return null;
        }

        if (!$verificationCode->verifyToken($token)) {
            return null;
        }

        // Mark user's email as verified
        $user = $verificationCode->user;
        if ($user) {
            $user->update([
                'email_verified_at' => now(),
                'status' => $user->phone_verified_at ? 'active' : $user->status,
            ]);

            $this->updateOnboardingProgress($user, 'email_verified');

            Log::info('Email verified via token', [
                'user_id' => $user->id,
            ]);
        }

        return $user;
    }

    /**
     * Verify phone with code.
     *
     * @param string $phone
     * @param string $code
     * @return User|null
     */
    public function verifySMSCode(string $phone, string $code): ?User
    {
        $verificationCode = VerificationCode::findValidCode($phone, 'phone', $code);

        if (!$verificationCode) {
            Log::warning('Invalid SMS verification code', [
                'phone' => substr($phone, 0, 5) . '****',
                'code' => $code,
            ]);
            return null;
        }

        if (!$verificationCode->verify($code)) {
            // Increment failed attempts tracking
            $this->recordFailedAttempt('phone', $phone);
            return null;
        }

        // Mark user's phone as verified
        $user = $verificationCode->user;
        if ($user) {
            $user->update([
                'phone_verified_at' => now(),
                'status' => $user->email_verified_at ? 'active' : $user->status,
            ]);

            $this->updateOnboardingProgress($user, 'phone_verified');

            Log::info('Phone verified', [
                'user_id' => $user->id,
                'phone' => substr($phone, 0, 5) . '****',
            ]);
        }

        return $user;
    }

    /**
     * Resend verification code.
     *
     * @param string $type 'email' or 'phone'
     * @param string $identifier Email address or phone number
     * @param User|null $user
     * @return VerificationCode
     * @throws \Exception
     */
    public function resendVerification(string $type, string $identifier, ?User $user = null): VerificationCode
    {
        // Check rate limit
        $this->checkRateLimit($type, $identifier);

        if ($type === 'email' && $user) {
            return $this->sendEmailVerification($user, 'resend');
        }

        if ($type === 'phone') {
            return $this->sendSMSVerification($identifier, $user?->id, 'resend');
        }

        throw new \InvalidArgumentException("Invalid verification type: {$type}");
    }

    /**
     * Check if user can request new verification code.
     *
     * @param string $type
     * @param string $identifier
     * @return array ['can_resend' => bool, 'seconds_remaining' => int]
     */
    public function canResendVerification(string $type, string $identifier): array
    {
        $key = $this->getRateLimitKey($type, $identifier);

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return [
                'can_resend' => false,
                'seconds_remaining' => $seconds,
                'message' => "Please wait {$seconds} seconds before requesting a new code.",
            ];
        }

        return [
            'can_resend' => true,
            'seconds_remaining' => 0,
            'message' => null,
        ];
    }

    /**
     * Check rate limit and throw exception if exceeded.
     *
     * @param string $type
     * @param string $identifier
     * @throws \Exception
     */
    protected function checkRateLimit(string $type, string $identifier): void
    {
        $key = $this->getRateLimitKey($type, $identifier);

        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \Exception("Too many verification attempts. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, self::RATE_LIMIT_DECAY_MINUTES * 60);
    }

    /**
     * Get rate limit key.
     */
    protected function getRateLimitKey(string $type, string $identifier): string
    {
        return "verification:{$type}:" . md5($identifier);
    }

    /**
     * Record a failed verification attempt.
     */
    protected function recordFailedAttempt(string $type, string $identifier): void
    {
        $key = "verification_failed:{$type}:" . md5($identifier);
        RateLimiter::hit($key, 3600); // 1 hour decay
    }

    /**
     * Send SMS using configured provider.
     *
     * @param string $phone
     * @param string $code
     */
    protected function sendSMS(string $phone, string $code): void
    {
        $provider = config('services.sms.provider', 'twilio');
        $message = "Your OvertimeStaff verification code is: {$code}. This code expires in 10 minutes.";

        switch ($provider) {
            case 'twilio':
                $this->sendViaTwilio($phone, $message);
                break;
            case 'nexmo':
            case 'vonage':
                $this->sendViaVonage($phone, $message);
                break;
            case 'sns':
                $this->sendViaSNS($phone, $message);
                break;
            default:
                // Log-only mode for development
                Log::info('SMS (dev mode)', [
                    'phone' => $phone,
                    'message' => $message,
                    'code' => $code,
                ]);
        }
    }

    /**
     * Send SMS via Twilio.
     */
    protected function sendViaTwilio(string $phone, string $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            Log::warning('Twilio not configured, skipping SMS');
            return;
        }

        try {
            $twilio = new \Twilio\Rest\Client($sid, $token);
            $twilio->messages->create($phone, [
                'from' => $from,
                'body' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send SMS via Vonage (Nexmo).
     */
    protected function sendViaVonage(string $phone, string $message): void
    {
        $apiKey = config('services.vonage.key');
        $apiSecret = config('services.vonage.secret');
        $from = config('services.vonage.from');

        if (!$apiKey || !$apiSecret) {
            Log::warning('Vonage not configured, skipping SMS');
            return;
        }

        try {
            $basic = new \Vonage\Client\Credentials\Basic($apiKey, $apiSecret);
            $client = new \Vonage\Client($basic);

            $client->sms()->send(
                new \Vonage\SMS\Message\SMS($phone, $from, $message)
            );
        } catch (\Exception $e) {
            Log::error('Vonage SMS failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send SMS via AWS SNS.
     */
    protected function sendViaSNS(string $phone, string $message): void
    {
        try {
            $sns = \Aws\Sns\SnsClient::factory([
                'region' => config('services.ses.region'),
                'version' => 'latest',
            ]);

            $sns->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType' => 'String',
                        'StringValue' => 'Transactional',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AWS SNS SMS failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update onboarding progress after verification.
     */
    protected function updateOnboardingProgress(User $user, string $step): void
    {
        if (!$user->workerProfile) {
            return;
        }

        $progress = OnboardingProgress::where('user_id', $user->id)->first();

        if ($progress) {
            $progress->updateProgress(
                min(100, $progress->progress_percentage + 20),
                array_merge($progress->progress_data ?? [], [$step => now()->toISOString()])
            );
        }
    }

    /**
     * Get verification status for a user.
     */
    public function getVerificationStatus(User $user): array
    {
        return [
            'email_verified' => $user->email_verified_at !== null,
            'email_verified_at' => $user->email_verified_at,
            'phone_verified' => $user->phone_verified_at !== null,
            'phone_verified_at' => $user->phone_verified_at,
            'can_resend_email' => $user->email
                ? $this->canResendVerification('email', $user->email)
                : ['can_resend' => false, 'message' => 'No email on file'],
            'can_resend_phone' => $user->phone
                ? $this->canResendVerification('phone', $user->phone)
                : ['can_resend' => false, 'message' => 'No phone on file'],
        ];
    }

    /**
     * Check remaining attempts for a verification code.
     */
    public function getRemainingAttempts(string $identifier, string $type): array
    {
        $code = VerificationCode::forIdentifier($identifier, $type)
            ->valid()
            ->latest()
            ->first();

        if (!$code) {
            return [
                'has_code' => false,
                'remaining_attempts' => 0,
                'expires_in_minutes' => 0,
            ];
        }

        return [
            'has_code' => true,
            'remaining_attempts' => $code->getRemainingAttempts(),
            'expires_in_minutes' => $code->getMinutesUntilExpiry(),
        ];
    }

    /**
     * Clean up expired verification codes.
     */
    public function cleanupExpiredCodes(): int
    {
        return VerificationCode::cleanupExpired();
    }
}
