<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftPayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shift_assignment_id',
        'worker_id',
        'business_id',
        'amount_gross',
        'platform_fee',
        'amount_net',
        'escrow_held_at',
        'released_at',
        'payout_initiated_at',
        'payout_completed_at',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'status',
        'disputed',
        'dispute_reason',
        'disputed_at',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount_gross' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'amount_net' => 'decimal:2',
        'escrow_held_at' => 'datetime',
        'released_at' => 'datetime',
        'payout_initiated_at' => 'datetime',
        'payout_completed_at' => 'datetime',
        'disputed' => 'boolean',
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the shift assignment.
     */
    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }

    /**
     * Get the worker receiving payment.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business paying.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Check if payment is in escrow.
     */
    public function isInEscrow()
    {
        return $this->status === 'in_escrow';
    }

    /**
     * Check if payment is released.
     */
    public function isReleased()
    {
        return $this->status === 'released';
    }

    /**
     * Check if payment is paid out.
     */
    public function isPaidOut()
    {
        return $this->status === 'paid_out';
    }

    /**
     * Check if payment is disputed.
     */
    public function isDisputed()
    {
        return $this->disputed;
    }

    /**
     * Check if ready for instant payout (15 minutes after shift completion).
     */
    public function isReadyForPayout()
    {
        return $this->status === 'released'
            && $this->released_at
            && $this->released_at->addMinutes(15)->isPast()
            && !$this->disputed;
    }

    /**
     * Mark as held in escrow.
     */
    public function holdInEscrow()
    {
        $this->update([
            'status' => 'in_escrow',
            'escrow_held_at' => now()
        ]);
    }

    /**
     * Release from escrow.
     */
    public function release()
    {
        $this->update([
            'status' => 'released',
            'released_at' => now()
        ]);
    }

    /**
     * Mark payout as initiated.
     */
    public function initiatePayout($transferId)
    {
        $this->update([
            'stripe_transfer_id' => $transferId,
            'payout_initiated_at' => now()
        ]);
    }

    /**
     * Mark payout as completed.
     */
    public function completePayout()
    {
        $this->update([
            'status' => 'paid_out',
            'payout_completed_at' => now()
        ]);
    }

    /**
     * File a dispute.
     */
    public function dispute($reason)
    {
        $this->update([
            'disputed' => true,
            'dispute_reason' => $reason,
            'disputed_at' => now(),
            'status' => 'disputed'
        ]);
    }

    /**
     * Resolve dispute.
     */
    public function resolveDispute()
    {
        $this->update([
            'disputed' => false,
            'resolved_at' => now(),
            'status' => 'released'
        ]);
    }

    /**
     * Scope for payments ready for instant payout.
     */
    public function scopeReadyForPayout($query)
    {
        return $query->where('status', 'released')
            ->where('disputed', false)
            ->where('released_at', '<=', now()->subMinutes(15))
            ->whereNull('payout_completed_at');
    }

    /**
     * Scope for payments in escrow.
     */
    public function scopeInEscrow($query)
    {
        return $query->where('status', 'in_escrow');
    }

    /**
     * Scope for disputed payments.
     */
    public function scopeDisputed($query)
    {
        return $query->where('disputed', true);
    }
}
