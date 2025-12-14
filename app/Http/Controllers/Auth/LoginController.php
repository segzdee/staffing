<?php

namespace App\Http\Controllers\Auth;

use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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
    public function __construct(Guard $auth)
    {
      $this->auth = $auth;
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

        // Check rate limiting
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        // Attempt login
        if ($this->attemptLogin($request)) {
            $user = $this->auth->user();

            // Check if account is active
            if ($user->status !== 'active') {
                $this->auth->logout();
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

            // Clear login attempts on successful login
            $this->clearLoginAttempts($request);

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

        // Login failed - increment attempts and log
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
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
        return $this->auth->attempt(
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
     * @return void
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
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
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
        // Log the failed attempt
        \Log::channel('security')->warning('Failed login attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempts' => RateLimiter::attempts($this->throttleKey($request)),
            'timestamp' => now()->toISOString(),
        ]);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
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
        // Check for intended URL first
        if (session()->has('url.intended')) {
            return redirect()->intended();
        }

        // Route based on user type
        if ($user->isWorker()) {
            return redirect()->route('worker.dashboard');
        } elseif ($user->isBusiness()) {
            return redirect()->route('business.dashboard');
        } elseif ($user->isAgency()) {
            return redirect()->route('agency.dashboard');
        } elseif ($user->isAiAgent()) {
            return redirect()->route('agent.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        // Default fallback
        return redirect()->route('dashboard');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = $this->auth->user();
        
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
        
        $this->auth->logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('home')
            ->with('success', 'You have been logged out successfully.');
    }

    // Legacy AJAX login method (keep for compatibility)
    public function loginAjax(Request $request)
    {
      if (! $request->expectsJson()) {
          abort(404);
      }

      $settings = AdminSettings::first();
      $request['_captcha'] = $settings->captcha;

      $messages = [
    'g-recaptcha-response.required_if' => trans('admin.captcha_error_required'),
    'g-recaptcha-response.captcha' => trans('admin.captcha_error'),
  ];

  	     // get our login input
      $login = $request->input('username_email');
      $urlReturn = $request->input('return');
      $isModal = $request->input('isModal');

      // check login field
      $login_type = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

      // merge our login field into the request with either email or username as key
      $request->merge([$login_type => $login]);

      // let's validate and set our credentials
      if ($login_type == 'email') {

          $validator = Validator::make($request->all(), [
              'username_email'    => 'required|email',
              'password' => 'required',
              'g-recaptcha-response' => 'required_if:_captcha,==,on|captcha'
          ], $messages);

          if ($validator->fails()) {
   		        return response()->json([
   				        'success' => false,
   				        'errors' => $validator->getMessageBag()->toArray()
   				    ]);
   		    }

          $credentials = $request->only('email', 'password');

      } else {

          $validator = Validator::make($request->all(), [
              'username_email' => 'required',
              'password' => 'required',
              'g-recaptcha-response' => 'required_if:_captcha,==,on|captcha'
          ], $messages);

          if ($validator->fails()) {
   		        return response()->json([
   				        'success' => false,
   				        'errors' => $validator->getMessageBag()->toArray(),
   				    ]);
   		    }

          $credentials = $request->only('username', 'password');

      }

    if ($this->auth->attempt($credentials, $request->has('remember'))) {

  			if ($this->auth->user()->status == 'active') {

          // Check Two step authentication
          if ($this->auth->user()->two_factor_auth == 'yes') {
            // Generate code...
            $this->generateTwofaCode($this->auth->user());

            // Logout user
            $this->auth->logout();

            return response()->json([
                'actionRequired' => true,
            ]);
          }

              if (isset($urlReturn) && url()->isValidUrl($urlReturn)) {
                return response()->json([
                    'success' => true,
                    'isLoginRegister' => true,
                    'isModal' => $isModal ? true : false,
                    'url_return' => $urlReturn
                ]);
                } else {
                  return response()->json([
                      'success' => true,
                      'isLoginRegister' => true,
                      'isModal' => $isModal ? true : false,
                      'url_return' => url('/')
                  ]);
                }

          } else if ($this->auth->user()->status == 'suspended') {

  			$this->auth->logout();

        return response()->json([
            'success' => false,
            'errors' => ['error' => trans('validation.user_suspended')],
        ]);

      } else if ($this->auth->user()->status == 'pending') {

  			$this->auth->logout();

        return response()->json([
            'success' => false,
            'errors' => ['error' => trans('validation.account_not_confirmed')],
        ]);
      }
    }

    return response()->json([
        'success' => false,
        'errors' => ['error' => trans('auth.failed')]
    ]);
  }

}
