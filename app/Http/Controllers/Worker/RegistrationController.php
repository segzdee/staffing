<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\RegisterRequest;
use App\Http\Requests\Worker\VerifyEmailRequest;
use App\Http\Requests\Worker\VerifyPhoneRequest;
use App\Http\Requests\Worker\ResendVerificationRequest;
use App\Services\WorkerRegistrationService;
use App\Services\VerificationService;
use App\Models\User;
use App\Models\ReferralCode;
use App\Models\AgencyInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Controller for handling worker registration and verification.
 */
class RegistrationController extends Controller
{
    protected WorkerRegistrationService $registrationService;
    protected VerificationService $verificationService;

    public function __construct(
        WorkerRegistrationService $registrationService,
        VerificationService $verificationService
    ) {
        $this->registrationService = $registrationService;
        $this->verificationService = $verificationService;
    }

    /**
     * Display the registration form.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $referralCode = $request->query('ref');
        $agencyToken = $request->query('invite');

        // Validate referral code if provided
        $referralInfo = null;
        if ($referralCode) {
            $code = ReferralCode::active()->byCode($referralCode)->first();
            if ($code && $code->isValid()) {
                $referralInfo = [
                    'code' => $referralCode,
                    'referrer_name' => $code->user->first_name ?? 'A friend',
                    'referee_reward' => $code->referee_reward_amount,
                ];
            }
        }

        // Validate agency invitation if provided
        $invitationInfo = null;
        if ($agencyToken) {
            $invitation = AgencyInvitation::byToken($agencyToken)->valid()->first();
            if ($invitation) {
                $invitation->markAsViewed();
                $invitationInfo = [
                    'token' => $agencyToken,
                    'agency_name' => $invitation->agency->businessProfile->company_name ?? $invitation->agency->name,
                    'invitee_name' => $invitation->name,
                    'invitee_email' => $invitation->email,
                    'invitee_phone' => $invitation->phone,
                    'message' => $invitation->personal_message,
                ];
            }
        }

        return view('worker.auth.register', [
            'referralInfo' => $referralInfo,
            'invitationInfo' => $invitationInfo,
        ]);
    }

    /**
     * Handle registration request.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Validate signup data (additional business logic validation)
            $data = $this->registrationService->validateSignupData($request->validated());

            // Add request metadata
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();

            // Create worker account
            $user = $this->registrationService->createWorkerAccount($data);

            // Log the user in
            Auth::login($user);

            Log::info('Worker registration successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'registration_method' => $user->registration_method,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please verify your email.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified' => $user->email_verified_at !== null,
                        'phone_verified' => $user->phone_verified_at !== null,
                    ],
                    'requires_email_verification' => $user->email && !$user->email_verified_at,
                    'requires_phone_verification' => $user->phone && !$user->phone_verified_at,
                    'redirect_url' => route('worker.verify.email'),
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Worker registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display email verification form.
     *
     * @return \Illuminate\View\View
     */
    public function showVerifyEmailForm()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->email_verified_at) {
            return redirect()->route('worker.dashboard')
                ->with('info', 'Your email is already verified.');
        }

        $verificationStatus = $this->verificationService->canResendVerification('email', $user->email);

        return view('worker.auth.verify-email', [
            'user' => $user,
            'canResend' => $verificationStatus['can_resend'],
            'secondsRemaining' => $verificationStatus['seconds_remaining'],
        ]);
    }

    /**
     * Verify email with code.
     *
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = Auth::user();
        $code = $request->input('code');

        // Allow verification by token from email link
        if ($request->has('token')) {
            $verifiedUser = $this->verificationService->verifyEmailToken($request->input('token'));
        } else {
            $verifiedUser = $this->verificationService->verifyEmailCode($user->email, $code);
        }

        if (!$verifiedUser) {
            $remainingAttempts = $this->verificationService->getRemainingAttempts($user->email, 'email');

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
                'remaining_attempts' => $remainingAttempts['remaining_attempts'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully!',
            'data' => [
                'email_verified' => true,
                'redirect_url' => $verifiedUser->phone && !$verifiedUser->phone_verified_at
                    ? route('worker.verify.phone')
                    : route('worker.onboarding'),
            ],
        ]);
    }

    /**
     * Verify email from link (GET request).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyEmailFromLink(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('login')
                ->with('error', 'Invalid verification link.');
        }

        $user = $this->verificationService->verifyEmailToken($token);

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired verification link.');
        }

        // Log in the user if not already
        if (!Auth::check()) {
            Auth::login($user);
        }

        return redirect()->route('worker.onboarding')
            ->with('success', 'Email verified successfully!');
    }

    /**
     * Display phone verification form.
     *
     * @return \Illuminate\View\View
     */
    public function showVerifyPhoneForm()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->phone) {
            return redirect()->route('worker.onboarding')
                ->with('info', 'No phone number on file.');
        }

        if ($user->phone_verified_at) {
            return redirect()->route('worker.onboarding')
                ->with('info', 'Your phone is already verified.');
        }

        $verificationStatus = $this->verificationService->canResendVerification('phone', $user->phone);

        return view('worker.auth.verify-phone', [
            'user' => $user,
            'maskedPhone' => $this->maskPhoneNumber($user->phone),
            'canResend' => $verificationStatus['can_resend'],
            'secondsRemaining' => $verificationStatus['seconds_remaining'],
        ]);
    }

    /**
     * Verify phone with code.
     *
     * @param VerifyPhoneRequest $request
     * @return JsonResponse
     */
    public function verifyPhone(VerifyPhoneRequest $request): JsonResponse
    {
        $user = Auth::user();
        $code = $request->input('code');

        $verifiedUser = $this->verificationService->verifySMSCode($user->phone, $code);

        if (!$verifiedUser) {
            $remainingAttempts = $this->verificationService->getRemainingAttempts($user->phone, 'phone');

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
                'remaining_attempts' => $remainingAttempts['remaining_attempts'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone verified successfully!',
            'data' => [
                'phone_verified' => true,
                'redirect_url' => route('worker.onboarding'),
            ],
        ]);
    }

    /**
     * Resend verification code.
     *
     * @param ResendVerificationRequest $request
     * @return JsonResponse
     */
    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = Auth::user();
        $type = $request->input('type', 'email');

        try {
            $identifier = $type === 'email' ? $user->email : $user->phone;

            if (!$identifier) {
                return response()->json([
                    'success' => false,
                    'message' => "No {$type} on file.",
                ], 400);
            }

            $canResend = $this->verificationService->canResendVerification($type, $identifier);

            if (!$canResend['can_resend']) {
                return response()->json([
                    'success' => false,
                    'message' => $canResend['message'],
                    'seconds_remaining' => $canResend['seconds_remaining'],
                ], 429);
            }

            $this->verificationService->resendVerification($type, $identifier, $user);

            return response()->json([
                'success' => true,
                'message' => "Verification code sent to your {$type}.",
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend verification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
            ], 500);
        }
    }

    /**
     * Check email availability.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkEmailAvailability(Request $request): JsonResponse
    {
        $email = $request->input('email');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'available' => false,
                'message' => 'Please enter a valid email address.',
            ]);
        }

        $exists = $this->registrationService->emailExists($email);

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This email is already registered.' : null,
        ]);
    }

    /**
     * Check phone availability.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPhoneAvailability(Request $request): JsonResponse
    {
        $phone = $request->input('phone');

        if (!$phone) {
            return response()->json([
                'available' => false,
                'message' => 'Please enter a phone number.',
            ]);
        }

        $exists = $this->registrationService->phoneExists($phone);

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This phone number is already registered.' : null,
        ]);
    }

    /**
     * Validate referral code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateReferralCode(Request $request): JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'valid' => false,
                'message' => 'Please enter a referral code.',
            ]);
        }

        $referralCode = ReferralCode::active()
            ->byCode(strtoupper($code))
            ->ofType('worker')
            ->first();

        if (!$referralCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid referral code.',
            ]);
        }

        if (!$referralCode->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => $referralCode->getInvalidReason(),
            ]);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Valid referral code!',
            'data' => [
                'referrer_name' => $referralCode->user->first_name ?? 'Your referrer',
                'referee_reward' => $referralCode->referee_reward_amount,
                'referee_reward_type' => $referralCode->referee_reward_type,
            ],
        ]);
    }

    /**
     * Get verification status.
     *
     * @return JsonResponse
     */
    public function getVerificationStatus(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $this->verificationService->getVerificationStatus($user),
        ]);
    }

    /**
     * Mask phone number for display.
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }

        return substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
    }
}
