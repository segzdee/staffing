<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PayrollItem Model - FIN-005: Payroll Processing System
 *
 * Represents an individual payment line item within a payroll run.
 *
 * @property int $id
 * @property int $payroll_run_id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int|null $shift_assignment_id
 * @property string $type
 * @property string $description
 * @property float $hours
 * @property float $rate
 * @property float $gross_amount
 * @property float $deductions
 * @property float $tax_withheld
 * @property float $net_amount
 * @property string $status
 * @property string|null $payment_reference
 * @property string|null $stripe_transfer_id
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayrollItem extends Model
{
    use HasFactory;

    // Type constants
    public const TYPE_REGULAR = 'regular';

    public const TYPE_OVERTIME = 'overtime';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REIMBURSEMENT = 'reimbursement';

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payroll_run_id',
        'user_id',
        'shift_id',
        'shift_assignment_id',
        'type',
        'description',
        'hours',
        'rate',
        'gross_amount',
        'deductions',
        'tax_withheld',
        'net_amount',
        'status',
        'payment_reference',
        'stripe_transfer_id',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'tax_withheld' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the payroll run this item belongs to.
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * Get the user (worker) this payment is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - Get the worker this payment is for.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the shift this payment is for (if applicable).
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the shift assignment this payment is for (if applicable).
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }

    /**
     * Get all deductions for this payroll item.
     */
    public function payrollDeductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }

    /**
     * Check if this item is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this item is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if this item is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if this item has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark this item as approved.
     */
    public function markAsApproved(): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        $this->update(['status' => self::STATUS_APPROVED]);

        return true;
    }

    /**
     * Mark this item as paid.
     */
    public function markAsPaid(?string $paymentReference = null, ?string $stripeTransferId = null): bool
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'payment_reference' => $paymentReference,
            'stripe_transfer_id' => $stripeTransferId,
            'paid_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark this item as failed.
     */
    public function markAsFailed(): bool
    {
        $this->update(['status' => self::STATUS_FAILED]);

        return true;
    }

    /**
     * Calculate and update the net amount based on gross, deductions, and taxes.
     */
    public function calculateNetAmount(): void
    {
        $this->net_amount = $this->gross_amount - $this->deductions - $this->tax_withheld;
        $this->save();
    }

    /**
     * Add a deduction to this payroll item.
     */
    public function addDeduction(string $type, string $description, float $amount, bool $isPercentage = false, ?float $percentageRate = null): PayrollDeduction
    {
        $deduction = $this->payrollDeductions()->create([
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
            'is_percentage' => $isPercentage,
            'percentage_rate' => $percentageRate,
        ]);

        // Recalculate deductions total
        $this->deductions = $this->payrollDeductions()->sum('amount');
        $this->calculateNetAmount();

        return $deduction;
    }

    /**
     * Get the type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_REGULAR => 'Regular Pay',
            self::TYPE_OVERTIME => 'Overtime',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_REIMBURSEMENT => 'Reimbursement',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray',
        };
    }

    /**
     * Scope for items by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid items.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for items by worker.
     */
    public function scopeForWorker($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
