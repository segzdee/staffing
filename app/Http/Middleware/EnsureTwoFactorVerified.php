<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureTwoFactorVerified Middleware
 *
 * This middleware ensures that users who have 2FA enabled have completed
 * the 2FA verification process. It's designed to work with the login flow
 * where users are temporarily stored in session before full authentication.
 *
 * @package App\Http\Middleware
 */
class EnsureTwoFactorVerified
{
    /**
     * Routes that should be accessible during 2FA verification.
     *
     * @var array
     */
    protected array $except = [
        'two-factor.verify',
        'two-factor.verify-code',
        'two-factor.recovery',
        'two-factor.recovery.verify',
        'login',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access to 2FA verification routes
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        // Check if there's a pending 2FA verification
        if (session()->has('two_factor_user_id')) {
            // User needs to complete 2FA
            return redirect()->route('two-factor.verify')
                ->with('warning', 'Please complete two-factor authentication.');
        }

        return $next($request);
    }

    /**
     * Determine if the request should bypass 2FA check.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldBypass(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return false;
        }

        return in_array($routeName, $this->except);
    }
}
