<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AdminSettings;

/**
 * Legacy User Dashboard Controller
 *
 * NOTE: This controller is deprecated for OvertimeStaff.
 * Use Worker\DashboardController or Business\DashboardController instead.
 *
 * Main dashboard routing is handled by DashboardController.php
 * which redirects based on user_type to the appropriate dashboard.
 */
class DashboardController extends Controller
{
    protected $request;
    protected $settings;

    public function __construct(Request $request)
    {
        $this->request = $request;
        try {
            $this->settings = \App\Models\AdminSettings::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Redirect to appropriate dashboard based on user type
     *
     * @return Response
     */
    public function dashboard()
    {
        return redirect()->route('dashboard');
    }

    /**
     * Account Settings
     *
     * @return Response
     */
    public function account()
    {
        $user = Auth::user();

        return view('users.account', [
            'user' => $user,
            'settings' => $this->settings
        ]);
    }

    /**
     * Privacy Settings
     *
     * @return Response
     */
    public function privacy()
    {
        $user = Auth::user();

        return view('users.privacy', [
            'user' => $user,
            'settings' => $this->settings
        ]);
    }

    /**
     * Notifications Settings
     *
     * @return Response
     */
    public function notifications()
    {
        $user = Auth::user();

        return view('users.notifications', [
            'user' => $user,
            'settings' => $this->settings
        ]);
    }
}
