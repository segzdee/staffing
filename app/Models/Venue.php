<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BIZ-REG-006: Venue Model
 *
 * Comprehensive venue management for businesses.
 * Supports geofencing, operating hours, worker instructions,
 * and manager assignments.
 *
 * @property int $id
 * @property int $business_profile_id
 * @property string $name
 * @property string|null $code
 * @property string $type
 * @property string|null $description
 * @property string $address
 * @property string|null $address_line_2
 * @property string $city
 * @property string $state
 * @property string $postal_code
 * @property string $country
 * @property string $timezone
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $geofence_radius
 * @property array|null $geofence_polygon
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $contact_person
 * @property string|null $manager_name
 * @property string|null $manager_phone
 * @property string|null $manager_email
 * @property string|null $parking_instructions
 * @property string|null $entrance_instructions
 * @property string|null $checkin_instructions
 * @property string|null $dress_code
 * @property string|null $equipment_provided
 * @property string|null $equipment_required
 * @property int $monthly_budget
 * @property int|null $default_hourly_rate
 * @property bool $auto_approve_favorites
 * @property bool $require_checkin_photo
 * @property bool $require_checkout_signature
 * @property int $gps_accuracy_required
 * @property array|null $settings
 * @property array|null $manager_ids
 * @property string|null $image_url
 * @property int $current_month_spend
 * @property int $ytd_spend
 * @property int $total_shifts
 * @property int $completed_shifts
 * @property int $cancelled_shifts
 * @property float $fill_rate
 * @property float $average_rating
 * @property bool $is_active
 * @property string $status
 * @property \Carbon\Carbon|null $first_shift_posted_at
 * @property int $active_shifts_count
 */
