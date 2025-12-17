<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Simple role-based access middleware
 *
 * Ensures users can only access dashboards that match their user_type
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $requiredRole
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $requiredRole = null)
    {
        // If no specific role required, just check authentication
        if (!$requiredRole) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has the required role
        if ($user->user_type !== $requiredRole) {

            // Debugging for test
            if (app()->environment('testing')) {
                dump("Role Middleware Failed: UserType: {$user->user_type}, Required: {$requiredRole}");
            }

            // Redirect to access denied page
            return redirect()->route('errors.access-denied')
                ->with('intended_role', $requiredRole)
                ->with('error', 'You are not authorized to access this dashboard.')
                ->with('error_code', 'UNAUTHORIZED_ACCESS');
        }

        return $next($request);
    }
}