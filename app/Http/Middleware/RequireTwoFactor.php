<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireTwoFactor Middleware
 *
 * SECURITY: Enforces 2FA verification for financial and sensitive operations.
 * This middleware checks if the authenticated user has 2FA enabled and verified.
 *
 * Unlike EnsureTwoFactorVerified (which is for login flow), this middleware
 * enforces 2FA on specific routes for authenticated users.
 */
class RequireTwoFactor
{
    /**
     * Routes that should bypass 2FA requirement (e.g., 2FA setup routes).
     */
    protected array $except = [
        'two-factor.setup',
        'two-factor.verify',
        'two-factor.verify-code',
        'two-factor.recovery',
        'two-factor.recovery.verify',
        'two-factor.disable',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to 2FA setup/verification routes
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        // Must be authenticated
        if (! Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Authentication required.',
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        // Check if user has 2FA enabled
        if (! $user->hasTwoFactorEnabled()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Two-factor authentication is required for this operation. Please enable 2FA in your account settings.',
                    'error_code' => 'TWO_FACTOR_REQUIRED',
                ], 403);
            }

            return redirect()->route('two-factor.setup')
                ->with('warning', 'Two-factor authentication is required for this operation. Please enable 2FA first.');
        }

        // Check if 2FA is verified in this session
        if (! session()->get('two_factor_verified', false)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Two-factor authentication verification required.',
                    'error_code' => 'TWO_FACTOR_VERIFICATION_REQUIRED',
                ], 403);
            }

            // Store intended URL for post-verification redirect
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('two-factor.verify')
                ->with('warning', 'Please verify your two-factor authentication to continue.');
        }

        return $next($request);
    }

    /**
     * Determine if the request should bypass 2FA check.
     */
    protected function shouldBypass(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        return in_array($routeName, $this->except);
    }
}
