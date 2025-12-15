<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show the agency profile page
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = Auth::user();
        $user->load('agencyProfile');

        // Get profile data
        $profile = $user->agencyProfile;

        // Calculate profile completeness
        $profileCompleteness = $this->calculateProfileCompleteness($user);

        // Get agency statistics with null safety
        $stats = [
            'total_workers' => optional($user->agencyWorkers())->count() ?? 0,
            'active_workers' => optional($user->agencyWorkers())->where('status', 'active')->count() ?? 0,
            'total_clients' => optional($user->agencyClients())->count() ?? 0,
        ];

        return view('agency.profile.show', compact('user', 'profile', 'profileCompleteness', 'stats'));
    }

    /**
     * Show the agency profile edit form
     *
     * @return \Illuminate\View\View
     */
    public function edit()
    {
        $user = Auth::user();
        $user->load('agencyProfile');

        $profile = $user->agencyProfile;

        return view('agency.profile.edit', compact('user', 'profile'));
    }

    /**
     * Update the agency profile
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'agency_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'commission_rate' => 'nullable|numeric|min:0|max:50',
            'specializations' => 'nullable|array',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $avatar = $request->file('avatar');
                $filename = time() . '_' . $user->id . '.' . $avatar->getClientOriginalExtension();
                $path = $avatar->storeAs('avatars', $filename, 'public');
                $user->avatar = 'storage/' . $path;
                $user->save();
            }

            // Update or create agency profile
            $profile = $user->agencyProfile;
            if (!$profile) {
                $profile = new \App\Models\AgencyProfile(['user_id' => $user->id]);
            }

            $profile->fill([
                'agency_name' => $request->agency_name,
                'contact_name' => $request->contact_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'country' => $request->country,
                'description' => $request->description,
                'commission_rate' => $request->commission_rate,
                'specializations' => $request->specializations ? json_encode($request->specializations) : null,
            ]);
            $profile->save();

            return redirect()->route('agency.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Agency Profile Update Error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }

    /**
     * Calculate profile completeness percentage
     *
     * @param  \App\Models\User  $user
     * @return int
     */
    private function calculateProfileCompleteness($user)
    {
        $completeness = 0;

        // Base user fields
        if ($user->name) $completeness += 10;
        if ($user->email) $completeness += 10;
        if ($user->avatar && $user->avatar != 'avatar.jpg') $completeness += 10;

        // Agency profile fields
        if ($user->agencyProfile) {
            $profile = $user->agencyProfile;
            if ($profile->agency_name) $completeness += 15;
            if ($profile->contact_name) $completeness += 10;
            if ($profile->phone) $completeness += 10;
            if ($profile->address) $completeness += 10;
            if ($profile->city && $profile->state) $completeness += 10;
            if ($profile->description) $completeness += 10;
            if ($profile->commission_rate) $completeness += 5;
        }

        return min($completeness, 100);
    }
}
