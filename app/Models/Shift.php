<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'duration_hours' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'dynamic_rate' => 'decimal:2',
        'final_rate' => 'decimal:2',
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
        'minimum_wage' => 'decimal:2',
        'base_worker_pay' => 'decimal:2',
        'platform_fee_rate' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_business_cost' => 'decimal:2',
        'escrow_amount' => 'decimal:2',
        'contingency_buffer_rate' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'time_surge' => 'decimal:2',
        'demand_surge' => 'decimal:2',
        'event_surge' => 'decimal:2',
        'cancellation_penalty_amount' => 'decimal:2',
        'worker_compensation_amount' => 'decimal:2',

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
        return $this->status === 'open' && !$this->isFull();
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
            ->whereColumn('filled_workers', '<', 'required_workers');
    }

    /**
     * Scope for upcoming shifts.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', now()->toDateString());
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
        return $query->selectRaw("
            *,
            ( 3959 * acos( cos( radians(?) ) *
              cos( radians( location_lat ) ) *
              cos( radians( location_lng ) - radians(?) ) +
              sin( radians(?) ) *
              sin( radians( location_lat ) ) )
            ) AS distance
        ", [$lat, $lng, $lat])
        ->havingRaw('distance < ?', [$radius])
        ->orderBy('distance');
    }

    // =========================================
    // SL-001: Cost Calculation Methods
    // =========================================

    /**
     * Calculate and update all financial fields for the shift.
     * Formula: ((Hourly Rate × Hours × Workers) + Platform Fee + VAT) + 5% Buffer
     */
    public function calculateCosts()
    {
        // Step 1: Calculate base worker pay (before surge)
        $baseHourlyRate = $this->base_rate;
        $hours = $this->duration_hours;
        $workers = $this->required_workers;

        $this->base_worker_pay = $baseHourlyRate * $hours * $workers;

        // Step 2: Apply surge pricing to get final rate
        $this->calculateSurge();
        $finalHourlyRate = $this->final_rate;

        // Step 3: Calculate total worker pay with surge
        $totalWorkerPay = $finalHourlyRate * $hours * $workers;

        // Step 4: Calculate platform fee (default 35%)
        $platformFeeRate = $this->platform_fee_rate ?? 35.00;
        $this->platform_fee_amount = ($totalWorkerPay * $platformFeeRate) / 100;

        // Step 5: Calculate subtotal (worker pay + platform fee)
        $subtotal = $totalWorkerPay + $this->platform_fee_amount;

        // Step 6: Calculate VAT (default 18% in Malta)
        $vatRate = $this->vat_rate ?? 18.00;
        $this->vat_amount = ($subtotal * $vatRate) / 100;

        // Step 7: Calculate total business cost
        $this->total_business_cost = $subtotal + $this->vat_amount;

        // Step 8: Add 5% contingency buffer for escrow
        $bufferRate = $this->contingency_buffer_rate ?? 5.00;
        $this->escrow_amount = $this->total_business_cost * (1 + $bufferRate / 100);

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
    public function calculateSurge()
    {
        $this->time_surge = $this->calculateTimeSurge();
        $this->demand_surge = $this->calculateDemandSurge();
        $this->event_surge = $this->calculateEventSurge();

        // Total surge multiplier
        $this->surge_multiplier = 1.0 + $this->time_surge + $this->demand_surge + $this->event_surge;

        // Apply surge to base rate
        $this->final_rate = $this->base_rate * $this->surge_multiplier;

        return $this;
    }

    /**
     * Calculate time-based surge (urgency, night shift, weekend, holiday).
     */
    protected function calculateTimeSurge()
    {
        $surge = 0.0;

        // Urgent shifts (< 24 hours notice): +50%
        if ($this->urgency_level === 'urgent' || $this->isUrgent()) {
            $surge += 0.50;
        }

        // Night shifts (10 PM - 6 AM): +30%
        if ($this->is_night_shift) {
            $surge += 0.30;
        }

        // Weekends (Saturday/Sunday): +20%
        if ($this->is_weekend) {
            $surge += 0.20;
        }

        // Public holidays: +50%
        if ($this->is_public_holiday) {
            $surge += 0.50;
        }

        return $surge;
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
                $penaltyRate = 0.00; // No penalty
            } elseif ($hoursUntilShift >= 48) {
                $penaltyRate = 0.25; // 25% penalty
            } elseif ($hoursUntilShift >= 24) {
                $penaltyRate = 0.50; // 50% penalty
            } elseif ($hoursUntilShift >= 12) {
                $penaltyRate = 0.75; // 75% penalty
            } else {
                $penaltyRate = 1.00; // 100% penalty
            }

            $this->cancellation_penalty_amount = $this->escrow_amount * $penaltyRate;

            // Worker compensation (workers get 50% of penalty if <24 hours)
            if ($hoursUntilShift < 24) {
                $this->worker_compensation_amount = ($this->escrow_amount * $penaltyRate) * 0.50;
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

    // =========================================
    // Lifecycle Status Methods
    // =========================================

    /**
     * Check if shift is confirmed (all workers confirmed).
     */
    public function isConfirmed()
    {
        return !is_null($this->confirmed_at);
    }

    /**
     * Check if shift has started.
     */
    public function hasStarted()
    {
        return !is_null($this->started_at);
    }

    /**
     * Check if shift is completed.
     */
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }

    /**
     * Check if shift is verified by business.
     */
    public function isVerified()
    {
        return !is_null($this->verified_at) || !is_null($this->auto_approved_at);
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
        if ($this->isCompleted() && !$this->isVerified() && $this->auto_approval_eligible) {
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
        return $this->final_rate ?? ($this->base_rate * ($this->surge_multiplier ?? 1.0));
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
}
