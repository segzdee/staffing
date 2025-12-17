<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AdminSettings;
use App\Models\Shift;

class HomeController extends Controller
{
    protected $settings;

    public function __construct(AdminSettings $settings)
    {
        try {
            // Check Database access
            $this->settings = $settings::first();
        } catch (\Exception $e) {
            // Database not initialized
            $this->settings = null;
        }
    }

    /**
     * OvertimeStaff Landing Page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Check Database access
            if (!$this->settings) {
                // Redirect to Installer if database not set up
                return redirect('install/script');
            }
        } catch (\Exception $e) {
            // Redirect to Installer
            return redirect('install/script');
        }

        // If user is logged in, redirect to their dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }

        // Guest users see the welcome page with shift marketplace info
        return view('welcome');
    }



    // Marketing Page Methods

    public function terms()
    {
        return view('public.terms');
    }
    public function privacy()
    {
        return view('public.privacy');
    }



    // Business Marketing Pages
    public function businessPricing()
    {
        return view('public.business.pricing');
    }

}
