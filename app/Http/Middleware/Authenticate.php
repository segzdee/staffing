<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Models\AdminSettings;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Store intended URL in session for post-login redirect
            session()->put('url.intended', $request->fullUrl());
            session()->flash('login_required', true);
            
            // Get settings safely (may not exist)
            try {
                $settings = AdminSettings::first();
                if ($settings && $settings->home_style == 0) {
                    return route('login');
                }
            } catch (\Exception $e) {
                // Settings table may not exist, use default
            }
            
            return route('login');
        }
    }
}