class Venue extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Venue types available in the system.
     */
    public const TYPES = [
        'office' => 'Office',
        'retail' => 'Retail Store',
        'warehouse' => 'Warehouse',
        'restaurant' => 'Restaurant',
        'hotel' => 'Hotel',
        'event_venue' => 'Event Venue',
        'healthcare' => 'Healthcare Facility',
        'industrial' => 'Industrial',
        'construction' => 'Construction Site',
        'education' => 'Educational Institution',
        'entertainment' => 'Entertainment Venue',
        'other' => 'Other',
    ];

    /**
     * Dress code options.
     */
    public const DRESS_CODES = [
        'casual' => 'Casual',
        'business_casual' => 'Business Casual',
        'formal' => 'Formal',
        'uniform' => 'Uniform Provided',
        'safety_gear' => 'Safety Gear Required',
        'specific' => 'Specific Requirements (see instructions)',
    ];

    /**
     * Venue statuses.
     */
    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending Setup',
    ];

    protected $fillable = [
        'business_profile_id',
        'name',
        'code',
        'type',
        'description',
        'address',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'latitude',
        'longitude',
        'geofence_radius',
        'geofence_polygon',
        'phone',
        'email',
        'contact_person',
        'manager_name',
        'manager_phone',
        'manager_email',
        'parking_instructions',
        'entrance_instructions',
        'checkin_instructions',
        'dress_code',
        'equipment_provided',
        'equipment_required',
        'monthly_budget',
        'default_hourly_rate',
        'auto_approve_favorites',
        'require_checkin_photo',
        'require_checkout_signature',
        'gps_accuracy_required',
        'settings',
        'manager_ids',
        'image_url',
        'current_month_spend',
        'ytd_spend',
        'total_shifts',
        'completed_shifts',
        'cancelled_shifts',
        'fill_rate',
        'average_rating',
        'is_active',
        'status',
        'first_shift_posted_at',
        'active_shifts_count',
    ];

    protected $casts = [
        'monthly_budget' => 'integer',
        'default_hourly_rate' => 'integer',
        'current_month_spend' => 'integer',
        'ytd_spend' => 'integer',
        'total_shifts' => 'integer',
        'completed_shifts' => 'integer',
        'cancelled_shifts' => 'integer',
        'fill_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'geofence_radius' => 'integer',
        'geofence_polygon' => 'array',
        'gps_accuracy_required' => 'integer',
        'settings' => 'array',
        'manager_ids' => 'array',
        'is_active' => 'boolean',
        'auto_approve_favorites' => 'boolean',
        'require_checkin_photo' => 'boolean',
        'require_checkout_signature' => 'boolean',
        'first_shift_posted_at' => 'datetime',
        'active_shifts_count' => 'integer',
    ];

    protected $appends = [
        'full_address',
        'budget_utilization',
        'remaining_budget',
        'type_label',
        'status_label',
        'dress_code_label',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business profile that owns the venue.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get all shifts for this venue.
     */
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get operating hours for this venue.
     */
    public function operatingHours()
    {
        return $this->hasMany(VenueOperatingHour::class)->orderBy('day_of_week');
    }

    /**
     * Get venue managers through pivot.
     */
    public function venueManagers()
    {
        return $this->hasMany(VenueManager::class);
    }

    /**
     * Get team members assigned to manage this venue.
     */
    public function managers()
    {
        return $this->belongsToMany(TeamMember::class, 'venue_managers')
            ->withPivot([
                'is_primary',
                'can_post_shifts',
                'can_edit_shifts',
                'can_cancel_shifts',
                'can_approve_workers',
                'can_manage_venue_settings',
                'notify_new_applications',
                'notify_shift_changes',
                'notify_worker_checkins',
            ])
            ->withTimestamps();
    }

    /**
     * Get the primary manager for this venue.
     */
    public function primaryManager()
    {
        return $this->managers()->wherePivot('is_primary', true)->first();
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Calculate budget utilization percentage.
     */
    public function getBudgetUtilizationAttribute()
    {
        if ($this->monthly_budget <= 0) {
            return 0;
        }

        return round(($this->current_month_spend / $this->monthly_budget) * 100, 2);
    }

    /**
     * Get remaining budget for the month.
     */
    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->monthly_budget - $this->current_month_spend);
    }

    /**
     * Get venue type label.
     */
    public function getTypeLabelAttribute()
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get dress code label.
     */
    public function getDressCodeLabelAttribute()
    {
        return self::DRESS_CODES[$this->dress_code] ?? $this->dress_code;
    }

    /**
     * Format money values from cents to dollars.
     */
    public function getMonthlyBudgetDollarsAttribute()
    {
        return $this->monthly_budget / 100;
    }

    public function getCurrentMonthSpendDollarsAttribute()
    {
        return $this->current_month_spend / 100;
    }

    public function getYtdSpendDollarsAttribute()
    {
        return $this->ytd_spend / 100;
    }

    public function getDefaultHourlyRateDollarsAttribute()
    {
        return $this->default_hourly_rate ? $this->default_hourly_rate / 100 : null;
    }

    // =========================================
    // Geofencing Methods
    // =========================================

    /**
     * Check if coordinates are within the circular geofence.
     */
    public function isWithinGeofence(float $lat, float $lng, ?int $accuracy = null): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return true; // No geofence configured
        }

        // Check GPS accuracy requirement
        if ($accuracy !== null && $this->gps_accuracy_required > 0) {
            if ($accuracy > $this->gps_accuracy_required) {
                return false; // GPS accuracy too low
            }
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance($lat, $lng);

        // Check if polygon geofence is configured
        if (!empty($this->geofence_polygon)) {
            return $this->isWithinPolygon($lat, $lng);
        }

        // Check circular geofence
        return $distance <= $this->geofence_radius;
    }

    /**
     * Calculate distance in meters between two coordinates.
     */
    public function calculateDistance(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if point is within polygon geofence.
     * Uses ray casting algorithm.
     */
    public function isWithinPolygon(float $lat, float $lng): bool
    {
        $polygon = $this->geofence_polygon;
        if (empty($polygon) || count($polygon) < 3) {
            return true;
        }

        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            if ((($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    // =========================================
    // Operating Hours Methods
    // =========================================

    /**
     * Get operating hours for a specific day.
     */
    public function getHoursForDay(int $dayOfWeek): ?array
    {
        $hours = $this->operatingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_open', true)
            ->get();

        if ($hours->isEmpty()) {
            return null;
        }

        return $hours->map(function ($hour) {
            return [
                'open' => $hour->open_time,
                'close' => $hour->close_time,
                'notes' => $hour->notes,
            ];
        })->toArray();
    }

    /**
     * Check if venue is open at a given time.
     */
    public function isOpenAt(\Carbon\Carbon $dateTime): bool
    {
        $dayOfWeek = $dateTime->dayOfWeek;
        $time = $dateTime->format('H:i:s');

        $hours = $this->operatingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_open', true)
            ->get();

        foreach ($hours as $hour) {
            if ($time >= $hour->open_time && $time <= $hour->close_time) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get formatted operating hours for display.
     */
    public function getFormattedOperatingHours(): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $formatted = [];

        foreach ($days as $index => $day) {
            $hours = $this->operatingHours()
                ->where('day_of_week', $index)
                ->get();

            if ($hours->isEmpty() || !$hours->first()->is_open) {
                $formatted[$day] = 'Closed';
            } else {
                $slots = $hours->map(function ($hour) {
                    $open = \Carbon\Carbon::parse($hour->open_time)->format('g:i A');
                    $close = \Carbon\Carbon::parse($hour->close_time)->format('g:i A');
                    return "{$open} - {$close}";
                })->implode(', ');

                $formatted[$day] = $slots;
            }
        }

        return $formatted;
    }

    // =========================================
    // Budget Methods
    // =========================================

    /**
     * Check if budget alert threshold is reached.
     */
    public function hasBudgetAlertThreshold(int $threshold): bool
    {
        return $this->budget_utilization >= $threshold;
    }

    /**
     * Check if venue can accommodate a shift cost.
     */
    public function canAccommodateShiftCost(int $costInCents): bool
    {
        if ($this->monthly_budget <= 0) {
            return true; // No budget limit
        }

        return ($this->current_month_spend + $costInCents) <= $this->monthly_budget;
    }

    /**
     * Add spend to current month.
     */
    public function addSpend(int $amountInCents): void
    {
        $this->increment('current_month_spend', $amountInCents);
        $this->increment('ytd_spend', $amountInCents);
    }

    // =========================================
    // Statistics Methods
    // =========================================

    /**
     * Increment shift counters.
     */
    public function incrementShiftCount(): void
    {
        $this->increment('total_shifts');
        $this->increment('active_shifts_count');

        if (!$this->first_shift_posted_at) {
            $this->update(['first_shift_posted_at' => now()]);
        }
    }

    /**
     * Record completed shift.
     */
    public function recordCompletedShift(): void
    {
        $this->increment('completed_shifts');
        $this->decrement('active_shifts_count');
        $this->updateFillRate();
    }

    /**
     * Record cancelled shift.
     */
    public function recordCancelledShift(): void
    {
        $this->increment('cancelled_shifts');
        $this->decrement('active_shifts_count');
        $this->updateFillRate();
    }

    /**
     * Update fill rate calculation.
     */
    public function updateFillRate(): void
    {
        if ($this->total_shifts > 0) {
            $rate = ($this->completed_shifts / $this->total_shifts) * 100;
            $this->update(['fill_rate' => round($rate, 2)]);
        }
    }

    /**
     * Update average rating.
     */
    public function updateAverageRating(): void
    {
        $avgRating = $this->shifts()
            ->whereHas('ratings')
            ->with('ratings')
            ->get()
            ->flatMap->ratings
            ->avg('overall_rating');

        if ($avgRating) {
            $this->update(['average_rating' => round($avgRating, 2)]);
        }
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope to get active venues only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope to get venues by business.
     */
    public function scopeForBusiness($query, $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }

    /**
     * Scope to get venues by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get venues by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get venues within a radius.
     */
    public function scopeWithinRadius($query, float $lat, float $lng, float $radiusKm)
    {
        // Haversine formula for distance calculation
        $haversine = "(6371 * acos(cos(radians(?))
                     * cos(radians(latitude))
                     * cos(radians(longitude) - radians(?))
                     + sin(radians(?))
                     * sin(radians(latitude))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->havingRaw('distance < ?', [$radiusKm])
            ->orderBy('distance');
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Generate unique venue code.
     */
    public static function generateCode(string $name, int $businessProfileId): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        $suffix = str_pad($businessProfileId, 3, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$base}-{$suffix}-{$random}";
    }

    /**
     * Check if venue is the first for this business.
     */
    public function isFirstVenue(): bool
    {
        return self::where('business_profile_id', $this->business_profile_id)
            ->where('id', '!=', $this->id)
            ->count() === 0;
    }

    /**
     * Get worker instructions as array.
     */
    public function getWorkerInstructions(): array
    {
        return [
            'parking' => $this->parking_instructions,
            'entrance' => $this->entrance_instructions,
            'checkin' => $this->checkin_instructions,
            'dress_code' => $this->dress_code,
            'dress_code_label' => $this->dress_code_label,
            'equipment_provided' => $this->equipment_provided,
            'equipment_required' => $this->equipment_required,
        ];
    }

    /**
     * Get venue settings with defaults.
     */
    public function getSettingsWithDefaults(): array
    {
        $defaults = [
            'notify_budget_threshold' => 80,
            'allow_overtime' => false,
            'max_workers_per_shift' => 10,
            'min_notice_hours' => 4,
            'cancellation_policy' => 'standard',
        ];

        return array_merge($defaults, $this->settings ?? []);
    }
}
