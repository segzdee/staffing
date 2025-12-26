<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        // \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\SecurityHeaders::class, // Security headers for XSS, clickjacking, Permissions-Policy, etc.
        \App\Http\Middleware\ContentSecurityPolicy::class, // Nonce-based CSP (replaces inline unsafe-inline/unsafe-eval)
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocaleMiddleware::class, // GLO-006: Enhanced locale detection (replaces Language)
            \App\Http\Middleware\UserOnline::class,
            \App\Http\Middleware\UserCountry::class,
            \App\Http\Middleware\Referred::class,
            \App\Http\Middleware\CheckDevAccountExpiration::class, // Dev account auto-expiration check
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'language' => \App\Http\Middleware\SetLocaleMiddleware::class, // GLO-006: Enhanced locale detection
        'set-locale' => \App\Http\Middleware\SetLocaleMiddleware::class, // GLO-006: Alias for new middleware
        'private.content' => \App\Http\Middleware\PrivateContent::class,
        'live' => \App\Http\Middleware\OnlineUsersLive::class,
        'worker' => \App\Http\Middleware\WorkerMiddleware::class,
        'business' => \App\Http\Middleware\BusinessMiddleware::class,
        'agency' => \App\Http\Middleware\AgencyMiddleware::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'team.permission' => \App\Http\Middleware\CheckTeamPermission::class, // BIZ-003: Team permission checking
        'worker.activated' => \App\Http\Middleware\EnsureWorkerActivated::class, // STAFF-REG-011: Worker activation gate
        'business.activated' => \App\Http\Middleware\EnsureBusinessActivated::class, // BIZ-REG-011: Business activation gate
        'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class, // SECURITY: Webhook signature verification
        'two-factor' => \App\Http\Middleware\EnsureTwoFactorVerified::class, // SECURITY: Two-factor authentication verification (login flow)
        'require-2fa' => \App\Http\Middleware\RequireTwoFactor::class, // SECURITY: Require 2FA for financial/sensitive operations
        'white-label' => \App\Http\Middleware\WhiteLabelMiddleware::class, // AGY-006: White-label branding detection
    ];
}
