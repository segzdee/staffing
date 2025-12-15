<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_assignment_id
 * @property int $worker_id
 * @property int $business_id
 * @property \Money\Money $amount_gross
 * @property \Money\Money $platform_fee
 * @property numeric|null $vat_amount
 * @property numeric $worker_tax_withheld
 * @property string|null $tax_year
 * @property string|null $tax_quarter
 * @property int $reported_to_tax_authority
 * @property numeric|null $platform_revenue
 * @property numeric|null $payment_processor_fee
 * @property numeric|null $net_platform_revenue
 * @property numeric|null $agency_commission
 * @property numeric|null $worker_amount
 * @property \Money\Money $amount_net
 * @property \Illuminate\Support\Carbon|null $escrow_held_at
 * @property \Illuminate\Support\Carbon|null $released_at
 * @property \Illuminate\Support\Carbon|null $payout_initiated_at
 * @property \Illuminate\Support\Carbon|null $payout_completed_at
 * @property int|null $payout_delay_minutes
 * @property string $payout_speed
 * @property int $early_payout_requested
 * @property numeric|null $early_payout_fee
 * @property int $requires_manual_review
 * @property string|null $manual_review_reason
 * @property string|null $reviewed_at
 * @property int|null $reviewed_by_admin_id
 * @property string|null $internal_notes
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_transfer_id
 * @property string $status
 * @property bool $disputed
 * @property string|null $dispute_reason
 * @property \Illuminate\Support\Carbon|null $disputed_at
 * @property string|null $dispute_filed_by
 * @property string|null $dispute_status
 * @property string|null $dispute_evidence_url
 * @property string|null $dispute_resolution_notes
 * @property numeric|null $dispute_adjustment_amount
 * @property int $is_refunded
 * @property numeric|null $refund_amount
 * @property string|null $refund_reason
 * @property string|null $refunded_at
 * @property string|null $stripe_refund_id
 * @property numeric $adjustment_amount
 * @property string|null $adjustment_notes
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ShiftAssignment $assignment
 * @property-read \App\Models\User $business
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment disputed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment inEscrow()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment readyForPayout()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereAdjustmentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereAdjustmentNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereAgencyCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereAmountGross($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereAmountNet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeAdjustmentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeEvidenceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeFiledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeResolutionNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereDisputedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereEarlyPayoutFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereEarlyPayoutRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereEscrowHeldAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereInternalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereIsRefunded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereManualReviewReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereNetPlatformRevenue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePaymentProcessorFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePayoutCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePayoutDelayMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePayoutInitiatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePayoutSpeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePlatformFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment wherePlatformRevenue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereRefundAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereRefundReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereRefundedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereReleasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereReportedToTaxAuthority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereRequiresManualReview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereReviewedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereShiftAssignmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereStripeRefundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereStripeTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereTaxQuarter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereTaxYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereVatAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereWorkerAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereWorkerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftPayment whereWorkerTaxWithheld($value)
 * @mixin \Eloquent
 */
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
        // Money casts (stored as cents in database)
        'amount_gross' => MoneyCast::class,
        'platform_fee' => MoneyCast::class,
        'amount_net' => MoneyCast::class,

        // Datetime casts
        'escrow_held_at' => 'datetime',
        'released_at' => 'datetime',
        'payout_initiated_at' => 'datetime',
        'payout_completed_at' => 'datetime',
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',

        // Boolean casts
        'disputed' => 'boolean',
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
     * Get the shift via the assignment relationship.
     * Uses a custom accessor for better eager loading support.
     * For eager loading, use: ShiftPayment::with(['assignment.shift'])
     * Then access via: $payment->assignment->shift
     *
     * This convenience relationship supports both:
     * - Direct access: $payment->shift
     * - Eager loading: ShiftPayment::with('shift')
     */
    public function shift()
    {
        return $this->hasOneThrough(
            Shift::class,
            ShiftAssignment::class,
            'id',                 // Foreign key on shift_assignments (primary key)
            'id',                 // Foreign key on shifts (primary key)
            'shift_assignment_id', // Local key on shift_payments
            'shift_id'            // Local key on shift_assignments
        );
    }

    /**
     * Accessor to get shift directly from assignment when loaded.
     * Falls back to the relationship if assignment not loaded.
     */
    public function getShiftAttribute()
    {
        // If assignment is already loaded, get shift from it
        if ($this->relationLoaded('assignment') && $this->assignment) {
            if ($this->assignment->relationLoaded('shift')) {
                return $this->assignment->shift;
            }
        }

        // Otherwise, use the relationship (may trigger additional query)
        return $this->getRelationValue('shift');
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
