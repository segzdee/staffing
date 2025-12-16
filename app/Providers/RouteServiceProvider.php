<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     * SECURITY: Comprehensive rate limiting for authentication routes
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // Default API rate limiter
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Login rate limiter: 5 attempts per minute per email+IP
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            $key = $email . '|' . $request->ip();
            return Limit::perMinute(5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in ' . ceil($headers['Retry-After'] / 60) . ' minute(s).',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // Password reset rate limiter: 3 attempts per hour per email+IP
        RateLimiter::for('password-reset', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            $key = 'password-reset|' . $email . '|' . $request->ip();
            return Limit::perHour(3)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many password reset attempts. Please try again later.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // 2FA code verification rate limiter: 3 attempts per minute per user
        RateLimiter::for('2fa-code', function (Request $request) {
            $userId = optional($request->user())->id ?: $request->ip();
            $key = '2fa-code|' . $userId;
            return Limit::perMinute(3)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many verification attempts. Please wait before trying again.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // TOTP 2FA rate limiter: 5 attempts per 5 minutes per session/IP
        // Used for 2FA verification during login and recovery code attempts
        RateLimiter::for('2fa', function (Request $request) {
            // Use session ID if available, otherwise IP address
            $sessionKey = session('two_factor_user_id') ?? $request->ip();
            $key = '2fa|' . $sessionKey;
            return Limit::perMinutes(5, 5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return back()->withErrors([
                        'code' => 'Too many verification attempts. Please wait ' . ceil($headers['Retry-After'] / 60) . ' minute(s) before trying again.',
                    ]);
                });
        });

        // Registration rate limiter: 5 attempts per hour per IP
        RateLimiter::for('registration', function (Request $request) {
            $key = 'registration|' . $request->ip();
            return Limit::perHour(5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many registration attempts from this IP. Please try again later.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // Email/Phone verification rate limiter: 3 attempts per hour per user
        RateLimiter::for('verification', function (Request $request) {
            $userId = optional($request->user())->id ?: $request->ip();
            $key = 'verification|' . $userId;
            return Limit::perHour(3)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many verification requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // Verification code submission rate limiter: 5 attempts per 10 minutes per user
        RateLimiter::for('verification-code', function (Request $request) {
            $userId = optional($request->user())->id ?: $request->ip();
            $key = 'verification-code|' . $userId;
            return Limit::perMinutes(10, 5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many verification code attempts. Please try again in a few minutes.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });

        // Password change rate limiter: 5 attempts per hour per user
        RateLimiter::for('password-change', function (Request $request) {
            $userId = optional($request->user())->id ?: $request->ip();
            $key = 'password-change|' . $userId;
            return Limit::perHour(5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many password change attempts. Please try again later.',
                        'retry_after' => $headers['Retry-After'],
                    ], 429, $headers);
                });
        });
    }
}
