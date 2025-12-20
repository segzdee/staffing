<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        // Check if user is a worker
        if ($user->user_type !== 'worker') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Worker access required.'], 403);
            }

            return redirect()->route('dashboard.index')->with('error', 'Access denied. Worker access required.');
        }

        // For API requests, skip profile completion checks to allow basic operations
        if ($request->expectsJson()) {
            return $next($request);
        }

        // Check if worker profile exists
        if (! $user->workerProfile) {
            return redirect()->route('worker.profile')->with('warning', 'Please complete your worker profile.');
        }

        // Check if worker profile is complete (skip for dev accounts)
        if (! $user->is_dev_account && isset($user->workerProfile->is_complete) && ! $user->workerProfile->is_complete) {
            return redirect()->route('worker.profile')->with('warning', 'Please complete your worker profile.');
        }

        return $next($request);
    }
}
