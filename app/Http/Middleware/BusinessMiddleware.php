<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessMiddleware
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

        // Check if user is a business
        if ($user->user_type !== 'business') {
            return redirect()->route('dashboard')->with('error', 'Access denied. Business access required.');
        }

        // Check if business profile exists
        if (!$user->businessProfile) {
            return redirect()->route('business.profile.complete')->with('warning', 'Please complete your business profile.');
        }

        // Check if business profile is complete (skip for dev accounts)
        if (!$user->is_dev_account && isset($user->businessProfile->is_complete) && !$user->businessProfile->is_complete) {
            return redirect()->route('business.profile.complete')->with('warning', 'Please complete your business profile.');
        }

        // Check if payment method is set up (skip for dev accounts)
        if (!$user->is_dev_account && isset($user->businessProfile->has_payment_method) && !$user->businessProfile->has_payment_method && !$request->routeIs('business.payment.*')) {
            return redirect()->route('business.payment.setup')->with('warning', 'Please set up a payment method to post shifts.');
        }

        return $next($request);
    }
}
