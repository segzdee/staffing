<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Time Adjustment Model
 *
 * Records all adjustments made to time tracking records.
 * Provides audit trail for business adjustments to worker hours.
 */
class TimeAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_assignment_id',
        'adjusted_by',
        'adjustment_type',
        'original_value',
        'new_value',
        'original_timestamp',
        'new_timestamp',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'original_value' => 'decimal:2',
            'new_value' => 'decimal:2',
            'original_timestamp' => 'datetime',
            'new_timestamp' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Adjustment Type Constants
     */
    public const TYPE_HOURS = 'hours';

    public const TYPE_CLOCK_IN = 'clock_in';

    public const TYPE_CLOCK_OUT = 'clock_out';

    public const TYPE_BREAK = 'break';

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Relationship to shift assignment
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Relationship to the user who made the adjustment
     */
    public function adjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Relationship to the user who approved/rejected the adjustment
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for pending adjustments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved adjustments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for rejected adjustments
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope for specific adjustment type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('adjustment_type', $type);
    }

    /**
     * Check if adjustment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if adjustment is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if adjustment is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get the difference value (for display)
     */
    public function getDifferenceAttribute(): ?float
    {
        if ($this->original_value === null || $this->new_value === null) {
            return null;
        }

        return $this->new_value - $this->original_value;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->adjustment_type) {
            self::TYPE_HOURS => 'Hours Worked',
            self::TYPE_CLOCK_IN => 'Clock-In Time',
            self::TYPE_CLOCK_OUT => 'Clock-Out Time',
            self::TYPE_BREAK => 'Break Time',
            default => 'Unknown'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Approve the adjustment
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        // Apply the adjustment to the shift assignment
        $this->applyToAssignment();

        return true;
    }

    /**
     * Reject the adjustment
     */
    public function reject(User $approver, ?string $notes = null): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Apply the adjustment to the shift assignment
     */
    protected function applyToAssignment(): void
    {
        $assignment = $this->shiftAssignment;

        if (! $assignment) {
            return;
        }

        switch ($this->adjustment_type) {
            case self::TYPE_HOURS:
                $assignment->update([
                    'business_adjusted_hours' => $this->new_value,
                    'business_adjustment_reason' => $this->reason,
                    'business_verified_at' => now(),
                    'business_verified_by' => $this->approved_by,
                ]);
                break;

            case self::TYPE_CLOCK_IN:
                $assignment->update([
                    'actual_clock_in' => $this->new_timestamp,
                    'check_in_time' => $this->new_timestamp,
                ]);
                break;

            case self::TYPE_CLOCK_OUT:
                $assignment->update([
                    'actual_clock_out' => $this->new_timestamp,
                    'check_out_time' => $this->new_timestamp,
                ]);
                break;

            case self::TYPE_BREAK:
                $assignment->update([
                    'total_break_minutes' => (int) $this->new_value,
                    'break_deduction_hours' => $this->new_value / 60,
                ]);
                break;
        }
    }

    /**
     * Create an hours adjustment
     */
    public static function adjustHours(
        ShiftAssignment $assignment,
        float $newHours,
        string $reason,
        User $adjustedBy
    ): self {
        return static::create([
            'shift_assignment_id' => $assignment->id,
            'adjusted_by' => $adjustedBy->id,
            'adjustment_type' => self::TYPE_HOURS,
            'original_value' => $assignment->net_hours_worked ?? $assignment->hours_worked,
            'new_value' => $newHours,
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Create a clock-in time adjustment
     */
    public static function adjustClockIn(
        ShiftAssignment $assignment,
        \DateTimeInterface $newTime,
        string $reason,
        User $adjustedBy
    ): self {
        return static::create([
            'shift_assignment_id' => $assignment->id,
            'adjusted_by' => $adjustedBy->id,
            'adjustment_type' => self::TYPE_CLOCK_IN,
            'original_timestamp' => $assignment->actual_clock_in ?? $assignment->check_in_time,
            'new_timestamp' => $newTime,
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Create a clock-out time adjustment
     */
    public static function adjustClockOut(
        ShiftAssignment $assignment,
        \DateTimeInterface $newTime,
        string $reason,
        User $adjustedBy
    ): self {
        return static::create([
            'shift_assignment_id' => $assignment->id,
            'adjusted_by' => $adjustedBy->id,
            'adjustment_type' => self::TYPE_CLOCK_OUT,
            'original_timestamp' => $assignment->actual_clock_out ?? $assignment->check_out_time,
            'new_timestamp' => $newTime,
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Create a break time adjustment
     */
    public static function adjustBreak(
        ShiftAssignment $assignment,
        int $newBreakMinutes,
        string $reason,
        User $adjustedBy
    ): self {
        return static::create([
            'shift_assignment_id' => $assignment->id,
            'adjusted_by' => $adjustedBy->id,
            'adjustment_type' => self::TYPE_BREAK,
            'original_value' => $assignment->total_break_minutes ?? 0,
            'new_value' => $newBreakMinutes,
            'reason' => $reason,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Get adjustments for an assignment
     */
    public static function getForAssignment(ShiftAssignment $assignment)
    {
        return static::where('shift_assignment_id', $assignment->id)
            ->with(['adjuster', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending adjustments for a business
     */
    public static function getPendingForBusiness(User $business)
    {
        return static::pending()
            ->whereHas('shiftAssignment.shift', function ($query) use ($business) {
                $query->where('business_id', $business->id);
            })
            ->with(['shiftAssignment.worker', 'shiftAssignment.shift', 'adjuster'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
