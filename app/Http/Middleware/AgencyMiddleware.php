<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgencyMiddleware
{
    /**
     * Handle an incoming request.
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

        // Check if user is an agency
        if ($user->user_type !== 'agency') {
            return redirect()->route('dashboard')->with('error', 'Access denied. Agency access required.');
        }

        // Check if agency profile exists
        if (!$user->agencyProfile) {
            return redirect()->route('agency.profile.complete')->with('warning', 'Please complete your agency profile.');
        }

        // Check if agency profile is complete (skip for dev accounts)
        if (!$user->is_dev_account && isset($user->agencyProfile->is_complete) && !$user->agencyProfile->is_complete) {
            return redirect()->route('agency.profile.complete')->with('warning', 'Please complete your agency profile.');
        }

        // Check if agency is verified (skip for dev accounts)
        if (!$user->is_dev_account && isset($user->agencyProfile->is_verified) && !$user->agencyProfile->is_verified && !$request->routeIs('agency.verification.*')) {
            return redirect()->route('agency.verification.pending')->with('info', 'Your agency verification is pending.');
        }

        return $next($request);
    }
}
