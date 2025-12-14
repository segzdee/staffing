<?php

namespace App\Http\Middleware;

use Closure;
use Session;

class Language
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
      // User Session Check
      if (auth()->check() && auth()->user()->language != '') {
        app()->setLocale(auth()->user()->language);
        Session::put('locale', auth()->user()->language);
      } else {
        if (Session::has('locale')) {
              app()->setLocale(session('locale'));
          } else {
              // Set default locale (simplified for OvertimeStaff)
              $defaultLocale = config('app.locale', 'en');
              Session::put('locale', $defaultLocale);
              app()->setLocale($defaultLocale);
          }
        } // User Session Check

        return $next($request);
    }
}
