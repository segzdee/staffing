<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-006: Worker Earnings Model
 *
 * Tracks individual earnings for workers including shift pay, bonuses, tips,
 * referral bonuses, adjustments, and reimbursements.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property string $type
 * @property float $gross_amount
 * @property float $platform_fee
 * @property float $tax_withheld
 * @property float $net_amount
 * @property string $currency
 * @property string $status
 * @property string|null $description
 * @property Carbon $earned_date
 * @property Carbon|null $pay_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $worker
 * @property-read Shift|null $shift
 */
class WorkerEarning extends Model
{
    use HasFactory;

    /**
     * Earning types
     */
    public const TYPE_SHIFT_PAY = 'shift_pay';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_TIP = 'tip';

    public const TYPE_REFERRAL = 'referral';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REIMBURSEMENT = 'reimbursement';

    /**
     * Earning statuses
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_DISPUTED = 'disputed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'shift_id',
        'type',
        'gross_amount',
        'platform_fee',
        'tax_withheld',
        'net_amount',
        'hours_worked',
        'hourly_rate',
        'currency',
        'status',
        'dispute_reason',
        'description',
        'earned_date',
        'pay_date',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gross_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'tax_withheld' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'earned_date' => 'date',
        'pay_date' => 'date',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the worker who earned this.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for worker relationship.
     */
    public function user(): BelongsTo
    {
        return $this->worker();
    }

    /**
     * Get the shift associated with this earning.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Scope for a specific worker.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWorker($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('earned_date', [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Scope for a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for paid earnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for pending earnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved earnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for shift pay earnings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShiftPay($query)
    {
        return $query->where('type', self::TYPE_SHIFT_PAY);
    }

    /**
     * Scope for a specific year.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('earned_date', $year);
    }

    /**
     * Scope for current month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('earned_date', now()->month)
            ->whereYear('earned_date', now()->year);
    }

    /**
     * Scope for current week.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('earned_date', [
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
        ]);
    }

    /**
     * Check if earning is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if earning is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if earning is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if earning is disputed.
     */
    public function isDisputed(): bool
    {
        return $this->status === self::STATUS_DISPUTED;
    }

    /**
     * Get the type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_SHIFT_PAY => 'Shift Pay',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_TIP => 'Tip',
            self::TYPE_REFERRAL => 'Referral Bonus',
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
            self::STATUS_DISPUTED => 'Disputed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_PAID => 'green',
            self::STATUS_DISPUTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Mark earning as approved.
     */
    public function approve(): bool
    {
        return $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark earning as paid.
     */
    public function markPaid(?Carbon $payDate = null): bool
    {
        return $this->update([
            'status' => self::STATUS_PAID,
            'pay_date' => $payDate ?? now(),
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark earning as disputed.
     */
    public function dispute(?string $reason = null): bool
    {
        $data = ['status' => self::STATUS_DISPUTED];

        if ($reason !== null) {
            $data['dispute_reason'] = $reason;
        }

        return $this->update($data);
    }

    /**
     * Get all available earning types.
     *
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SHIFT_PAY => 'Shift Pay',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_TIP => 'Tip',
            self::TYPE_REFERRAL => 'Referral Bonus',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_REIMBURSEMENT => 'Reimbursement',
        ];
    }

    /**
     * Get all available statuses.
     *
     * @return array<string, string>
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID => 'Paid',
            self::STATUS_DISPUTED => 'Disputed',
        ];
    }
}
