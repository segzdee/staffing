<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Notifications\AccountLockedNotification;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Maximum number of login attempts allowed.
     *
     * @var int
     */
    protected $maxAttempts = 6;

    /**
     * Number of minutes to lock out after max attempts.
     *
     * @var int
     */
    protected $decayMinutes = 15;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        // Validate request
        $this->validateLogin($request);

        // Check rate limiting (IP-based throttling)
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Check if the user account is locked (database-level lockout)
        $lockedUser = $this->checkAccountLockout($request);
        if ($lockedUser) {
            return $this->sendAccountLockedResponse($request, $lockedUser);
        }

        // Attempt login
        if ($this->attemptLogin($request)) {
            $user = Auth::user();

            // Check if account is locked (double-check after auth)
            if ($user->isLocked()) {
                Auth::logout();
                return $this->sendAccountLockedResponse($request, $user);
            }

            // Check if account is active
            if ($user->status !== 'active') {
                Auth::logout();
                $this->incrementLoginAttempts($request);

                \Log::channel('security')->warning('Login attempt with inactive account', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString(),
                ]);

                return redirect()->back()
                    ->withErrors(['email' => 'Your account is not active.'])
                    ->withInput();
            }

            // Clear login attempts on successful login (both rate limiter and database)
            $this->clearLoginAttempts($request);
            $user->resetFailedLoginAttempts();

            // Regenerate session to prevent fixation
            $request->session()->regenerate();

            // Log successful login
            \Log::channel('security')->info('Successful login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

            // Redirect based on user type
            return $this->authenticated($request, $user)
                ?: redirect()->intended($this->redirectPath());
        }

        // Login failed - increment attempts and handle lockout
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Check if the user account is locked before attempting login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User|null
     */
    protected function checkAccountLockout(Request $request): ?User
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->isLocked()) {
            return $user;
        }

        return null;
    }

    /**
     * Send response when account is locked.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendAccountLockedResponse(Request $request, User $user)
    {
        $minutesRemaining = $user->lockoutMinutesRemaining();

        \Log::channel('security')->warning('Login attempt on locked account', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'locked_until' => $user->locked_until->toIso8601String(),
            'lock_reason' => $user->lock_reason,
            'minutes_remaining' => $minutesRemaining,
            'timestamp' => now()->toISOString(),
        ]);

        throw ValidationException::withMessages([
            'email' => [
                trans('auth.locked', [
                    'minutes' => $minutesRemaining,
                ])
            ],
        ])->status(423); // 423 Locked status code
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::attempt(
            $this->credentials($request),
            $request->filled('remember')
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    /**
     * Determine if the user has too many login attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return RateLimiter::tooManyAttempts(
            $this->throttleKey($request),
            $this->maxAttempts
        );
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function incrementLoginAttempts(Request $request)
    {
        RateLimiter::hit(
            $this->throttleKey($request),
            $this->decayMinutes * 60
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request)
    {
        RateLimiter::clear($this->throttleKey($request));
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        \Log::channel('security')->warning('Login rate limit exceeded', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'seconds_remaining' => $seconds,
            'timestamp' => now()->toISOString(),
        ]);

        throw ValidationException::withMessages([
            'email' => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])
            ],
        ])->status(429);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $email = $request->input('email');
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Find the user to track their failed attempts
        $user = User::where('email', $email)->first();

        // Log the failed attempt
        \Log::channel('security')->warning('Failed login attempt', [
            'email' => $email,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'rate_limiter_attempts' => RateLimiter::attempts($this->throttleKey($request)),
            'user_failed_attempts' => $user ? $user->failed_login_attempts + 1 : 'N/A (user not found)',
            'timestamp' => now()->toISOString(),
        ]);

        // If user exists, increment their failed login attempts
        if ($user) {
            $wasLocked = $user->incrementFailedLoginAttempts();

            // If the account was just locked, send notification and log
            if ($wasLocked) {
                $this->handleAccountLockout($user, $ipAddress, $userAgent);

                // Return locked response instead of generic failed response
                return $this->sendAccountLockedResponse($request, $user);
            }

            // Warn user about remaining attempts
            $remainingAttempts = $user->remainingLoginAttempts();
            if ($remainingAttempts <= 2 && $remainingAttempts > 0) {
                throw ValidationException::withMessages([
                    'email' => [
                        trans('auth.failed_with_warning', [
                            'attempts' => $remainingAttempts,
                        ])
                    ],
                ]);
            }
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * Handle account lockout - send notification and log security event.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ipAddress
     * @param  string|null  $userAgent
     * @return void
     */
    protected function handleAccountLockout(User $user, string $ipAddress, ?string $userAgent): void
    {
        // Log the lockout event
        \Log::channel('security')->critical('Account locked due to failed login attempts', [
            'user_id' => $user->id,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'failed_attempts' => $user->failed_login_attempts,
            'locked_until' => $user->locked_until->toIso8601String(),
            'lock_reason' => $user->lock_reason,
            'timestamp' => now()->toISOString(),
        ]);

        // Send notification to user
        try {
            $user->notify(new AccountLockedNotification(
                $user->locked_until,
                $user->lock_reason ?? 'Too many failed login attempts',
                User::LOCKOUT_DURATION_MINUTES,
                $ipAddress,
                $userAgent,
                false // Not an admin lock
            ));
        } catch (\Exception $e) {
            \Log::channel('security')->error('Failed to send account locked notification', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Check if user has TOTP-based 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            // Log out the user temporarily
            Auth::logout();

            // Store user ID and remember preference in session for 2FA verification
            session([
                'two_factor_user_id' => $user->id,
                'two_factor_remember' => $request->filled('remember'),
            ]);

            // Log 2FA challenge
            \Log::channel('security')->info('2FA challenge initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
            ]);

            // Redirect to 2FA verification page
            return redirect()->route('two-factor.verify');
        }

        // Check for intended URL first
        if (session()->has('url.intended')) {
            return redirect()->intended();
        }

        // Route based on user type
        if ($user->isAdmin()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        // All other user types (worker, business, agency) use generic dashboard
        // which automatically routes to correct dashboard based on user type
        return redirect()->route('dashboard.index');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log logout event
        if ($user) {
            \Log::channel('security')->info('User logged out', [
                'user_id' => $user->id,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Get the post login redirect path.
     * Implements the redirect logic: email verify → role select → dashboard
     *
     * @return string
     */
    public function redirectPath()
    {
        if (Auth::check()) {
            return $this->resolveRedirectUrl(Auth::user());
        }

        return '/';
    }

    /**
     * Resolve the correct redirect URL based on user state
     * Implements the redirect logic: email verify → role select → dashboard
     */
    protected function resolveRedirectUrl(User $user): string
    {
        // 1. Email Verification Check
        if (!$user->email_verified_at) {
            return route('verification.notice');
        }

        // 2. Role/Onboarding Check
        if (!$user->user_type) {
            return route('onboarding.role-selection');
        }

        // 3. Admin Redirect
        if ($user->role === 'admin') {
            return route('filament.admin.pages.dashboard');
        }

        // 4. User Type Redirect
        // Map user types to their respective dashboard route names
        $routeName = match ($user->user_type) {
            'worker' => 'dashboard.worker',
            'business' => 'dashboard.company',
            'agency' => 'dashboard.agency',
            default => 'dashboard.index',
        };

        return route($routeName);
    }
}
