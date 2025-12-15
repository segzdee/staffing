<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ADM-003: Middleware for checking manage_settings permission.
 *
 * This middleware ensures that only authorized admins can access
 * the platform configuration management features.
 */
class ManageSettingsMiddleware
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
            return redirect()->route('login')
                ->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        // Must be an admin
        if ($user->role !== 'admin') {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Access denied. Admin access required.',
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Access denied. Admin access required.');
        }

        // Dev accounts bypass permission checks
        if ($user->is_dev_account) {
            return $next($request);
        }

        // Check for manage_settings permission
        if (!$user->hasPermission('manage_settings')) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Access denied. You do not have permission to manage settings.',
                ], 403);
            }

            return view('admin.unauthorized');
        }

        // Log the access for audit purposes
        \Log::channel('admin')->info('Settings management access', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'action' => $request->method() . ' ' . $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
