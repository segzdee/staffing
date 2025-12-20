<?php

namespace App\Http\Controllers;

use App\Models\AdminSettings;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

    public function contact()
    {
        return view('public.contact');
    }

    public function about()
    {
        return view('public.about');
    }

    // Business Marketing Pages
    public function businessPricing()
    {
        return view('public.business.pricing');
    }

    /**
     * Access Denied Page
     * Shown when user doesn't have permission to access a resource
     */
    public function accessDenied()
    {
        return view('errors.access-denied');
    }
}
