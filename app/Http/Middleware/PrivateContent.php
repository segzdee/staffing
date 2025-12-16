<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\AdminSettings;

class PrivateContent
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$settings = AdminSettings::first();

		if (Auth::guest() && $settings && $settings->who_can_see_content == 'users') {
			session()->flash('login_required', true);
			return $settings->home_style == 0 ? redirect()->route('login') : redirect()->route('home');
		}

		return $next($request);
	}

}
