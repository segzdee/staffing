<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DevLoginController extends Controller
{
    /**
     * Constructor - Only allow in local/development environments.
     */
    public function __construct()
    {
        if (!app()->environment('local', 'development')) {
            abort(404);
        }
    }

    /**
     * Quick login as a dev user by type.
     */
    public function login($type)
    {
        $emailMap = [
            'worker' => 'dev.worker@overtimestaff.io',
            'business' => 'dev.business@overtimestaff.io',
            'agency' => 'dev.agency@overtimestaff.io',
            'agent' => 'dev.agent@overtimestaff.io',
            'admin' => 'dev.admin@overtimestaff.io',
        ];

        if (!isset($emailMap[$type])) {
            abort(404, 'Invalid dev user type');
        }

        $user = User::where('email', $emailMap[$type])
            ->where('is_dev_account', true)
            ->first();

        if (!$user) {
            return redirect()->route('dev.credentials')
                ->with('error', "Dev {$type} account not found. Please run: php artisan db:seed --class=DevCredentialsSeeder");
        }

        // Check if expired
        if ($user->dev_expires_at) {
            $expiresAt = $user->dev_expires_at instanceof Carbon 
                ? $user->dev_expires_at 
                : Carbon::parse($user->dev_expires_at);
            
            if ($expiresAt->isPast()) {
                return redirect()->route('dev.credentials')
                    ->with('error', "Dev {$type} account has expired. Please run: php artisan db:seed --class=DevCredentialsSeeder");
            }
        }

        // Login the user
        Auth::login($user, true);

        // Redirect to appropriate dashboard
        if ($type === 'agent') {
            // AI Agents have a dashboard showing API usage
            return redirect()->route('agent.dashboard')
                ->with('success', "Logged in as Dev AI Agent.");
        }

        $redirectMap = [
            'worker' => route('worker.dashboard'),
            'business' => route('business.dashboard'),
            'agency' => route('agency.dashboard'),
            'admin' => route('admin.dashboard'), // This is /panel/admin
        ];

        return redirect($redirectMap[$type] ?? route('home'))
            ->with('success', "Logged in as Dev {$type}");
    }

    /**
     * Display dev credentials page.
     * Handles both GET (display) and POST (refresh credentials).
     */
    public function showCredentials(Request $request)
    {
        // Handle POST request to refresh credentials
        if ($request->isMethod('post') && $request->input('action') === 'refresh') {
            try {
                \Artisan::call('db:seed', ['--class' => 'DevCredentialsSeeder']);
                $output = \Artisan::output();
                
                return redirect()->route('dev.credentials')
                    ->with('success', 'Dev credentials refreshed successfully! Expiration extended by 7 days.');
            } catch (\Exception $e) {
                return redirect()->route('dev.credentials')
                    ->with('error', 'Failed to refresh credentials: ' . $e->getMessage());
            }
        }
        $credentials = [
            'worker' => [
                'email' => 'dev.worker@overtimestaff.io',
                'password' => 'Dev007!',
                'name' => 'Dev Worker',
                'dashboard' => route('worker.dashboard'),
            ],
            'business' => [
                'email' => 'dev.business@overtimestaff.io',
                'password' => 'Dev007!',
                'name' => 'Dev Business',
                'dashboard' => route('business.dashboard'),
            ],
            'agency' => [
                'email' => 'dev.agency@overtimestaff.io',
                'password' => 'Dev007!',
                'name' => 'Dev Agency',
                'dashboard' => route('agency.dashboard'),
            ],
            'agent' => [
                'email' => 'dev.agent@overtimestaff.io',
                'password' => 'Dev007!',
                'name' => 'Dev AI Agent',
                'dashboard' => route('home'), // AI Agents use API, not web dashboard
                'api_info' => 'Use API endpoints at /api/agent/* with X-Agent-API-Key header',
            ],
            'admin' => [
                'email' => 'dev.admin@overtimestaff.io',
                'password' => 'Dev007!',
                'name' => 'Dev Admin',
                'dashboard' => route('admin.dashboard'),
            ],
        ];

        // Get expiration info for each account
        foreach ($credentials as $type => &$cred) {
            $user = User::where('email', $cred['email'])
                ->where('is_dev_account', true)
                ->first();

            if ($user && $user->dev_expires_at) {
                // Ensure dev_expires_at is a Carbon instance
                $expiresAt = $user->dev_expires_at instanceof Carbon 
                    ? $user->dev_expires_at 
                    : Carbon::parse($user->dev_expires_at);
                
                $cred['expires_at'] = $expiresAt;
                $cred['expires_in'] = $expiresAt->diffForHumans();
                $cred['days_remaining'] = max(0, Carbon::now()->diffInDays($expiresAt, false));
                $cred['is_expired'] = $expiresAt->isPast();
                $cred['exists'] = true;
            } else {
                $cred['exists'] = false;
                $cred['expires_at'] = null;
                $cred['expires_in'] = 'Not created';
                $cred['days_remaining'] = 0;
                $cred['is_expired'] = false;
            }
        }

        return view('dev.credentials', compact('credentials'));
    }
}

