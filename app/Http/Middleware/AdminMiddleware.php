<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
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
     * Handle an incoming request with MFA verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        // Check if user is an admin
        if ($user->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Access denied. Admin access required.');
        }

        // SECURITY: Dev account bypass only works in local environment AND from localhost IPs
        $isDevBypassAllowed = $this->isDevBypassAllowed($request, $user);

        // Check if admin has verified MFA (if enabled)
        if (isset($user->mfa_enabled) && $user->mfa_enabled) {
            $mfaVerified = session('mfa_verified_at');

            // MFA expires after 30 minutes
            if (!$mfaVerified || now()->diffInMinutes($mfaVerified) > 30) {
                session()->forget('mfa_verified_at');

                // SECURITY: Only allow dev bypass if all conditions are met
                if (!$isDevBypassAllowed) {
                    return redirect()->route('login')
                        ->with('warning', 'Please verify your identity with multi-factor authentication.');
                }

                // Log dev bypass usage for audit
                \Log::channel('admin')->warning('Dev account MFA bypass used', [
                    'admin_id' => $user->id,
                    'admin_email' => $user->email,
                    'ip' => $request->ip(),
                    'environment' => app()->environment(),
                ]);
            }
        }

        // Log admin action for audit trail
        \Log::channel('admin')->info('Admin action', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'action' => $request->method() . ' ' . $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'is_dev_account' => $user->is_dev_account ?? false,
        ]);

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
}
