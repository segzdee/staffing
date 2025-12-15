<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCancellationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_profile_id',
        'shift_id',
        'cancelled_by_user_id',
        'cancellation_type',
        'cancellation_reason',
        'hours_before_shift',
        'shift_start_time',
        'shift_end_time',
        'shift_pay_rate',
        'shift_role',
        'cancellation_fee',
        'fee_waived',
        'fee_waiver_reason',
        'total_cancellations_at_time',
        'cancellations_last_30_days_at_time',
        'cancellation_rate_at_time',
        'warning_issued',
        'escrow_increased',
        'credit_suspended',
        'action_taken_at',
    ];

    protected $casts = [
        'hours_before_shift' => 'integer',
        'shift_start_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'shift_pay_rate' => 'integer',
        'cancellation_fee' => 'integer',
        'fee_waived' => 'boolean',
        'total_cancellations_at_time' => 'integer',
        'cancellations_last_30_days_at_time' => 'integer',
        'cancellation_rate_at_time' => 'decimal:2',
        'warning_issued' => 'boolean',
        'escrow_increased' => 'boolean',
        'credit_suspended' => 'boolean',
        'action_taken_at' => 'datetime',
    ];

    /**
     * Get the business profile that owns this log.
     */
    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    /**
     * Get the shift that was cancelled.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the user who cancelled the shift.
     */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * Check if this was a late cancellation.
     */
    public function isLateCancellation()
    {
        return in_array($this->cancellation_type, ['late', 'no_show']);
    }

    /**
     * Scope to get late cancellations only.
     */
    public function scopeLateCancellations($query)
    {
        return $query->whereIn('cancellation_type', ['late', 'no_show']);
    }

    /**
     * Scope to get cancellations within date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get cancellations for a business.
     */
    public function scopeForBusiness($query, $businessProfileId)
    {
        return $query->where('business_profile_id', $businessProfileId);
    }
}
