<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\StoreVenueRequest;
use App\Http\Requests\Business\UpdateVenueRequest;
use App\Http\Requests\Business\UpdateVenueInstructionsRequest;
use App\Http\Requests\Business\UpdateVenueSettingsRequest;
use App\Models\Venue;
use App\Models\TeamMember;
use App\Services\VenueService;
use App\Services\TeamManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BIZ-REG-006: Venue Management Controller
 *
 * Handles all venue-related operations for businesses.
 */
class VenueController extends Controller
{
    protected VenueService $venueService;
    protected TeamManagementService $teamService;

    public function __construct(VenueService $venueService, TeamManagementService $teamService)
    {
        $this->venueService = $venueService;
        $this->teamService = $teamService;
    }

    /**
     * Display a listing of venues.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            return redirect()->route('business.profile.complete')
                ->with('error', 'Please complete your business profile first.');
        }

        // Check permission
        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to manage venues.');
        }

        $filters = [
            'status' => $request->get('status'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
            'active_only' => $request->boolean('active_only'),
            'sort_by' => $request->get('sort_by', 'name'),
            'sort_dir' => $request->get('sort_dir', 'asc'),
        ];

        $venues = $this->venueService->getVenuesForBusiness($businessProfile->id, $filters);
        $statistics = $this->venueService->getVenueStatistics($businessProfile->id);
        $venueTypes = Venue::TYPES;

        return view('business.venues.index', compact('venues', 'statistics', 'venueTypes', 'filters'));
    }

    /**
     * Show the form for creating a new venue.
     */
    public function create()
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to create venues.');
        }

        $businessProfile = $user->businessProfile;
        $venueTypes = Venue::TYPES;
        $dressCodes = Venue::DRESS_CODES;
        $timezones = \DateTimeZone::listIdentifiers();

        // Get team members who can manage venues
        $teamMembers = TeamMember::forBusiness($user->id)
            ->active()
            ->with('user')
            ->get();

        return view('business.venues.create', compact(
            'venueTypes',
            'dressCodes',
            'timezones',
            'teamMembers',
            'businessProfile'
        ));
    }

    /**
     * Store a newly created venue.
     */
    public function store(StoreVenueRequest $request)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to create venues.');
        }

        try {
            $venue = $this->venueService->createVenue($request->validated(), $user);

            return redirect()->route('business.venues.show', $venue->id)
                ->with('success', 'Venue created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create venue: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified venue.
     */
    public function show($id)
    {
        $user = Auth::user();
        $venue = $this->getVenueForBusiness($id, $user);

        // Check access
        $teamMember = $this->teamService->getTeamMember($user, $user->id);
        if ($teamMember && !$teamMember->canAccessVenue($venue->id)) {
            abort(403, 'You do not have access to this venue.');
        }

        $venue->load(['operatingHours', 'managers.user', 'shifts' => function ($q) {
            $q->orderBy('start_time', 'desc')->limit(10);
        }]);

        $formattedHours = $venue->getFormattedOperatingHours();
        $workerInstructions = $venue->getWorkerInstructions();
        $settings = $venue->getSettingsWithDefaults();

        return view('business.venues.show', compact('venue', 'formattedHours', 'workerInstructions', 'settings'));
    }

    /**
     * Show the form for editing the specified venue.
     */
    public function edit($id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to edit venues.');
        }

        $venue = $this->getVenueForBusiness($id, $user);
        $venue->load(['operatingHours', 'managers']);

        $venueTypes = Venue::TYPES;
        $dressCodes = Venue::DRESS_CODES;
        $timezones = \DateTimeZone::listIdentifiers();

        $teamMembers = TeamMember::forBusiness($user->id)
            ->active()
            ->with('user')
            ->get();

        return view('business.venues.edit', compact(
            'venue',
            'venueTypes',
            'dressCodes',
            'timezones',
            'teamMembers'
        ));
    }

    /**
     * Update the specified venue.
     */
    public function update(UpdateVenueRequest $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to update venues.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $venue = $this->venueService->updateVenue($venue, $request->validated(), $user);

            return redirect()->route('business.venues.show', $venue->id)
                ->with('success', 'Venue updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update venue: ' . $e->getMessage());
        }
    }

    /**
     * Update worker instructions for the venue.
     */
    public function updateInstructions(UpdateVenueInstructionsRequest $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to update venue instructions.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $venue = $this->venueService->updateInstructions($venue, $request->validated(), $user);

            return redirect()->route('business.venues.show', $venue->id)
                ->with('success', 'Worker instructions updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update instructions: ' . $e->getMessage());
        }
    }

    /**
     * Update venue settings.
     */
    public function updateSettings(UpdateVenueSettingsRequest $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to update venue settings.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $venue = $this->venueService->updateSettings($venue, $request->validated(), $user);

            return redirect()->route('business.venues.show', $venue->id)
                ->with('success', 'Venue settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate the specified venue.
     */
    public function deactivate($id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to deactivate venues.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $this->venueService->deactivateVenue($venue, $user);

            return redirect()->route('business.venues.index')
                ->with('success', 'Venue deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to deactivate venue: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate the specified venue.
     */
    public function reactivate($id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to reactivate venues.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $this->venueService->reactivateVenue($venue, $user);

            return redirect()->route('business.venues.index')
                ->with('success', 'Venue reactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reactivate venue: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified venue.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to delete venues.');
        }

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $this->venueService->deleteVenue($venue, $user);

            return redirect()->route('business.venues.index')
                ->with('success', 'Venue deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete venue: ' . $e->getMessage());
        }
    }

    /**
     * Assign managers to a venue.
     */
    public function assignManagers(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageVenues($user)) {
            abort(403, 'You do not have permission to assign venue managers.');
        }

        $request->validate([
            'manager_ids' => 'required|array',
            'manager_ids.*' => 'integer|exists:team_members,id',
            'primary_manager_id' => 'nullable|integer|exists:team_members,id',
        ]);

        $venue = $this->getVenueForBusiness($id, $user);

        try {
            $this->venueService->assignManagers(
                $venue,
                $request->input('manager_ids'),
                $request->input('primary_manager_id')
            );

            return redirect()->route('business.venues.show', $venue->id)
                ->with('success', 'Venue managers assigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to assign managers: ' . $e->getMessage());
        }
    }

    /**
     * Validate geofence location.
     */
    public function validateLocation(Request $request, $id)
    {
        $user = Auth::user();
        $venue = $this->getVenueForBusiness($id, $user);

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0',
        ]);

        $result = $this->venueService->validateGeofence(
            $venue,
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('accuracy')
        );

        return response()->json($result);
    }

    /**
     * Get geocoded coordinates for an address.
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'nullable|string',
        ]);

        $result = $this->venueService->geocodeAddress($request->all());

        if ($result) {
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to geocode the provided address.',
        ], 422);
    }

    /**
     * Get recommended geofence radius for venue type.
     */
    public function getRecommendedGeofence(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $radius = $this->venueService->calculateRecommendedGeofence($request->input('type'));

        return response()->json([
            'recommended_radius' => $radius,
        ]);
    }

    /**
     * Get venue for the business.
     */
    protected function getVenueForBusiness($id, $user): Venue
    {
        $businessProfile = $user->businessProfile;

        if (!$businessProfile) {
            abort(404, 'Business profile not found.');
        }

        $venue = Venue::forBusiness($businessProfile->id)->findOrFail($id);

        return $venue;
    }

    /**
     * Check if user can manage venues.
     */
    protected function canManageVenues($user): bool
    {
        // Business owner can always manage venues
        if ($user->user_type === 'business') {
            return true;
        }

        // Check team member permission
        $teamMember = TeamMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('can_manage_venues', true)
            ->first();

        return !is_null($teamMember);
    }
}
