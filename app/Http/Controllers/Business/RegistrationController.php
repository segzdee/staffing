<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\BusinessRegistrationRequest;
use App\Http\Requests\Business\BusinessEmailVerificationRequest;
use App\Http\Requests\Business\BusinessResendVerificationRequest;
use App\Services\BusinessRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Business Registration Controller
 * BIZ-REG-002: Handles business account creation and email verification
 */
class RegistrationController extends Controller
{
    protected BusinessRegistrationService $registrationService;

    public function __construct(BusinessRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Show business registration form
     */
    public function showRegistrationForm(Request $request)
    {
        // Get referral code if present
        $referralCode = $request->get('ref');

        return view('business.auth.register', [
            'referral_code' => $referralCode,
        ]);
    }

    /**
     * Register a new business account
     *
     * POST /api/business/register
     */
    public function register(BusinessRegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Add tracking parameters
        $data['utm_source'] = $request->get('utm_source');
        $data['utm_medium'] = $request->get('utm_medium');
        $data['utm_campaign'] = $request->get('utm_campaign');

        $result = $this->registrationService->createBusinessAccount($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'errors' => $result['errors'],
            ], 422);
        }

        // Log the user in
        Auth::login($result['user']);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                ],
                'business_profile' => [
                    'id' => $result['business_profile']->id,
                    'business_name' => $result['business_profile']->business_name,
                    'work_email_verified' => false,
                ],
                'redirect_url' => route('business.profile.complete'),
            ],
        ], 201);
    }

    /**
     * Verify business email
     *
     * POST /api/business/verify-email
     */
    public function verifyEmail(BusinessEmailVerificationRequest $request): JsonResponse
    {
        $result = $this->registrationService->verifyEmail(
            $request->validated('token'),
            $request->validated('email')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'business_profile' => [
                    'id' => $result['business_profile']->id,
                    'work_email_verified' => true,
                ],
                'redirect_url' => route('business.profile.complete'),
            ],
        ]);
    }

    /**
     * Verify email via GET link (from email)
     */
    public function verifyEmailLink(Request $request)
    {
        $token = $request->get('token');
        $email = $request->get('email');

        if (!$token || !$email) {
            return redirect()->route('home')
                ->with('error', 'Invalid verification link');
        }

        $result = $this->registrationService->verifyEmail($token, $email);

        if (!$result['success']) {
            return redirect()->route('login')
                ->with('error', $result['message']);
        }

        // Log in if not already
        if (!Auth::check()) {
            Auth::login($result['business_profile']->user);
        }

        return redirect()->route('business.profile.complete')
            ->with('success', 'Your email has been verified successfully!');
    }

    /**
     * Resend verification email
     *
     * POST /api/business/resend-verification
     */
    public function resendVerification(BusinessResendVerificationRequest $request): JsonResponse
    {
        $result = $this->registrationService->resendVerification(
            $request->validated('email')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Validate email domain (AJAX)
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->registrationService->validateBusinessEmail($request->email);

        if (!$result['valid']) {
            return response()->json([
                'valid' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        // Check for duplicates
        $duplicateCheck = $this->registrationService->checkDuplicateBusiness($request->email);

        if ($duplicateCheck['exists']) {
            return response()->json([
                'valid' => false,
                'errors' => [$duplicateCheck['message']],
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'domain' => $result['domain'],
            'warning' => $duplicateCheck['warning'] ?? null,
        ]);
    }

    /**
     * Check if referral code is valid
     */
    public function validateReferralCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:8',
        ]);

        $referral = \App\Models\BusinessReferral::byCode($request->code)
            ->active()
            ->with('referrerBusiness:id,business_name')
            ->first();

        if (!$referral) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired referral code',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer' => $referral->referrerBusiness->business_name ?? 'A partner business',
        ]);
    }

    /**
     * Sales-assisted registration endpoint
     * Requires sales rep authentication
     */
    public function salesAssistedRegister(Request $request): JsonResponse
    {
        // Verify sales rep authorization (would typically use middleware)
        $salesRep = Auth::user();
        if (!$salesRep || !$salesRep->hasRole('sales_rep')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $result = $this->registrationService->createSalesAssistedAccount(
            $request->only(['name', 'email', 'company_name', 'phone']),
            (string) $salesRep->id,
            $salesRep->name,
            $salesRep->email
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business account created. Verification email sent to the business.',
            'data' => [
                'business_profile_id' => $result['business_profile']->id,
                'user_id' => $result['user']->id,
            ],
        ], 201);
    }
}
