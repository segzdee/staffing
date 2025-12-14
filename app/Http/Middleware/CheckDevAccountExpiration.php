<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CheckDevAccountExpiration
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user is a dev account and if it has expired.
     * If expired, logs them out and redirects to login with an error message.
     * If expiring soon (< 24 hours), shows a warning flash message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only check if user is authenticated and is a dev account
        if (!$user || !$user->is_dev_account) {
            return $next($request);
        }

        // Get expiration date
        $expiresAt = $user->dev_expires_at;

        if (!$expiresAt) {
            return $next($request);
        }

        // Ensure it's a Carbon instance
        if (!$expiresAt instanceof Carbon) {
            $expiresAt = Carbon::parse($expiresAt);
        }

        // Check if expired
        if ($expiresAt->isPast()) {
            // Log the user out
            Auth::logout();

            // Invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect to login with error message
            return redirect()->route('login')->with('error',
                'Your development account has expired. Please run "php artisan dev:create-accounts" to create a fresh account, or use the "Refresh Credentials" button on the dev credentials page.'
            );
        }

        // Check if expiring soon (less than 24 hours remaining)
        $hoursRemaining = now()->diffInHours($expiresAt, false);

        if ($hoursRemaining > 0 && $hoursRemaining < 24) {
            session()->flash('warning',
                "Your dev account expires {$expiresAt->diffForHumans()}. Visit /dev/credentials to refresh."
            );
        }

        // Check if expiring in less than 1 hour - more urgent warning
        if ($hoursRemaining > 0 && $hoursRemaining < 1) {
            session()->flash('warning',
                "URGENT: Your dev account expires in less than 1 hour! Visit /dev/credentials to refresh immediately."
            );
        }

        return $next($request);
    }
}
