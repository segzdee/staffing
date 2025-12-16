<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Role
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @param  string|null  $requiredRole
	 * @return mixed
	 */
	public function handle($request, Closure $next, $requiredRole = null)
	{
		// Check if user is authenticated
		if (Auth::guest()) {
			return redirect()->guest('login')
				->with(['login_required' => trans('auth.login_required')]);
		}

		$user = Auth::user();

		// If a specific role is required, check it
		if ($requiredRole) {
			// Check user_type for worker, business, agency
			if (in_array($requiredRole, ['worker', 'business', 'agency'])) {
				if ($user->user_type !== $requiredRole) {
					abort(403, 'Unauthorized. Required role: ' . $requiredRole);
				}
			}
			// Check role for admin
			elseif ($requiredRole === 'admin') {
				if ($user->role !== 'admin') {
					abort(403, 'Unauthorized. Admin access required.');
				}
			}
		}

		// Legacy permission checking (for backward compatibility)
		// Only applies if user is not admin and route is not dashboard
		if ($user->role !== 'admin' && $user->role == 'normal') {
			return redirect('/');
		}

		if ($request->route()->getName() != 'dashboard'
			&& !$user->hasPermission($request->route()->getName())
			&& $request->isMethod('get')
		) {
			abort(403);
		}

		if (isset($user->permissions) && $user->permissions == 'limited_access' && $request->isMethod('post')) {
			return redirect()->back()->withUnauthorized(trans('general.unauthorized_action'));
		}

		return $next($request);
	}

}
