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
            return redirect()->route('dashboard');
        }

        // Guest users see the welcome page with shift marketplace info
        return view('welcome');
    }

    /**
     * About Page
     */
    public function about()
    {
        return view('pages.about');
    }

    /**
     * Contact Page
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * How It Works Page
     */
    public function howItWorks()
    {
        return view('pages.how-it-works');
    }

    /**
     * Pricing Page
     */
    public function pricing()
    {
        return view('pages.pricing');
    }

    /**
     * FAQ Page
     */
    public function faq()
    {
        return view('pages.faq');
    }

    /**
     * Handle Contact Form Submission
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'user_type' => 'required|in:worker,business,agency,other',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        // Here you would typically:
        // 1. Save to database
        // 2. Send notification email to admin
        // 3. Send confirmation email to user

        // For now, we'll just redirect with success
        return redirect()->back()->with('success', 'Thank you for your message! We will get back to you within 24 hours.');
    }
}
