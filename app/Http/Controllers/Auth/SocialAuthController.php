<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WorkerRegistrationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * STAFF-REG-002: Worker Account Creation & Verification System
 *
 * Controller for handling social authentication (Google, Apple, Facebook).
 */
class SocialAuthController extends Controller
{
    protected WorkerRegistrationService $registrationService;

    /**
     * Supported social providers.
     */
    protected const SUPPORTED_PROVIDERS = ['google', 'apple', 'facebook'];

    public function __construct(WorkerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Redirect to social provider for authentication.
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect(Request $request, string $provider)
    {
        if (!$this->isValidProvider($provider)) {
            return redirect()->route('login')
                ->with('error', 'Invalid authentication provider.');
        }

        // Store referral code in session if provided
        if ($request->has('ref')) {
            session(['referral_code' => $request->input('ref')]);
        }

        // Store agency invitation token if provided
        if ($request->has('invite')) {
            session(['agency_invitation_token' => $request->input('invite')]);
        }

        // Store intended action (login or register)
        session(['social_auth_action' => $request->input('action', 'login')]);

        try {
            return Socialite::driver($provider)
                ->scopes($this->getScopes($provider))
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Social auth redirect failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Unable to connect to ' . ucfirst($provider) . '. Please try again.');
        }
    }

    /**
     * Handle callback from social provider.
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request, string $provider)
    {
        if (!$this->isValidProvider($provider)) {
            return redirect()->route('login')
                ->with('error', 'Invalid authentication provider.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            return $this->handleSocialUser($provider, $socialUser);

        } catch (\Exception $e) {
            Log::error('Social auth callback failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Handle the authenticated social user.
     *
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleSocialUser(string $provider, $socialUser)
    {
        $socialData = $this->extractSocialData($provider, $socialUser);

        // Check if user exists with this social account
        $existingUser = $this->registrationService->findBySocialProvider($provider, $socialData['id']);

        if ($existingUser) {
            return $this->loginUser($existingUser);
        }

        // Check if user exists with this email
        if ($socialData['email']) {
            $existingUser = User::where('email', $socialData['email'])->first();

            if ($existingUser) {
                // Link social account to existing user
                $this->registrationService->linkSocialAccount($existingUser, $socialData);
                return $this->loginUser($existingUser);
            }
        }

        // Create new user
        return $this->createNewUser($socialData);
    }

    /**
     * Extract social data from provider response.
     *
     * @param string $provider
     * @param \Laravel\Socialite\Contracts\User $socialUser
     * @return array
     */
    protected function extractSocialData(string $provider, $socialUser): array
    {
        $data = [
            'provider' => $provider,
            'id' => $socialUser->getId(),
            'email' => $socialUser->getEmail(),
            'name' => $socialUser->getName(),
            'avatar' => $socialUser->getAvatar(),
            'token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken ?? null,
            'expires_in' => $socialUser->expiresIn ?? null,
        ];

        // Provider-specific data extraction
        switch ($provider) {
            case 'google':
                $data['email_verified'] = $socialUser->user['email_verified'] ?? true;
                $data['locale'] = $socialUser->user['locale'] ?? null;
                break;

            case 'apple':
                // Apple may not always provide name after first auth
                if (empty($data['name']) && isset($socialUser->user['name'])) {
                    $data['name'] = trim(
                        ($socialUser->user['name']['firstName'] ?? '') . ' ' .
                        ($socialUser->user['name']['lastName'] ?? '')
                    );
                }
                break;

            case 'facebook':
                $data['facebook_id'] = $socialUser->getId();
                // Get higher resolution avatar
                $data['avatar'] = "https://graph.facebook.com/{$socialUser->getId()}/picture?type=large";
                break;
        }

        // Ensure name is set
        if (empty($data['name'])) {
            $data['name'] = $this->generateNameFromEmail($data['email']);
        }

        return $data;
    }

    /**
     * Login an existing user.
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function loginUser(User $user)
    {
        Auth::login($user, true);

        // Clear session data
        session()->forget(['referral_code', 'agency_invitation_token', 'social_auth_action']);

        Log::info('User logged in via social auth', [
            'user_id' => $user->id,
            'provider' => $user->social_provider,
        ]);

        // Redirect based on user type and onboarding status
        if ($user->user_type === 'worker') {
            if (!$user->workerProfile || !$user->workerProfile->onboarding_completed) {
                return redirect()->route('worker.onboarding');
            }
            return redirect()->route('worker.dashboard');
        }

        return redirect()->route('dashboard');
    }

    /**
     * Create a new user from social data.
     *
     * @param array $socialData
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function createNewUser(array $socialData)
    {
        try {
            // Get referral code from session
            $referralCode = session('referral_code');

            // Create the user
            $user = $this->registrationService->createFromSocialProvider($socialData, $referralCode);

            // Process agency invitation if in session
            $agencyToken = session('agency_invitation_token');
            if ($agencyToken) {
                $this->registrationService->processAgencyInvitation($user, $agencyToken);
            }

            // Login the new user
            Auth::login($user, true);

            // Clear session data
            session()->forget(['referral_code', 'agency_invitation_token', 'social_auth_action']);

            Log::info('New user created via social auth', [
                'user_id' => $user->id,
                'provider' => $socialData['provider'],
            ]);

            // Redirect to onboarding
            return redirect()->route('worker.onboarding')
                ->with('success', 'Welcome to OvertimeStaff! Please complete your profile.');

        } catch (\Exception $e) {
            Log::error('Failed to create user from social auth', [
                'provider' => $socialData['provider'],
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('register')
                ->with('error', 'Unable to create account. Please try registering manually.')
                ->withInput(['email' => $socialData['email'], 'name' => $socialData['name']]);
        }
    }

    /**
     * Check if provider is valid and supported.
     *
     * @param string $provider
     * @return bool
     */
    protected function isValidProvider(string $provider): bool
    {
        return in_array(strtolower($provider), self::SUPPORTED_PROVIDERS);
    }

    /**
     * Get OAuth scopes for provider.
     *
     * @param string $provider
     * @return array
     */
    protected function getScopes(string $provider): array
    {
        switch ($provider) {
            case 'google':
                return ['openid', 'email', 'profile'];
            case 'facebook':
                return ['email', 'public_profile'];
            case 'apple':
                return ['name', 'email'];
            default:
                return [];
        }
    }

    /**
     * Generate a name from email address.
     *
     * @param string|null $email
     * @return string
     */
    protected function generateNameFromEmail(?string $email): string
    {
        if (!$email) {
            return 'User';
        }

        $localPart = explode('@', $email)[0];
        // Convert underscores and dots to spaces, capitalize words
        $name = str_replace(['.', '_', '-'], ' ', $localPart);
        return ucwords($name);
    }

    /**
     * Disconnect social account.
     *
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function disconnect(Request $request, string $provider)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->social_provider !== $provider) {
            return response()->json([
                'success' => false,
                'message' => 'This social account is not connected.',
            ], 400);
        }

        // Ensure user has a password before disconnecting
        if (!$user->password) {
            return response()->json([
                'success' => false,
                'message' => 'Please set a password before disconnecting social login.',
            ], 400);
        }

        $user->update([
            'social_provider' => null,
            'social_id' => null,
            'social_avatar' => null,
            'oauth_provider' => null,
            'oauth_uid' => null,
        ]);

        Log::info('Social account disconnected', [
            'user_id' => $user->id,
            'provider' => $provider,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider) . ' account disconnected successfully.',
        ]);
    }

    /**
     * Get connected social accounts for current user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConnectedAccounts()
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
            'data' => [
                'connected_provider' => $user->social_provider,
                'has_password' => $user->password !== null,
                'available_providers' => self::SUPPORTED_PROVIDERS,
            ],
        ]);
    }
}
