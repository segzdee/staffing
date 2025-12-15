<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Worker Penalty Model
 *
 * Tracks penalties issued to workers for various violations including:
 * - No-shows (failing to show up for confirmed shifts)
 * - Late cancellations (cancelling within policy window)
 * - Misconduct (inappropriate behavior during shifts)
 * - Property damage (damage to business property)
 *
 * @property int $id
 * @property int $worker_id
 * @property int|null $shift_id
 * @property int|null $business_id
 * @property int|null $issued_by_admin_id
 * @property string $penalty_type
 * @property float $penalty_amount
 * @property string $reason
 * @property string|null $evidence_notes
 * @property string $status
 * @property bool $is_paid
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $payment_method
 * @property \Illuminate\Support\Carbon|null $issued_at
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class WorkerPenalty extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'worker_id',
        'shift_id',
        'business_id',
        'issued_by_admin_id',
        'penalty_type',
        'penalty_amount',
        'reason',
        'evidence_notes',
        'status',
        'is_paid',
        'paid_at',
        'payment_method',
        'issued_at',
        'due_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'issued_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($penalty) {
            if (!$penalty->issued_at) {
                $penalty->issued_at = now();
            }
            if (!$penalty->due_date) {
                // Default to 7 days from issue
                $penalty->due_date = now()->addDays(7);
            }
        });
    }

    /**
     * Get the worker who received the penalty.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the shift related to this penalty.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Get the business that reported the penalty.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the admin who issued the penalty.
     */
    public function issuedByAdmin()
    {
        return $this->belongsTo(User::class, 'issued_by_admin_id');
    }

    /**
     * Get the appeals for this penalty.
     */
    public function appeals()
    {
        return $this->hasMany(PenaltyAppeal::class, 'penalty_id');
    }

    /**
     * Get the active appeal for this penalty.
     */
    public function activeAppeal()
    {
        return $this->hasOne(PenaltyAppeal::class, 'penalty_id')
            ->whereIn('status', ['pending', 'under_review'])
            ->latest();
    }

    /**
     * Check if the penalty can be appealed.
     */
    public function canBeAppealed()
    {
        // Can only appeal if status is pending or active
        if (!in_array($this->status, ['pending', 'active'])) {
            return false;
        }

        // Can only appeal within 14 days of issue
        if ($this->issued_at && $this->issued_at->addDays(14)->isPast()) {
            return false;
        }

        // Can't appeal if already appealed
        if ($this->status === 'appealed') {
            return false;
        }

        return true;
    }

    /**
     * Check if the penalty is overdue.
     */
    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_paid;
    }

    /**
     * Mark penalty as appealed.
     */
    public function markAsAppealed()
    {
        $this->update(['status' => 'appealed']);
    }

    /**
     * Mark penalty as paid.
     */
    public function markAsPaid($paymentMethod = 'deducted')
    {
        $this->update([
            'is_paid' => true,
            'paid_at' => now(),
            'payment_method' => $paymentMethod,
            'status' => 'paid',
        ]);
    }

    /**
     * Approve appeal and waive penalty.
     */
    public function approveAppeal()
    {
        $this->update(['status' => 'appeal_approved']);
    }

    /**
     * Reject appeal and keep penalty active.
     */
    public function rejectAppeal()
    {
        $this->update(['status' => 'appeal_rejected']);
    }

    /**
     * Waive the penalty (admin discretion).
     */
    public function waive()
    {
        $this->update([
            'status' => 'waived',
            'is_paid' => true,
            'paid_at' => now(),
            'payment_method' => 'waived',
        ]);
    }

    /**
     * Scope for active penalties.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending penalties.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for unpaid penalties.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope for overdue penalties.
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_paid', false)
            ->where('due_date', '<', now());
    }

    /**
     * Scope for penalties by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('penalty_type', $type);
    }

    /**
     * Scope for penalties that can be appealed.
     */
    public function scopeAppealable($query)
    {
        return $query->whereIn('status', ['pending', 'active'])
            ->where('issued_at', '>', now()->subDays(14));
    }
}
