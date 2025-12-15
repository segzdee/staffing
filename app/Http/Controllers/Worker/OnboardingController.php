<?php

namespace App\Http\Controllers\Worker;

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
        $this->middleware(['auth', 'worker']);
    }

    /**
     * Show the profile completion page for workers.
     * Guides workers through completing their profile after registration.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function completeProfile()
    {
        $user = Auth::user();
        $user->load('workerProfile');

        // Calculate profile completeness
        $completeness = $this->calculateProfileCompleteness($user);

        // If profile is already complete (>80%), redirect to dashboard
        if ($completeness >= 80) {
            return redirect()->route('worker.dashboard')
                ->with('success', 'Your profile is complete! Start browsing shifts.');
        }

        // Identify missing fields
        $missingFields = $this->getMissingFields($user);

        return view('worker.onboarding.complete-profile', compact(
            'user',
            'completeness',
            'missingFields'
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

        // Basic user fields (60%)
        if ($user->name) $completeness += 15;
        if ($user->email) $completeness += 15;
        if ($user->phone) $completeness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $completeness += 10;
        if ($user->city && $user->state) $completeness += 10;

        // Worker profile fields (40%)
        if ($user->workerProfile) {
            $profile = $user->workerProfile;

            if ($profile->experience_level) $completeness += 10;
            if ($profile->skills) $completeness += 10;
            if ($profile->preferred_industries) $completeness += 10;
            if ($profile->has_transportation !== null) $completeness += 5;
            if ($profile->max_commute_distance) $completeness += 5;
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

        // Check basic user fields
        if (!$user->phone) {
            $missing[] = [
                'field' => 'phone',
                'label' => 'Phone Number',
                'description' => 'Employers need to contact you about shifts',
                'priority' => 'high'
            ];
        }

        if (!$user->city || !$user->state) {
            $missing[] = [
                'field' => 'location',
                'label' => 'Location',
                'description' => 'We need your location to show you nearby shifts',
                'priority' => 'high'
            ];
        }

        if (!$user->avatar || $user->avatar == 'avatar.jpg') {
            $missing[] = [
                'field' => 'avatar',
                'label' => 'Profile Photo',
                'description' => 'A photo helps employers recognize you',
                'priority' => 'medium'
            ];
        }

        // Check worker profile fields
        $profile = $user->workerProfile;

        if (!$profile || !$profile->experience_level) {
            $missing[] = [
                'field' => 'experience_level',
                'label' => 'Experience Level',
                'description' => 'Help employers find workers with the right experience',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->skills) {
            $missing[] = [
                'field' => 'skills',
                'label' => 'Skills',
                'description' => 'List your skills to match with relevant shifts',
                'priority' => 'high'
            ];
        }

        if (!$profile || !$profile->preferred_industries) {
            $missing[] = [
                'field' => 'industries',
                'label' => 'Preferred Industries',
                'description' => 'Select industries you want to work in',
                'priority' => 'medium'
            ];
        }

        if (!$profile || $profile->has_transportation === null) {
            $missing[] = [
                'field' => 'transportation',
                'label' => 'Transportation',
                'description' => 'Let employers know if you have reliable transportation',
                'priority' => 'medium'
            ];
        }

        if (!$profile || !$profile->max_commute_distance) {
            $missing[] = [
                'field' => 'max_distance',
                'label' => 'Maximum Commute Distance',
                'description' => 'How far are you willing to travel for work?',
                'priority' => 'low'
            ];
        }

        return $missing;
    }
}
