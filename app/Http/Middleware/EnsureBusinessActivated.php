<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Ensure Business Activated Middleware
 * BIZ-REG-011: Business Account Activation
 *
 * Gates shift posting and other restricted actions until business is fully activated:
 * - Email verified
 * - Profile complete
 * - KYB verified
 * - Insurance uploaded and verified
 * - At least one venue created
 * - Payment method verified
 */
class EnsureBusinessActivated
{
    /**
     * Allowed IP addresses for dev account bypasses.
     * SECURITY: Only truly local IPs should be allowed.
     */
    protected array $allowedDevIps = [
        '127.0.0.1',
        '::1',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Allow if not authenticated (let auth middleware handle)
        if (!$user) {
            return $next($request);
        }

        // Only check business accounts
        if ($user->user_type !== 'business') {
            return $next($request);
        }

        // Get business profile
        $profile = $user->businessProfile;

        if (!$profile) {
            return $this->handleNotActivated(
                $request,
                'Business profile not found',
                route('business.profile.complete')
            );
        }

        // Check if business is activated
        $onboarding = $profile->onboarding;
        $isActivated = $onboarding?->is_activated ?? false;

        // SECURITY: Dev bypass only allowed in truly local environment
        if ($this->isDevBypassAllowed($request, $user)) {
            Log::warning('Dev account activation bypass used', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
            ]);
            return $next($request);
        }

        // If not activated, check requirements
        if (!$isActivated) {
            return $this->handleNotActivated(
                $request,
                'Your business account is not fully activated. Please complete all activation requirements.',
                route('business.activation.status')
            );
        }

        // Check if account is in good standing
        if (!$profile->account_in_good_standing) {
            $message = $profile->account_warning_message ?? 'Your account has been flagged for review';
            return $this->handleNotActivated($request, $message, route('business.support'));
        }

        // Check if can post shifts
        if (!$profile->can_post_shifts) {
            return $this->handleNotActivated(
                $request,
                'Shift posting is currently disabled for your account. Please contact support.',
                route('business.support')
            );
        }

        // Additional runtime checks (payment method still valid, etc.)
        if (!$this->quickActivationCheck($profile)) {
            return $this->handleNotActivated(
                $request,
                'One or more activation requirements are no longer met. Please review your account.',
                route('business.activation.status')
            );
        }

        return $next($request);
    }

    /**
     * Check if dev account bypass is allowed.
     *
     * SECURITY: Dev bypasses are ONLY allowed when ALL conditions are met:
     * 1. User has is_dev_account flag set
     * 2. Application is in local environment
     * 3. Request comes from localhost (127.0.0.1 or ::1)
     * 4. APP_DEBUG is true
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return bool
     */
    protected function isDevBypassAllowed(Request $request, $user): bool
    {
        // Must be flagged as dev account
        if (!($user->is_dev_account ?? false)) {
            return false;
        }

        // Must be in local environment
        if (!app()->environment('local')) {
            return false;
        }

        // Must have debug mode enabled
        if (!config('app.debug')) {
            return false;
        }

        // Must be from localhost
        if (!in_array($request->ip(), $this->allowedDevIps)) {
            return false;
        }

        return true;
    }

    /**
     * Quick activation check without full requirement verification.
     * For performance, we only check critical fields that might change.
     */
    protected function quickActivationCheck($profile): bool
    {
        // Check user email verified
        if (!$profile->user->email_verified_at) {
            return false;
        }

        // Check payment method exists
        if (!$profile->payment_setup_complete) {
            return false;
        }

        // Check at least one active venue
        if ($profile->active_venues < 1) {
            return false;
        }

        return true;
    }

    /**
     * Handle response when business is not activated.
     */
    protected function handleNotActivated(Request $request, string $message, string $redirectUrl)
    {
        // Log the activation block
        Log::info('Business activation gate blocked request', [
            'user_id' => $request->user()?->id,
            'route' => $request->route()?->getName(),
            'message' => $message,
        ]);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => 'business_not_activated',
                'redirect_url' => $redirectUrl,
            ], 403);
        }

        // Redirect for web requests
        return redirect($redirectUrl)->with('error', $message);
    }
}
