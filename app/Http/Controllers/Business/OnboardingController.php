<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(['auth', 'business']);
    }

    /**
     * Show the profile completion page for businesses.
     * Guides businesses through completing their profile after registration.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function completeProfile()
    {
        $user = Auth::user();
        $user->load('businessProfile');

        // Calculate profile completeness
        $completeness = $this->calculateProfileCompleteness($user);

        // If profile is already complete (>80%), redirect to dashboard
        if ($completeness >= 80) {
            return redirect()->route('business.dashboard')
                ->with('success', 'Your profile is complete! Start posting shifts.');
        }

        // Identify missing fields
        $missingFields = $this->getMissingFields($user);

        return view('business.onboarding.complete-profile', compact(
            'user',
            'completeness',
            'missingFields'
        ));
    }

    /**
     * Show payment setup page.
     * Guides businesses through setting up payment methods.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function setupPayment()
    {
        $user = Auth::user();

        // Check if payment method is already configured
        // TODO: Add proper payment gateway check when implemented
        $hasPaymentMethod = false;

        if ($hasPaymentMethod) {
            return redirect()->route('business.dashboard')
                ->with('success', 'Payment method already configured.');
        }

        return view('business.onboarding.setup-payment', compact('user'));
    }

    /**
     * Calculate profile completeness percentage.
     *
     * @param  \App\Models\User  $user
     * @return int
     */
    private function calculateProfileCompleteness($user)
    {
        $completeness = 0;

        // Base user fields (30%)
        if ($user->name) $completeness += 10;
        if ($user->email) $completeness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $completeness += 10;

        // Business profile fields (70%)
        if ($user->businessProfile) {
            $profile = $user->businessProfile;

            if ($profile->company_name) $completeness += 15;
            if ($profile->business_type) $completeness += 10;
            if ($profile->address) $completeness += 10;
            if ($profile->city && $profile->state) $completeness += 15;
            if ($profile->phone) $completeness += 10;
            if ($profile->description) $completeness += 10;
        }

        return min($completeness, 100);
    }

    /**
     * Get list of missing profile fields.
     *
     * @param  \App\Models\User  $user
     * @return array
     */
    private function getMissingFields($user)
    {
        $missing = [];

        // Check base user fields
        if (!$user->avatar || $user->avatar == 'avatar.jpg') {
            $missing[] = [
                'field' => 'avatar',
                'label' => 'Company Logo',
                'description' => 'A logo helps workers recognize your business',
                'priority' => 'medium'
            ];
        }

        // Check business profile fields
        $profile = $user->businessProfile;

        if (!$profile || !$profile->company_name) {
            $missing[] = [
                'field' => 'company_name',
                'label' => 'Company Name',
                'description' => 'Your official business name',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->business_type) {
            $missing[] = [
                'field' => 'business_type',
                'label' => 'Business Type',
                'description' => 'What industry is your business in?',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->address) {
            $missing[] = [
                'field' => 'address',
                'label' => 'Business Address',
                'description' => 'Where workers will report for shifts',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->city || !$profile->state) {
            $missing[] = [
                'field' => 'location',
                'label' => 'City and State',
                'description' => 'Your business location',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->phone) {
            $missing[] = [
                'field' => 'phone',
                'label' => 'Business Phone',
                'description' => 'Contact number for workers',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->description) {
            $missing[] = [
                'field' => 'description',
                'label' => 'Business Description',
                'description' => 'Tell workers about your company',
                'priority' => 'medium'
            ];
        }

        return $missing;
    }
}
