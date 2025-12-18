<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $business_id
 * @property int|null $venue_id
 * @property bool $posted_by_agent
 * @property int|null $agent_id
 * @property int|null $agency_client_id
 * @property int|null $posted_by_agency_id
 * @property bool $allow_agencies
 * @property int|null $template_id
 * @property string $title
 * @property string $description
 * @property string|null $role_type
 * @property string $industry
 * @property string $location_address
 * @property string $location_city
 * @property string $location_state
 * @property string $location_country
 * @property numeric|null $location_lat
 * @property numeric|null $location_lng
 * @property int $geofence_radius
 * @property int $early_clockin_minutes
 * @property int $late_grace_minutes
 * @property \Illuminate\Support\Carbon $shift_date
 * @property \Illuminate\Support\Carbon $start_time
 * @property \Illuminate\Support\Carbon $end_time
 * @property string|null $start_datetime
 * @property string|null $end_datetime
 * @property numeric $duration_hours
 * @property numeric $minimum_shift_duration
 * @property numeric $maximum_shift_duration
 * @property numeric $required_rest_hours
 * @property \Money\Money|null $base_rate
 * @property numeric|null $dynamic_rate
 * @property \Money\Money|null $final_rate
 * @property \Money\Money|null $minimum_wage
 * @property \Money\Money|null $base_worker_pay
 * @property numeric $platform_fee_rate
 * @property \Money\Money|null $platform_fee_amount
 * @property numeric $vat_rate
 * @property \Money\Money|null $vat_amount
 * @property \Money\Money|null $total_business_cost
 * @property \Money\Money|null $escrow_amount
 * @property numeric $contingency_buffer_rate
 * @property numeric $surge_multiplier
 * @property numeric $time_surge
 * @property numeric $demand_surge
 * @property numeric $event_surge
 * @property bool $is_public_holiday
 * @property bool $is_night_shift
 * @property bool $is_weekend
 * @property string $status
 * @property string $urgency_level
 * @property bool $requires_overtime_approval
 * @property bool $has_disputes
 * @property bool $auto_approval_eligible
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $priority_notification_sent_at
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $first_worker_clocked_in_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $last_worker_clocked_out_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property int|null $verified_by
 * @property \Illuminate\Support\Carbon|null $auto_approved_at
 * @property int|null $cancelled_by
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $cancellation_type
 * @property \Money\Money|null $cancellation_penalty_amount
 * @property \Money\Money|null $worker_compensation_amount
 * @property int $required_workers
 * @property int $filled_workers
 * @property array<array-key, mixed>|null $requirements
 * @property array<array-key, mixed>|null $required_skills
 * @property array<array-key, mixed>|null $required_certifications
 * @property string|null $dress_code
 * @property string|null $parking_info
 * @property string|null $break_info
 * @property string|null $special_instructions
 * @property bool $in_market
 * @property bool $is_demo
 * @property \Illuminate\Support\Carbon|null $market_posted_at
 * @property bool $instant_claim_enabled
 * @property int $market_views
 * @property int $market_applications
 * @property string|null $demo_business_name
 * @property int $application_count
 * @property int $view_count
 * @property \Illuminate\Support\Carbon|null $first_application_at
 * @property \Illuminate\Support\Carbon|null $last_application_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\AgencyClient|null $agencyClient
 * @property-read \App\Models\User|null $agent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftApplication> $applications
 * @property-read int|null $applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $assignedWorkers
 * @property-read int|null $assigned_workers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftAssignment> $assignments
 * @property-read int|null $assignments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \App\Models\User $business
 * @property-read \App\Models\BusinessProfile|null $businessProfile
 * @property-read mixed $effective_rate
 * @property-read mixed $fill_percentage
 * @property-read mixed $spots_remaining
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPayment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User|null $postedByAgency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rating> $ratings
 * @property-read int|null $ratings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftSwap> $swapRequests
 * @property-read int|null $swap_requests_count
 * @property-read \App\Models\ShiftTemplate|null $template
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift byIndustry($industry)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift demoShifts()
 * @method static \Database\Factories\ShiftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift inMarket()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift nearby($lat, $lng, $radius = 25)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift open()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift realShifts()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAgencyClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAllowAgencies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereApplicationCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAutoApprovalEligible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereAutoApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBaseRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBaseWorkerPay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBreakInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCancellationPenaltyAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCancellationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCancelledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereContingencyBufferRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDemandSurge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDemoBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDressCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDurationHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDynamicRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEarlyClockinMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEscrowAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEventSurge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFilledWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFinalRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFirstApplicationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereFirstWorkerClockedInAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereGeofenceRadius($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereHasDisputes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereInMarket($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIndustry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereInstantClaimEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsDemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsNightShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsPublicHoliday($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereIsWeekend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLastApplicationAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLastWorkerClockedOutAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLateGraceMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereLocationState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMarketApplications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMarketPostedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMarketViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMaximumShiftDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMinimumShiftDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereMinimumWage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereParkingInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift wherePlatformFeeAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift wherePlatformFeeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift wherePostedByAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift wherePostedByAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift wherePriorityNotificationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequiredCertifications($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequiredRestHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequiredSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequiredWorkers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRequiresOvertimeApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereRoleType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereSpecialInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartDatetime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereSurgeMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereTimeSurge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereTotalBusinessCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUrgencyLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereVatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereVatRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereVenueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereViewCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereWorkerCompensationAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Shift extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'venue_id',
        'title',
        'description',
        'industry',
        'location_address',
        'location_city',
        'location_state',
        'location_country',
        'location_lat',
        'location_lng',
        'shift_date',
        'start_time',
        'end_time',
        'duration_hours',
        'base_rate',
        'dynamic_rate',
        'final_rate',
        'urgency_level',
        'status',
        'required_workers',
        'filled_workers',
        'requirements',
        'dress_code',
        'parking_info',
        'break_info',
        'special_instructions',
        'posted_by_agent',
        'agent_id',
        'allow_agencies',

        // Business logic fields (SL-001)
        'role_type',
        'required_skills',
        'required_certifications',
        'minimum_shift_duration',
        'maximum_shift_duration',
        'required_rest_hours',
        'minimum_wage',
        'base_worker_pay',
        'platform_fee_rate',
        'platform_fee_amount',
        'vat_rate',
        'vat_amount',
        'total_business_cost',
        'escrow_amount',
        'contingency_buffer_rate',

        // Surge pricing (SL-008)
        'surge_multiplier',
        'time_surge',
        'demand_surge',
        'event_surge',
        'is_public_holiday',
        'is_night_shift',
        'is_weekend',

        // Clock-in verification (SL-005)
        'geofence_radius',
        'early_clockin_minutes',
        'late_grace_minutes',

        // Lifecycle timestamps
        'confirmed_at',
        'priority_notification_sent_at',
        'started_at',
        'first_worker_clocked_in_at',
        'completed_at',
        'last_worker_clocked_out_at',
        'verified_at',
        'verified_by',
        'auto_approved_at',

        // Cancellation tracking (SL-009, SL-010)
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancellation_type',
        'cancellation_penalty_amount',
        'worker_compensation_amount',

        // Status flags
        'requires_overtime_approval',
        'has_disputes',
        'auto_approval_eligible',

        // Application tracking
        'application_count',
        'view_count',
        'first_application_at',
        'last_application_at',

        // Market fields
        'in_market',
        'is_demo',
        'market_posted_at',
        'instant_claim_enabled',
        'market_views',
        'market_applications',
        'agency_client_id',
        'posted_by_agency_id',

        // SAF-005: Health protocol fields
        'requires_health_declaration',
        'requires_vaccination',
        'required_vaccinations',
        'ppe_requirements',
        'max_capacity',
        'health_protocols_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shift_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'duration_hours' => 'decimal:2',
        'dynamic_rate' => 'decimal:2',
        'required_workers' => 'integer',
        'filled_workers' => 'integer',
        'requirements' => 'array',
        'posted_by_agent' => 'boolean',
        'location_lat' => 'decimal:8',
        'location_lng' => 'decimal:8',

        // Business logic casts
        'required_skills' => 'array',
        'required_certifications' => 'array',
        'minimum_shift_duration' => 'decimal:2',
        'maximum_shift_duration' => 'decimal:2',
        'required_rest_hours' => 'decimal:2',

        // Money casts (stored as cents in database)
        'base_rate' => MoneyCast::class,
        'final_rate' => MoneyCast::class,
        'minimum_wage' => MoneyCast::class,
        'base_worker_pay' => MoneyCast::class,
        'platform_fee_amount' => MoneyCast::class,
        'vat_amount' => MoneyCast::class,
        'total_business_cost' => MoneyCast::class,
        'escrow_amount' => MoneyCast::class,
        'cancellation_penalty_amount' => MoneyCast::class,
        'worker_compensation_amount' => MoneyCast::class,

        // Rates (still decimal)
        'platform_fee_rate' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'contingency_buffer_rate' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'time_surge' => 'decimal:2',
        'demand_surge' => 'decimal:2',
        'event_surge' => 'decimal:2',

        // Boolean casts
        'is_public_holiday' => 'boolean',
        'is_night_shift' => 'boolean',
        'is_weekend' => 'boolean',
        'requires_overtime_approval' => 'boolean',
        'has_disputes' => 'boolean',
        'auto_approval_eligible' => 'boolean',
        'allow_agencies' => 'boolean',

        // Timestamp casts
        'confirmed_at' => 'datetime',
        'priority_notification_sent_at' => 'datetime',
        'started_at' => 'datetime',
        'first_worker_clocked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_worker_clocked_out_at' => 'datetime',
        'verified_at' => 'datetime',
        'auto_approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'first_application_at' => 'datetime',
        'last_application_at' => 'datetime',

        // Market field casts
        'in_market' => 'boolean',
        'is_demo' => 'boolean',
        'instant_claim_enabled' => 'boolean',
        'market_posted_at' => 'datetime',
        'market_views' => 'integer',
        'market_applications' => 'integer',

        // SAF-005: Health protocol casts
        'requires_health_declaration' => 'boolean',
        'requires_vaccination' => 'boolean',
        'required_vaccinations' => 'array',
        'ppe_requirements' => 'array',
        'max_capacity' => 'integer',
    ];

    /**
     * Get the business that posted the shift.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the AI agent that posted the shift (if any).
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get all applications for this shift.
     */
    public function applications()
    {
        return $this->hasMany(ShiftApplication::class);
    }

    /**
     * Get all assignments for this shift.
     */
    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Get all attachments for this shift.
     */
    public function attachments()
    {
        return $this->hasMany(ShiftAttachment::class);
    }

    /**
     * Get all invitations for this shift.
     */
    public function invitations()
    {
        return $this->hasMany(ShiftInvitation::class);
    }

    /**
     * SAF-005: Get all health declarations for this shift.
     */
    public function healthDeclarations()
    {
        return $this->hasMany(HealthDeclaration::class);
    }

    /**
     * SAF-003: Get certification requirements for this shift.
     */
    public function certificationRequirements()
    {
        return $this->hasMany(ShiftCertificationRequirement::class);
    }

    /**
     * SAF-003: Get required safety certifications for this shift.
     */
    public function requiredSafetyCertifications()
    {
        return $this->belongsToMany(SafetyCertification::class, 'shift_certification_requirements')
            ->withPivot('is_mandatory')
            ->withTimestamps();
    }

    /**
     * SAF-003: Get mandatory certification requirements.
     */
    public function mandatoryCertificationRequirements()
    {
        return $this->certificationRequirements()->where('is_mandatory', true);
    }

    /**
     * Get pending applications.
     */
    public function pendingApplications()
    {
        return $this->applications()->where('status', 'pending');
    }

    /**
     * Get assigned workers.
     */
    public function assignedWorkers()
    {
        return $this->belongsToMany(User::class, 'shift_assignments', 'shift_id', 'worker_id')
            ->withPivot('status', 'check_in_time', 'check_out_time', 'hours_worked')
            ->withTimestamps();
    }

    /**
     * Check if shift is full.
     */
    public function isFull()
    {
        return $this->filled_workers >= $this->required_workers;
    }

    /**
     * Check if shift is open for applications.
     */
    public function isOpen()
    {
        return $this->status === 'open' && ! $this->isFull();
    }

    /**
     * Check if shift is urgent (starts in less than 24 hours).
     */
    public function isUrgent()
    {
        return $this->shift_date->diffInHours(now()) < 24;
    }

    /**
     * Scope for open shifts.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where('start_datetime', '>', now())
            ->whereColumn('filled_workers', '<', 'required_workers');
    }

    /**
     * Scope for upcoming shifts.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', now()->toDateString())
            ->orderBy('start_datetime', 'asc');
    }

    /**
     * Scope for shifts by industry.
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope for shifts by location.
     */
    public function scopeNearby($query, $lat, $lng, $radius = 25)
    {
        // Haversine formula for nearby locations (radius in miles)
        // Using whereRaw instead of havingRaw for SQLite compatibility
        return $query->selectRaw('
            *,
            ( 3959 * acos( cos( radians(?) ) *
              cos( radians( location_lat ) ) *
              cos( radians( location_lng ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( location_lat ) ) )
            ) AS distance
        ', [$lat, $lng, $lat])
            ->whereRaw('
            ( 3959 * acos( cos( radians(?) ) *
              cos( radians( location_lat ) ) *
              cos( radians( location_lng ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( location_lat ) ) )
            ) < ?
        ', [$lat, $lng, $lat, $radius])
            ->orderBy('distance');
    }

    // =========================================
    // SL-001: Cost Calculation Methods
    // =========================================

    /**
     * Calculate and update all financial fields for the shift.
     * Formula: ((Hourly Rate × Hours × Workers) + Platform Fee + VAT) + 5% Buffer
     */
    /**
     * Calculate and update all financial fields for the shift.
     * Delegates to ShiftPricingService.
     */
    public function calculateCosts()
    {
        $pricingService = app(\App\Services\ShiftPricingService::class);
        $pricingService->calculateCosts($this);
        $this->save();

        return $this;
    }

    /**
     * Get the effective hourly rate (base + surge).
     */
    public function getEffectiveHourlyRate()
    {
        return $this->final_rate ?? $this->base_rate;
    }

    // =========================================
    // SL-008: Surge Pricing Calculations
    // =========================================

    /**
     * Calculate and apply surge pricing multipliers.
     * Formula: Base Rate × (Time Surge + Demand Surge + Event Surge)
     */
    /**
     * Calculate and apply surge pricing multipliers.
     * Delegates to ShiftPricingService.
     */
    public function calculateSurge()
    {
        $pricingService = app(\App\Services\ShiftPricingService::class);
        $pricingService->calculateSurge($this);

        return $this;
    }

    /**
     * Calculate demand-based surge (high application volume).
     */
    protected function calculateDemandSurge()
    {
        // TODO: Implement demand calculation based on recent application patterns
        // For now, return the stored value or 0
        return $this->demand_surge ?? 0.0;
    }

    /**
     * Calculate event-based surge (special circumstances).
     */
    protected function calculateEventSurge()
    {
        // TODO: Implement event detection (sports events, festivals, etc.)
        // For now, return the stored value or 0
        return $this->event_surge ?? 0.0;
    }

    // =========================================
    // SL-009/SL-010: Cancellation Logic
    // =========================================

    /**
     * Calculate cancellation penalty based on timing.
     *
     * Business cancellation:
     * - >72 hours: 0% penalty
     * - 48-72 hours: 25% penalty
     * - 24-48 hours: 50% penalty
     * - 12-24 hours: 75% penalty
     * - <12 hours: 100% penalty
     *
     * Worker cancellation:
     * - >48 hours: Warning only
     * - 24-48 hours: First strike
     * - <24 hours: Second strike + suspension risk
     */
    public function calculateCancellationPenalty($cancelledBy = 'business')
    {
        $hoursUntilShift = now()->diffInHours($this->shift_date->setTimeFromTimeString($this->start_time));

        if ($cancelledBy === 'business') {
            if ($hoursUntilShift >= 72) {
                $penaltyRate = config('overtimestaff.cancellation.business.penalty_72h', 0.00);
            } elseif ($hoursUntilShift >= 48) {
                $penaltyRate = config('overtimestaff.cancellation.business.penalty_48h', 0.25);
            } elseif ($hoursUntilShift >= 24) {
                $penaltyRate = config('overtimestaff.cancellation.business.penalty_24h', 0.50);
            } elseif ($hoursUntilShift >= 12) {
                $penaltyRate = config('overtimestaff.cancellation.business.penalty_12h', 0.75);
            } else {
                $penaltyRate = config('overtimestaff.cancellation.business.penalty_0h', 1.00);
            }

            $this->cancellation_penalty_amount = $this->escrow_amount * $penaltyRate;

            // Worker compensation (workers get % of penalty if <24 hours)
            if ($hoursUntilShift < 24) {
                $compensationShare = config('overtimestaff.cancellation.worker_compensation_share', 0.50);
                $this->worker_compensation_amount = $this->cancellation_penalty_amount * $compensationShare;
            }
        }

        return $this;
    }

    /**
     * Cancel the shift with penalty calculation.
     */
    public function cancel($reason, $cancelledBy, $cancelledByUserId)
    {
        $this->calculateCancellationPenalty($cancelledBy === 'business' ? 'business' : 'worker');

        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancelled_by = $cancelledByUserId;
        $this->cancellation_reason = $reason;
        $this->cancellation_type = $cancelledBy;

        $this->save();

        return $this;
    }

    /**
     * Cancel shift by worker with reliability impact.
     * SL-010: Worker Cancellation Logic
     */
    public function cancelByWorker(ShiftAssignment $assignment, array $cancellationData = []): array
    {
        $service = app(\App\Services\WorkerCancellationService::class);

        return $service->cancelByWorker($assignment, $cancellationData);
    }

    // =========================================
    // Lifecycle Status Methods
    // =========================================

    /**
     * Check if shift is confirmed (all workers confirmed).
     */
    public function isConfirmed()
    {
        return ! is_null($this->confirmed_at);
    }

    /**
     * Check if shift has started.
     */
    public function hasStarted()
    {
        return ! is_null($this->started_at);
    }

    /**
     * Check if shift is completed.
     */
    public function isCompleted()
    {
        return ! is_null($this->completed_at);
    }

    /**
     * Check if shift is verified by business.
     */
    public function isVerified()
    {
        return ! is_null($this->verified_at) || ! is_null($this->auto_approved_at);
    }

    /**
     * Check if shift is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if auto-approval deadline has passed (72 hours after completion).
     */
    public function shouldAutoApprove()
    {
        if ($this->isCompleted() && ! $this->isVerified() && $this->auto_approval_eligible) {
            $hoursElapsed = $this->completed_at->diffInHours(now());

            return $hoursElapsed >= 72;
        }

        return false;
    }

    /**
     * Auto-approve the shift if eligible.
     */
    public function autoApprove()
    {
        if ($this->shouldAutoApprove()) {
            $this->auto_approved_at = now();
            $this->verified_at = now();
            $this->save();

            return true;
        }

        return false;
    }

    // =========================================
    // Live Market Relationships & Methods
    // =========================================

    /**
     * Get the agency client this shift is posted for.
     */
    public function agencyClient()
    {
        return $this->belongsTo(AgencyClient::class, 'agency_client_id');
    }

    /**
     * Get the agency that posted this shift.
     */
    public function postedByAgency()
    {
        return $this->belongsTo(User::class, 'posted_by_agency_id');
    }

    /**
     * Scope to get shifts in the live market.
     */
    public function scopeInMarket($query)
    {
        return $query->where('in_market', true)
            ->where('status', 'open')
            ->where('shift_date', '>=', now()->toDateString())
            ->whereColumn('filled_workers', '<', 'required_workers');
    }

    /**
     * Scope to get only real (non-demo) shifts.
     */
    public function scopeRealShifts($query)
    {
        return $query->where('is_demo', false);
    }

    /**
     * Scope to get only demo shifts.
     */
    public function scopeDemoShifts($query)
    {
        return $query->where('is_demo', true);
    }

    /**
     * Get the effective hourly rate with surge applied.
     */
    public function getEffectiveRateAttribute()
    {
        $rateObj = $this->base_rate;
        $rate = 0;

        if (is_numeric($rateObj)) {
            $rate = (float) $rateObj;
        } elseif (is_object($rateObj) && method_exists($rateObj, 'getAmount')) {
            $rate = ((float) $rateObj->getAmount()) / 100;
        }

        return $this->final_rate ?? ($rate * ($this->surge_multiplier ?? 1.0));
    }

    /**
     * Get the number of spots remaining.
     */
    public function getSpotsRemainingAttribute()
    {
        return max(0, $this->required_workers - $this->filled_workers);
    }

    /**
     * Get the fill percentage.
     */
    public function getFillPercentageAttribute()
    {
        if ($this->required_workers == 0) {
            return 0;
        }

        return round(($this->filled_workers / $this->required_workers) * 100);
    }

    // =========================================
    // Live Market Accessors
    // =========================================

    /**
     * Get urgency based on time until shift.
     */
    public function getUrgencyAttribute(): string
    {
        $startDatetime = $this->getStartDatetimeCarbon();
        if (! $startDatetime) {
            return 'open';
        }

        $hoursUntil = now()->diffInHours($startDatetime, false);

        if ($hoursUntil < 0) {
            return 'expired';
        }
        if ($hoursUntil < 4) {
            return 'asap';
        }
        if ($hoursUntil < 12) {
            return 'urgent';
        }
        if ($hoursUntil < 24) {
            return 'soon';
        }

        return 'open';
    }

    /**
     * Get rate color based on market comparison.
     */
    public function getRateColorAttribute(): string
    {
        $avgRate = Cache::remember('market_avg_rate', 300, function () {
            return Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->avg('base_rate') ?? 25;
        });

        $rateObj = $this->base_rate;
        $rate = 0;

        if (is_numeric($rateObj)) {
            $rate = (float) $rateObj;
        } elseif (is_object($rateObj) && method_exists($rateObj, 'getAmount')) {
            $rate = ((float) $rateObj->getAmount()) / 100;
        }

        if (! $rate || $avgRate == 0) {
            return 'gray';
        }

        $diff = (($rate - $avgRate) / $avgRate) * 100;

        if ($diff >= 15) {
            return 'green';
        }
        if ($diff >= 5) {
            return 'blue';
        }
        if ($diff >= -5) {
            return 'gray';
        }

        return 'orange';
    }

    /**
     * Get rate change percentage compared to market average.
     */
    public function getRateChangeAttribute(): float
    {
        $avgRate = Cache::remember('market_avg_rate', 300, function () {
            $avgCents = Shift::where('status', 'open')
                ->where('shift_date', '>=', now())
                ->avg('base_rate');

            return $avgCents ? ($avgCents / 100) : 25;
        });

        $rateObj = $this->final_rate ?? $this->base_rate;
        $rate = 0;

        if (is_numeric($rateObj)) {
            $rate = (float) $rateObj;
        } elseif (is_object($rateObj) && method_exists($rateObj, 'getAmount')) {
            $rate = ((float) $rateObj->getAmount()) / 100;
        }

        if (! $rate || $avgRate == 0) {
            return 0;
        }

        return round((($rate - $avgRate) / $avgRate) * 100, 1);
    }

    /**
     * Get time away formatted for display.
     */
    public function getTimeAwayAttribute(): string
    {
        $startDatetime = $this->getStartDatetimeCarbon();
        if (! $startDatetime) {
            return 'TBD';
        }

        return $startDatetime->diffForHumans(['parts' => 1, 'short' => true]);
    }

    /**
     * Get formatted date for display.
     */
    public function getFormattedDateAttribute(): string
    {
        $startDatetime = $this->getStartDatetimeCarbon();
        if (! $startDatetime) {
            return 'TBD';
        }

        return $startDatetime->format('D, M j \u{2022} g:ia');
    }

    /**
     * Get availability color based on spots remaining.
     */
    public function getAvailabilityColorAttribute(): string
    {
        $remaining = $this->required_workers - ($this->filled_workers ?? 0);

        if ($remaining <= 0) {
            return 'red';
        }
        if ($remaining == 1) {
            return 'red';
        }

        $percent = ($remaining / $this->required_workers) * 100;

        if ($percent <= 25) {
            return 'orange';
        }
        if ($percent <= 50) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Get filled spots count.
     */
    public function getFilledAttribute(): int
    {
        return $this->filled_workers ?? 0;
    }

    /**
     * Get business color for avatar display.
     */
    public function getColorAttribute(): string
    {
        $colors = ['blue', 'green', 'purple', 'pink', 'orange'];

        return $colors[($this->business_id ?? 0) % count($colors)];
    }

    /**
     * Check if current user has applied to this shift.
     */
    public function getHasAppliedAttribute(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return $this->applications()
            ->where('worker_id', auth()->id())
            ->exists();
    }

    /**
     * Helper method to get start datetime as Carbon instance.
     */
    protected function getStartDatetimeCarbon(): ?\Carbon\Carbon
    {
        if ($this->start_datetime) {
            return \Carbon\Carbon::parse($this->start_datetime);
        }

        if ($this->shift_date && $this->start_time) {
            $date = $this->shift_date instanceof \Carbon\Carbon
                ? $this->shift_date->format('Y-m-d')
                : $this->shift_date;
            $time = $this->start_time instanceof \Carbon\Carbon
                ? $this->start_time->format('H:i:s')
                : $this->start_time;

            return \Carbon\Carbon::parse("{$date} {$time}");
        }

        return null;
    }

    // =========================================
    // Additional Relationships
    // =========================================

    /**
     * Get all payments for this shift (through assignments).
     */
    public function payments()
    {
        return $this->hasManyThrough(
            ShiftPayment::class,
            ShiftAssignment::class,
            'shift_id',           // Foreign key on shift_assignments
            'shift_assignment_id', // Foreign key on shift_payments
            'id',                 // Local key on shifts
            'id'                  // Local key on shift_assignments
        );
    }

    /**
     * Get the template this shift was created from (if any).
     */
    public function template()
    {
        return $this->belongsTo(ShiftTemplate::class, 'template_id');
    }

    /**
     * Get all swap requests for this shift (through assignments).
     */
    public function swapRequests()
    {
        return $this->hasManyThrough(
            ShiftSwap::class,
            ShiftAssignment::class,
            'shift_id',           // Foreign key on shift_assignments
            'shift_assignment_id', // Foreign key on shift_swaps
            'id',                 // Local key on shifts
            'id'                  // Local key on shift_assignments
        );
    }

    /**
     * QUA-002: Get all audits for this shift.
     */
    public function audits()
    {
        return $this->hasMany(ShiftAudit::class);
    }

    /**
     * Get all ratings for this shift (through assignments).
     */
    public function ratings()
    {
        return $this->hasManyThrough(
            Rating::class,
            ShiftAssignment::class,
            'shift_id',           // Foreign key on shift_assignments
            'shift_assignment_id', // Foreign key on ratings
            'id',                 // Local key on shifts
            'id'                  // Local key on shift_assignments
        );
    }

    /**
     * Get the business profile for this shift.
     */
    public function businessProfile()
    {
        return $this->hasOneThrough(
            BusinessProfile::class,
            User::class,
            'id',           // Foreign key on users
            'user_id',      // Foreign key on business_profiles
            'business_id',  // Local key on shifts
            'id'            // Local key on users
        );
    }

    /**
     * Get the venue for this shift (if any).
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    // =========================================
    // SL-012: Multi-Position Shifts
    // =========================================

    /**
     * Get all positions for this shift.
     */
    public function positions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ShiftPosition::class);
    }

    /**
     * Check if this is a multi-position shift (has more than one position defined).
     */
    public function isMultiPosition(): bool
    {
        return $this->positions()->count() > 1;
    }

    /**
     * Check if this shift has any positions defined.
     */
    public function hasPositions(): bool
    {
        return $this->positions()->count() > 0;
    }

    /**
     * Get available positions (not fully filled) for this shift.
     */
    public function availablePositions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->positions()
            ->whereIn('status', [ShiftPosition::STATUS_OPEN, ShiftPosition::STATUS_PARTIALLY_FILLED])
            ->whereColumn('filled_workers', '<', 'required_workers');
    }

    /**
     * Get the total number of workers required across all positions.
     */
    public function getTotalPositionWorkersRequired(): int
    {
        return $this->positions()
            ->where('status', '!=', ShiftPosition::STATUS_CANCELLED)
            ->sum('required_workers');
    }

    /**
     * Get the total number of workers filled across all positions.
     */
    public function getTotalPositionWorkersFilled(): int
    {
        return $this->positions()
            ->where('status', '!=', ShiftPosition::STATUS_CANCELLED)
            ->sum('filled_workers');
    }

    /**
     * Get a summary of positions fill status.
     */
    public function getPositionsSummary(): array
    {
        $positions = $this->positions;

        return [
            'total' => $positions->count(),
            'open' => $positions->where('status', ShiftPosition::STATUS_OPEN)->count(),
            'partially_filled' => $positions->where('status', ShiftPosition::STATUS_PARTIALLY_FILLED)->count(),
            'filled' => $positions->where('status', ShiftPosition::STATUS_FILLED)->count(),
            'cancelled' => $positions->where('status', ShiftPosition::STATUS_CANCELLED)->count(),
        ];
    }

    // =========================================
    // SL-006: Break Enforcement Methods
    // =========================================

    /**
     * Check if shift requires a mandatory break based on jurisdiction and duration.
     *
     * Break requirements by jurisdiction:
     * - EU/Malta: 30-minute break after 6 hours
     * - US: Varies by state
     * - Default: 30-minute break after 6 hours
     */
    public function requiresBreak(): bool
    {
        // Check if shift duration is 6 hours or more
        if ($this->duration_hours < 6) {
            return false;
        }

        // Get jurisdiction from shift location
        $jurisdiction = $this->getJurisdiction();

        // EU/Malta rules: 30-minute break after 6 hours
        if (in_array($jurisdiction, ['MT', 'EU'])) {
            return $this->duration_hours >= 6;
        }

        // Default rule: 30-minute break after 6 hours
        return $this->duration_hours >= 6;
    }

    /**
     * Get the minimum break duration required (in minutes).
     */
    public function getRequiredBreakMinutes(): int
    {
        if (! $this->requiresBreak()) {
            return 0;
        }

        $jurisdiction = $this->getJurisdiction();

        // EU/Malta: 30 minutes minimum
        if (in_array($jurisdiction, ['MT', 'EU'])) {
            return 30;
        }

        // Default: 30 minutes
        return 30;
    }

    /**
     * Get jurisdiction code from shift location.
     */
    protected function getJurisdiction(): string
    {
        // Map country codes to jurisdiction
        $jurisdictionMap = [
            'Malta' => 'MT',
            'MT' => 'MT',
            // EU countries
            'Austria' => 'EU',
            'Belgium' => 'EU',
            'Bulgaria' => 'EU',
            'Croatia' => 'EU',
            'Cyprus' => 'EU',
            'Czech Republic' => 'EU',
            'Denmark' => 'EU',
            'Estonia' => 'EU',
            'Finland' => 'EU',
            'France' => 'EU',
            'Germany' => 'EU',
            'Greece' => 'EU',
            'Hungary' => 'EU',
            'Ireland' => 'EU',
            'Italy' => 'EU',
            'Latvia' => 'EU',
            'Lithuania' => 'EU',
            'Luxembourg' => 'EU',
            'Netherlands' => 'EU',
            'Poland' => 'EU',
            'Portugal' => 'EU',
            'Romania' => 'EU',
            'Slovakia' => 'EU',
            'Slovenia' => 'EU',
            'Spain' => 'EU',
            'Sweden' => 'EU',
        ];

        return $jurisdictionMap[$this->location_country] ?? 'DEFAULT';
    }

    /**
     * Check break compliance for all assignments in this shift.
     */
    public function checkBreakCompliance(): array
    {
        if (! $this->requiresBreak()) {
            return [
                'requires_break' => false,
                'compliant' => true,
                'assignments' => [],
            ];
        }

        $requiredMinutes = $this->getRequiredBreakMinutes();
        $assignments = $this->assignments()->where('status', 'checked_in')->get();

        $result = [
            'requires_break' => true,
            'required_minutes' => $requiredMinutes,
            'compliant' => true,
            'assignments' => [],
        ];

        foreach ($assignments as $assignment) {
            $assignmentCompliance = $assignment->checkBreakCompliance();
            $result['assignments'][] = [
                'assignment_id' => $assignment->id,
                'worker_id' => $assignment->worker_id,
                'worker_name' => $assignment->worker->name ?? 'Unknown',
                'hours_worked' => $assignment->getHoursWorkedSinceClockIn(),
                'break_taken' => $assignmentCompliance['break_taken'],
                'break_minutes' => $assignmentCompliance['break_minutes'],
                'compliant' => $assignmentCompliance['compliant'],
                'needs_reminder' => $assignmentCompliance['needs_reminder'],
            ];

            if (! $assignmentCompliance['compliant']) {
                $result['compliant'] = false;
            }
        }

        return $result;
    }

    /**
     * Get all assignments that need break reminders.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAssignmentsNeedingBreakReminder()
    {
        if (! $this->requiresBreak()) {
            return collect();
        }

        return $this->assignments()
            ->where('status', 'checked_in')
            ->get()
            ->filter(function ($assignment) {
                $compliance = $assignment->checkBreakCompliance();

                return $compliance['needs_reminder'];
            });
    }
}
