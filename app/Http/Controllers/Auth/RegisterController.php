<?php

namespace App\Http\Controllers\Auth;

use Mail;
use Cookie;
use Validator;
use App\Helper;
use App\Models\User;
use App\Models\Countries;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\AdminSettings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Validation\Rules\Password;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/onboarding';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminSettings $settings)
    {
        $this->middleware('guest');
        $this->settings = $settings::first();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

      $data['_captcha'] = $this->settings->captcha;

		$messages = array (
			"letters"    => trans('validation.letters'),
      'g-recaptcha-response.required_if' => trans('admin.captcha_error_required'),
      'g-recaptcha-response.captcha' => trans('admin.captcha_error'),
        );

		 Validator::extend('ascii_only', function($attribute, $value, $parameters){
    		return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		// Validate if have one letter
	Validator::extend('letters', function($attribute, $value, $parameters){
    	return preg_match('/[a-zA-Z0-9]/', $value);
	});

        return Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'user_type' => 'required|in:worker,business,agency',
            'agree_terms' => 'required|accepted',
            'g-recaptcha-response' => 'required_if:_captcha,==,on|captcha'
        ], $messages);
    }

    /**
     * Show registration form.
     * Accepts optional 'type' query parameter to pre-select user type.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $type = $request->query('type', 'worker');

        // Validate type - only allow valid user types
        if (!in_array($type, ['worker', 'business', 'agency'])) {
            $type = 'worker';
        }

        return view('auth.register', compact('type'));
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        // Simple user creation for OvertimeStaff
        // SECURITY: email_verified_at is NOT set - users must verify via email
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => bcrypt($data['password']),
            'user_type' => $data['user_type'] ?? 'worker',
            'role' => 'user',
            'status' => 'active',
            'onboarding_completed' => false,
        ]);

        // Create corresponding profile based on user type
        if ($user->user_type === 'worker') {
            \App\Models\WorkerProfile::create([
                'user_id' => $user->id,
            ]);
        } elseif ($user->user_type === 'business') {
            \App\Models\BusinessProfile::create([
                'user_id' => $user->id,
            ]);
        } elseif ($user->user_type === 'agency') {
            \App\Models\AgencyProfile::create([
                'user_id' => $user->id,
            ]);
        }

        return $user;
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        // If agency, redirect to multi-step agency registration flow
        // Agency registration requires documents, verification, and partnership tier selection
        if ($request->user_type === 'agency') {
            return redirect()->route('agency.register.index')
                ->with('info', 'Agency registration requires additional information. Please complete the multi-step registration process.');
        }

        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create the user and fire Registered event
        // The Registered event listener will send email verification notification
        event(new Registered($user = $this->create($request->all())));

        // SECURITY: Send email verification notification explicitly
        // This ensures the user must verify their email before full access
        $user->sendEmailVerificationNotification();

        // Auto-login the user
        $this->guard()->login($user);

        // Redirect to email verification notice page
        return redirect()->route('verification.notice')
            ->with('success', 'Welcome to OvertimeStaff! Please check your email to verify your account.');
    }
}
