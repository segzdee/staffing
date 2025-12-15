<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model for tracking worker conversions to direct hire.
 * BIZ-010: Direct Hire & Conversion
 */
class WorkerConversion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'worker_id',
        'business_id',
        'total_hours_worked',
        'total_shifts_completed',
        'conversion_fee_cents',
        'conversion_fee_tier',
        'status',
        'hire_intent_submitted_at',
        'hire_intent_notes',
        'worker_notified_at',
        'worker_accepted',
        'worker_accepted_at',
        'worker_response_notes',
        'payment_completed_at',
        'payment_method',
        'payment_transaction_id',
        'conversion_completed_at',
        'non_solicitation_expires_at',
        'is_active',
    ];

    protected $casts = [
        'total_hours_worked' => 'decimal:2',
        'total_shifts_completed' => 'integer',
        'conversion_fee_cents' => 'integer',
        'worker_accepted' => 'boolean',
        'is_active' => 'boolean',
        'hire_intent_submitted_at' => 'datetime',
        'worker_notified_at' => 'datetime',
        'worker_accepted_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'conversion_completed_at' => 'datetime',
        'non_solicitation_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the worker being hired.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business hiring the worker.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get conversion fee in dollars.
     *
     * @return float
     */
    public function getConversionFeeDollarsAttribute()
    {
        return $this->conversion_fee_cents / 100;
    }

    /**
     * Check if non-solicitation period is still active.
     *
     * @return bool
     */
    public function isNonSolicitationActive()
    {
        if (!$this->non_solicitation_expires_at) {
            return false;
        }

        return now()->lt($this->non_solicitation_expires_at);
    }

    /**
     * Get days remaining in non-solicitation period.
     *
     * @return int|null
     */
    public function getNonSolicitationDaysRemaining()
    {
        if (!$this->non_solicitation_expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->non_solicitation_expires_at, false));
    }

    /**
     * Scope to get active conversions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get pending conversions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get completed conversions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get conversions within non-solicitation period.
     */
    public function scopeWithinNonSolicitation($query)
    {
        return $query->where('non_solicitation_expires_at', '>', now());
    }
}
