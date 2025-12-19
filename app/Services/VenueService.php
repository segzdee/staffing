<?php

namespace App\Services;

use App\Models\TeamActivity;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueManager;
use App\Models\VenueOperatingHour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BIZ-REG-006: Venue Service
 *
 * Handles venue management operations including:
 * - Venue CRUD operations
 * - Geocoding and address validation
 * - Geofence calculations
 * - Operating hours management
 * - Venue manager assignments
 */
class VenueService
{
    /**
     * Default geofence radius in meters.
     */
    public const DEFAULT_GEOFENCE_RADIUS = 100;

    /**
     * Default GPS accuracy requirement in meters.
     */
    public const DEFAULT_GPS_ACCURACY = 50;

    /**
     * Create a new venue.
     */
    public function createVenue(array $data, User $createdBy): Venue
    {
        return DB::transaction(function () use ($data, $createdBy) {
            // Get business profile
            $businessProfile = $createdBy->businessProfile;
            if (! $businessProfile) {
                throw new \Exception('User does not have a business profile.');
            }

            // Generate venue code
            $code = Venue::generateCode($data['name'], $businessProfile->id);

            // Geocode address if lat/lng not provided
            if (empty($data['latitude']) || empty($data['longitude'])) {
                $geocoded = $this->geocodeAddress($data);
                if ($geocoded) {
                    $data['latitude'] = $geocoded['lat'];
                    $data['longitude'] = $geocoded['lng'];
                    // Update timezone if geocoded
                    if (isset($geocoded['timezone'])) {
                        $data['timezone'] = $geocoded['timezone'];
                    }
                }
            }

            // Create venue
            $venue = Venue::create([
                'business_profile_id' => $businessProfile->id,
                'name' => $data['name'],
                'code' => $code,
                'type' => $data['type'] ?? 'office',
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'postal_code' => $data['postal_code'],
                'country' => $data['country'] ?? 'US',
                'timezone' => $data['timezone'] ?? config('app.timezone', 'UTC'),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'geofence_radius' => $data['geofence_radius'] ?? self::DEFAULT_GEOFENCE_RADIUS,
                'geofence_polygon' => $data['geofence_polygon'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'contact_person' => $data['contact_person'] ?? null,
                'manager_name' => $data['manager_name'] ?? null,
                'manager_phone' => $data['manager_phone'] ?? null,
                'manager_email' => $data['manager_email'] ?? null,
                'parking_instructions' => $data['parking_instructions'] ?? null,
                'entrance_instructions' => $data['entrance_instructions'] ?? null,
                'checkin_instructions' => $data['checkin_instructions'] ?? null,
                'dress_code' => $data['dress_code'] ?? null,
                'equipment_provided' => $data['equipment_provided'] ?? null,
                'equipment_required' => $data['equipment_required'] ?? null,
                'monthly_budget' => isset($data['monthly_budget']) ? (int) ($data['monthly_budget'] * 100) : 0,
                'default_hourly_rate' => isset($data['default_hourly_rate']) ? (int) ($data['default_hourly_rate'] * 100) : null,
                'auto_approve_favorites' => $data['auto_approve_favorites'] ?? false,
                'require_checkin_photo' => $data['require_checkin_photo'] ?? false,
                'require_checkout_signature' => $data['require_checkout_signature'] ?? false,
                'gps_accuracy_required' => $data['gps_accuracy_required'] ?? self::DEFAULT_GPS_ACCURACY,
                'settings' => $data['settings'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'is_active' => true,
                'status' => 'active',
            ]);

            // Create default operating hours
            if (isset($data['operating_hours']) && ! empty($data['operating_hours'])) {
                $this->updateOperatingHours($venue, $data['operating_hours']);
            } else {
                VenueOperatingHour::createDefaultHours($venue->id);
            }

            // Assign managers if provided
            if (isset($data['manager_ids']) && is_array($data['manager_ids'])) {
                $this->assignManagers($venue, $data['manager_ids']);
            }

            // Log activity
            TeamActivity::logVenueCreated($venue, $createdBy);

            // Check if this is the first venue (milestone)
            if ($venue->isFirstVenue()) {
                $this->handleFirstVenueMilestone($venue, $createdBy);
            }

            return $venue->fresh(['operatingHours', 'managers']);
        });
    }

    /**
     * Update an existing venue.
     */
    public function updateVenue(Venue $venue, array $data, User $updatedBy): Venue
    {
        return DB::transaction(function () use ($venue, $data, $updatedBy) {
            // Track changes for activity log
            $changes = [];
            $oldValues = $venue->toArray();

            // Geocode if address changed
            $addressFields = ['address', 'city', 'state', 'postal_code', 'country'];
            $addressChanged = false;
            foreach ($addressFields as $field) {
                if (isset($data[$field]) && $data[$field] !== $venue->{$field}) {
                    $addressChanged = true;
                    break;
                }
            }

            if ($addressChanged && (empty($data['latitude']) || empty($data['longitude']))) {
                $geocoded = $this->geocodeAddress(array_merge($venue->toArray(), $data));
                if ($geocoded) {
                    $data['latitude'] = $geocoded['lat'];
                    $data['longitude'] = $geocoded['lng'];
                }
            }

            // Convert money fields from dollars to cents
            if (isset($data['monthly_budget'])) {
                $data['monthly_budget'] = (int) ($data['monthly_budget'] * 100);
            }
            if (isset($data['default_hourly_rate'])) {
                $data['default_hourly_rate'] = (int) ($data['default_hourly_rate'] * 100);
            }

            // Update venue
            $venue->update($data);

            // Update operating hours if provided
            if (isset($data['operating_hours'])) {
                $this->updateOperatingHours($venue, $data['operating_hours']);
            }

            // Update managers if provided
            if (isset($data['manager_ids'])) {
                $this->assignManagers($venue, $data['manager_ids']);
            }

            // Log activity
            $newValues = $venue->fresh()->toArray();
            foreach ($data as $key => $value) {
                if (isset($oldValues[$key]) && $oldValues[$key] !== $newValues[$key]) {
                    $changes[$key] = [
                        'old' => $oldValues[$key],
                        'new' => $newValues[$key],
                    ];
                }
            }

            if (! empty($changes)) {
                TeamActivity::log(
                    $venue->businessProfile->user_id,
                    $updatedBy->id,
                    'venue_updated',
                    "Updated venue: {$venue->name}",
                    ['changes' => $changes],
                    null,
                    $venue->id,
                    Venue::class,
                    $venue->id
                );
            }

            return $venue->fresh(['operatingHours', 'managers']);
        });
    }

    /**
     * Update worker instructions for a venue.
     */
    public function updateInstructions(Venue $venue, array $data, User $updatedBy): Venue
    {
        $venue->update([
            'parking_instructions' => $data['parking_instructions'] ?? $venue->parking_instructions,
            'entrance_instructions' => $data['entrance_instructions'] ?? $venue->entrance_instructions,
            'checkin_instructions' => $data['checkin_instructions'] ?? $venue->checkin_instructions,
            'dress_code' => $data['dress_code'] ?? $venue->dress_code,
            'equipment_provided' => $data['equipment_provided'] ?? $venue->equipment_provided,
            'equipment_required' => $data['equipment_required'] ?? $venue->equipment_required,
        ]);

        TeamActivity::log(
            $venue->businessProfile->user_id,
            $updatedBy->id,
            'venue_updated',
            "Updated worker instructions for: {$venue->name}",
            ['section' => 'instructions'],
            null,
            $venue->id,
            Venue::class,
            $venue->id
        );

        return $venue->fresh();
    }

    /**
     * Update venue settings.
     */
    public function updateSettings(Venue $venue, array $data, User $updatedBy): Venue
    {
        $venue->update([
            'default_hourly_rate' => isset($data['default_hourly_rate']) ? (int) ($data['default_hourly_rate'] * 100) : $venue->default_hourly_rate,
            'auto_approve_favorites' => $data['auto_approve_favorites'] ?? $venue->auto_approve_favorites,
            'require_checkin_photo' => $data['require_checkin_photo'] ?? $venue->require_checkin_photo,
            'require_checkout_signature' => $data['require_checkout_signature'] ?? $venue->require_checkout_signature,
            'gps_accuracy_required' => $data['gps_accuracy_required'] ?? $venue->gps_accuracy_required,
            'geofence_radius' => $data['geofence_radius'] ?? $venue->geofence_radius,
            'monthly_budget' => isset($data['monthly_budget']) ? (int) ($data['monthly_budget'] * 100) : $venue->monthly_budget,
            'settings' => array_merge($venue->settings ?? [], $data['settings'] ?? []),
        ]);

        TeamActivity::log(
            $venue->businessProfile->user_id,
            $updatedBy->id,
            'venue_updated',
            "Updated settings for: {$venue->name}",
            ['section' => 'settings'],
            null,
            $venue->id,
            Venue::class,
            $venue->id
        );

        return $venue->fresh();
    }

    /**
     * Deactivate a venue.
     */
    public function deactivateVenue(Venue $venue, User $deactivatedBy): void
    {
        $venue->update([
            'is_active' => false,
            'status' => 'inactive',
        ]);

        TeamActivity::log(
            $venue->businessProfile->user_id,
            $deactivatedBy->id,
            'venue_deactivated',
            "Deactivated venue: {$venue->name}",
            null,
            null,
            $venue->id,
            Venue::class,
            $venue->id
        );
    }

    /**
     * Reactivate a venue.
     */
    public function reactivateVenue(Venue $venue, User $reactivatedBy): void
    {
        $venue->update([
            'is_active' => true,
            'status' => 'active',
        ]);

        TeamActivity::log(
            $venue->businessProfile->user_id,
            $reactivatedBy->id,
            'venue_reactivated',
            "Reactivated venue: {$venue->name}",
            null,
            null,
            $venue->id,
            Venue::class,
            $venue->id
        );
    }

    /**
     * Delete (soft delete) a venue.
     */
    public function deleteVenue(Venue $venue, User $deletedBy): void
    {
        // Check for active shifts
        $activeShifts = $venue->shifts()
            ->where('status', 'open')
            ->orWhere('start_time', '>', now())
            ->count();

        if ($activeShifts > 0) {
            throw new \Exception("Cannot delete venue with {$activeShifts} active or upcoming shifts.");
        }

        TeamActivity::log(
            $venue->businessProfile->user_id,
            $deletedBy->id,
            'venue_deleted',
            "Deleted venue: {$venue->name}",
            ['venue_id' => $venue->id, 'venue_name' => $venue->name],
            null,
            null,
            Venue::class,
            $venue->id
        );

        $venue->delete();
    }

    /**
     * Geocode an address using external service.
     */
    public function geocodeAddress(array $data): ?array
    {
        $address = implode(', ', array_filter([
            $data['address'] ?? '',
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['postal_code'] ?? '',
            $data['country'] ?? 'US',
        ]));

        if (empty(trim($address))) {
            return null;
        }

        try {
            // Try Google Geocoding API if configured
            $googleApiKey = config('services.google.maps_api_key');
            if ($googleApiKey) {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $googleApiKey,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    if (! empty($result['results'][0])) {
                        $location = $result['results'][0]['geometry']['location'];

                        return [
                            'lat' => $location['lat'],
                            'lng' => $location['lng'],
                            'formatted_address' => $result['results'][0]['formatted_address'],
                        ];
                    }
                }
            }

            // Fallback to Nominatim (OpenStreetMap)
            $response = Http::withHeaders([
                'User-Agent' => config('app.name').' Geocoder',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (! empty($result[0])) {
                    return [
                        'lat' => (float) $result[0]['lat'],
                        'lng' => (float) $result[0]['lon'],
                        'formatted_address' => $result[0]['display_name'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Geocoding failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Validate coordinates are within geofence.
     */
    public function validateGeofence(Venue $venue, float $lat, float $lng, ?int $accuracy = null): array
    {
        $isWithin = $venue->isWithinGeofence($lat, $lng, $accuracy);
        $distance = $venue->calculateDistance($lat, $lng);

        return [
            'is_valid' => $isWithin,
            'distance_meters' => round($distance, 2),
            'geofence_radius' => $venue->geofence_radius,
            'gps_accuracy_required' => $venue->gps_accuracy_required,
            'gps_accuracy_provided' => $accuracy,
            'message' => $isWithin
                ? 'Location is within the venue geofence.'
                : "Location is {$distance}m away from venue. Must be within {$venue->geofence_radius}m.",
        ];
    }

    /**
     * Calculate geofence radius based on venue size/type.
     */
    public function calculateRecommendedGeofence(string $type): int
    {
        $recommendations = [
            'office' => 50,
            'retail' => 75,
            'warehouse' => 200,
            'restaurant' => 50,
            'hotel' => 100,
            'event_venue' => 250,
            'healthcare' => 100,
            'industrial' => 300,
            'construction' => 500,
            'education' => 150,
            'entertainment' => 200,
            'other' => 100,
        ];

        return $recommendations[$type] ?? self::DEFAULT_GEOFENCE_RADIUS;
    }

    /**
     * Update operating hours for a venue.
     */
    public function updateOperatingHours(Venue $venue, array $hours): void
    {
        // Delete existing hours
        $venue->operatingHours()->delete();

        // Create new hours
        foreach ($hours as $dayData) {
            if (! isset($dayData['day_of_week'])) {
                continue;
            }

            // Handle multiple time slots per day
            $slots = isset($dayData['slots']) ? $dayData['slots'] : [$dayData];

            foreach ($slots as $index => $slot) {
                VenueOperatingHour::create([
                    'venue_id' => $venue->id,
                    'day_of_week' => $dayData['day_of_week'],
                    'open_time' => $slot['open_time'] ?? '09:00:00',
                    'close_time' => $slot['close_time'] ?? '17:00:00',
                    'is_primary' => $index === 0,
                    'is_open' => $slot['is_open'] ?? true,
                    'notes' => $slot['notes'] ?? null,
                ]);
            }
        }
    }

    /**
     * Validate operating hours consistency.
     */
    public function validateOperatingHours(array $hours): array
    {
        $errors = [];

        foreach ($hours as $dayData) {
            if (! isset($dayData['day_of_week'])) {
                continue;
            }

            $slots = isset($dayData['slots']) ? $dayData['slots'] : [$dayData];

            foreach ($slots as $slot) {
                if (($slot['is_open'] ?? true) === false) {
                    continue;
                }

                $open = $slot['open_time'] ?? null;
                $close = $slot['close_time'] ?? null;

                if (! $open || ! $close) {
                    $errors[] = "Day {$dayData['day_of_week']}: Open and close times are required.";

                    continue;
                }

                $openTime = Carbon::parse($open);
                $closeTime = Carbon::parse($close);

                // Allow overnight hours (close < open)
                if ($closeTime->eq($openTime)) {
                    $errors[] = "Day {$dayData['day_of_week']}: Open and close times cannot be the same.";
                }
            }
        }

        return $errors;
    }

    /**
     * Assign managers to a venue.
     */
    public function assignManagers(Venue $venue, array $teamMemberIds, ?int $primaryManagerId = null): void
    {
        // Remove existing managers
        $venue->venueManagers()->delete();

        // Assign new managers
        foreach ($teamMemberIds as $teamMemberId) {
            $teamMember = TeamMember::find($teamMemberId);
            if (! $teamMember || $teamMember->business_id !== $venue->businessProfile->user_id) {
                continue;
            }

            VenueManager::create([
                'venue_id' => $venue->id,
                'team_member_id' => $teamMemberId,
                'is_primary' => $primaryManagerId ? ($teamMemberId == $primaryManagerId) : false,
                'can_post_shifts' => $teamMember->can_post_shifts,
                'can_edit_shifts' => $teamMember->can_edit_shifts,
                'can_cancel_shifts' => $teamMember->can_cancel_shifts,
                'can_approve_workers' => $teamMember->can_approve_applications,
                'can_manage_venue_settings' => $teamMember->can_manage_venues,
            ]);
        }

        // Update manager_ids on venue
        $venue->update(['manager_ids' => $teamMemberIds]);
    }

    /**
     * Add a manager to a venue.
     */
    public function addManager(Venue $venue, TeamMember $teamMember, bool $isPrimary = false): VenueManager
    {
        // Check if already a manager
        $existing = VenueManager::where('venue_id', $venue->id)
            ->where('team_member_id', $teamMember->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $manager = VenueManager::create([
            'venue_id' => $venue->id,
            'team_member_id' => $teamMember->id,
            'is_primary' => $isPrimary,
            'can_post_shifts' => $teamMember->can_post_shifts,
            'can_edit_shifts' => $teamMember->can_edit_shifts,
            'can_cancel_shifts' => $teamMember->can_cancel_shifts,
            'can_approve_workers' => $teamMember->can_approve_applications,
            'can_manage_venue_settings' => $teamMember->can_manage_venues,
        ]);

        // Update venue manager_ids
        $managerIds = $venue->manager_ids ?? [];
        $managerIds[] = $teamMember->id;
        $venue->update(['manager_ids' => array_unique($managerIds)]);

        return $manager;
    }

    /**
     * Remove a manager from a venue.
     */
    public function removeManager(Venue $venue, TeamMember $teamMember): void
    {
        VenueManager::where('venue_id', $venue->id)
            ->where('team_member_id', $teamMember->id)
            ->delete();

        // Update venue manager_ids
        $managerIds = $venue->manager_ids ?? [];
        $managerIds = array_values(array_filter($managerIds, fn ($id) => $id != $teamMember->id));
        $venue->update(['manager_ids' => $managerIds]);
    }

    /**
     * Handle first venue milestone.
     */
    protected function handleFirstVenueMilestone(Venue $venue, User $createdBy): void
    {
        // Update business profile onboarding
        $businessProfile = $venue->businessProfile;
        if ($businessProfile) {
            $onboardingData = $businessProfile->onboarding_data ?? [];
            $onboardingData['first_venue_created'] = true;
            $onboardingData['first_venue_created_at'] = now()->toIso8601String();
            $onboardingData['first_venue_id'] = $venue->id;

            $businessProfile->update(['onboarding_data' => $onboardingData]);
        }

        // Send congratulations notification for first venue milestone
        $createdBy->notify(new \App\Notifications\Business\FirstVenueCreatedNotification($venue));
    }

    /**
     * Get venues for a business with filters.
     */
    public function getVenuesForBusiness(int $businessProfileId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Venue::forBusiness($businessProfileId)
            ->with(['operatingHours', 'managers.user']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->active();
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        return $query->get();
    }

    /**
     * Get venue statistics for a business.
     */
    public function getVenueStatistics(int $businessProfileId): array
    {
        $venues = Venue::forBusiness($businessProfileId)->get();

        return [
            'total_venues' => $venues->count(),
            'active_venues' => $venues->where('is_active', true)->count(),
            'inactive_venues' => $venues->where('is_active', false)->count(),
            'total_shifts' => $venues->sum('total_shifts'),
            'completed_shifts' => $venues->sum('completed_shifts'),
            'total_budget' => $venues->sum('monthly_budget'),
            'total_spend' => $venues->sum('current_month_spend'),
            'average_fill_rate' => round($venues->avg('fill_rate'), 2),
            'average_rating' => round($venues->avg('average_rating'), 2),
            'by_type' => $venues->groupBy('type')->map->count(),
        ];
    }
}
