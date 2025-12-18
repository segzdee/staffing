<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Validator;

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
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers(),
            ],
            'user_type' => 'required|in:worker,business,agency',
            'agree_terms' => 'required|accepted',
        ]);
    }

    /**
     * Show registration form.
     * Accepts optional 'type' query parameter to pre-select user type.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        $type = $request->query('type', 'worker');

        // Validate type - only allow valid user types
        if (! in_array($type, ['worker', 'business', 'agency'])) {
            $type = 'worker';
        }

        return view('auth.register', compact('type'));
    }

    /**
     * Create a new user instance after a valid registration.
     *
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
    /**
     * Handle a registration request for the application.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(\App\Http\Requests\Auth\RegisterRequest $request)
    {
        // If agency, redirect to multi-step agency registration flow
        if ($request->user_type === 'agency') {
            return redirect()->route('agency.register.index')
                ->with('info', 'Agency registration requires additional information. Please complete the multi-step registration process.');
        }

        // Data is already validated by RegisterRequest
        $data = $request->validated();

        // Create the user and fire Registered event
        event(new Registered($user = $this->create($data)));

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        // Auto-login the user
        $this->guard()->login($user);

        // Redirect to email verification notice page
        return redirect()->route('verification.notice')
            ->with('success', 'Welcome to OvertimeStaff! Please check your email to verify your account.');
    }
}
