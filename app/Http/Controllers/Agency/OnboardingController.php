<?php

namespace App\Http\Controllers\Agency;

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
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show the profile completion page for agencies.
     * Guides agencies through completing their profile after registration.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function completeProfile()
    {
        $user = Auth::user();
        $user->load('agencyProfile');

        // Calculate profile completeness
        $completeness = $this->calculateProfileCompleteness($user);

        // If profile is already complete (>80%), redirect to dashboard
        if ($completeness >= 80) {
            return redirect()->route('agency.dashboard')
                ->with('success', 'Your profile is complete!');
        }

        // Identify missing fields
        $missingFields = $this->getMissingFields($user);

        return view('agency.onboarding.complete-profile', compact(
            'user',
            'completeness',
            'missingFields'
        ));
    }

    /**
     * Show verification pending page for agencies awaiting approval.
     *
     * @return \Illuminate\View\View
     */
    public function verificationPending()
    {
        $user = Auth::user();
        $user->load('agencyProfile');

        // Check verification status
        $verificationStatus = $user->agencyProfile ? $user->agencyProfile->verification_status : 'pending';

        // If already verified, redirect to dashboard
        if ($verificationStatus === 'verified') {
            return redirect()->route('agency.dashboard')
                ->with('success', 'Your agency has been verified!');
        }

        // If rejected, show different message
        $isRejected = $verificationStatus === 'rejected';

        return view('agency.onboarding.verification-pending', compact(
            'user',
            'verificationStatus',
            'isRejected'
        ));
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

        // Agency profile fields (70%)
        if ($user->agencyProfile) {
            $profile = $user->agencyProfile;

            if ($profile->agency_name) $completeness += 15;
            if ($profile->agency_type) $completeness += 10;
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
                'label' => 'Agency Logo',
                'description' => 'A logo helps build trust with clients',
                'priority' => 'medium'
            ];
        }

        // Check agency profile fields
        $profile = $user->agencyProfile;

        if (!$profile || !$profile->agency_name) {
            $missing[] = [
                'field' => 'agency_name',
                'label' => 'Agency Name',
                'description' => 'Your official agency name',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->agency_type) {
            $missing[] = [
                'field' => 'agency_type',
                'label' => 'Agency Type',
                'description' => 'What type of staffing agency are you?',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->address) {
            $missing[] = [
                'field' => 'address',
                'label' => 'Agency Address',
                'description' => 'Your business address',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->city || !$profile->state) {
            $missing[] = [
                'field' => 'location',
                'label' => 'City and State',
                'description' => 'Your agency location',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->phone) {
            $missing[] = [
                'field' => 'phone',
                'label' => 'Agency Phone',
                'description' => 'Contact number for clients',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->description) {
            $missing[] = [
                'field' => 'description',
                'label' => 'Agency Description',
                'description' => 'Tell clients about your agency',
                'priority' => 'medium'
            ];
        }

        return $missing;
    }
}
