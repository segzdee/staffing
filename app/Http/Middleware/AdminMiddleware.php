<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
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

        // Check if admin has verified MFA (if enabled) - Skip for dev accounts
        if (!$user->is_dev_account && isset($user->mfa_enabled) && $user->mfa_enabled) {
            $mfaVerified = session('mfa_verified_at');
            
            // MFA expires after 30 minutes
            if (!$mfaVerified || now()->diffInMinutes($mfaVerified) > 30) {
                session()->forget('mfa_verified_at');
                // Skip MFA for dev accounts - just log it
                if (!$user->is_dev_account) {
                    return redirect()->route('login')
                        ->with('warning', 'Please verify your identity with multi-factor authentication.');
                }
            }
        }

        // Log admin action for audit trail
        \Log::channel('admin')->info('Admin action', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'action' => $request->method() . ' ' . $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
