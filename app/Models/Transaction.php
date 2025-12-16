<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transaction Model
 * 
 * Records all financial transactions for users and platform
 * Includes earnings, payouts, refunds, penalties, and adjustments
 * 
 * FIN-006: Penalty & Deduction Engine
 * FIN-003: Payment Settlement Engine
 */
class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'amount_cents',
        'currency',
        'status',
        'description',
        'stripe_transfer_id',
        'stripe_refund_id',
        'stripe_payment_intent_id',
        'shift_id',
        'payment_id',
        'related_transaction_id',
        'metadata',
        'processed_at',
        'failure_reason'
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'processed_at' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * Transaction Type Constants
     */
    const TYPE_EARNING = 'EARNING';
    const TYPE_PAYOUT = 'PAYOUT';
    const TYPE_REFUND = 'REFUND';
    const TYPE_COMPENSATION = 'COMPENSATION';
    const TYPE_PENALTY = 'PENALTY';
    const TYPE_INSTAPAY_FEE = 'INSTAPAY_FEE';
    const TYPE_PLATFORM_FEE = 'PLATFORM_FEE';
    const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    const TYPE_CHARGEBACK = 'CHARGEBACK';

    /**
     * Transaction Status Constants
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to shift
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Relationship to related transaction (for reversals, adjustments)
     */
    public function relatedTransaction()
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    /**
     * Relationship to child transactions
     */
    public function childTransactions()
    {
        return $this->hasMany(Transaction::class, 'related_transaction_id');
    }

    /**
     * Scope for earnings
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    /**
     * Scope for payouts
     */
    public function scopePayouts($query)
    {
        return $query->where('type', self::TYPE_PAYOUT);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if transaction is a credit (positive for user)
     */
    public function isCredit(): bool
    {
        return in_array($this->type, [
            self::TYPE_EARNING,
            self::TYPE_REFUND,
            self::TYPE_COMPENSATION
        ]);
    }

    /**
     * Check if transaction is a debit (negative for user)
     */
    public function isDebit(): bool
    {
        return in_array($this->type, [
            self::TYPE_PAYOUT,
            self::TYPE_PENALTY,
            self::TYPE_INSTAPAY_FEE
        ]);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isCredit() ? '+' : '-';
        return $prefix . number_format(abs($this->amount_cents) / 100, 2);
    }

    /**
     * Get amount as decimal
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }

    /**
     * Get transaction type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EARNING => 'Shift Earnings',
            self::TYPE_PAYOUT => 'Payout',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_COMPENSATION => 'Compensation',
            self::TYPE_PENALTY => 'Penalty',
            self::TYPE_INSTAPAY_FEE => 'InstaPay Fee',
            self::TYPE_PLATFORM_FEE => 'Platform Fee',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_CHARGEBACK => 'Chargeback',
            default => 'Unknown'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now()
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'processed_at' => now()
        ]);
    }

    /**
     * Create earning transaction for shift completion
     */
    public static function createEarning(User $user, Shift $shift, int $amountCents, string $description = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => self::TYPE_EARNING,
            'amount_cents' => $amountCents,
            'currency' => $shift->currency ?? 'usd',
            'status' => self::STATUS_COMPLETED,
            'description' => $description ?: "Shift #{$shift->id} earnings",
            'shift_id' => $shift->id,
            'processed_at' => now()
        ]);
    }

    /**
     * Create penalty transaction
     */
    public static function createPenalty(User $user, int $amountCents, string $reason, Shift $shift = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => self::TYPE_PENALTY,
            'amount_cents' => $amountCents,
            'currency' => 'usd',
            'status' => self::STATUS_PENDING,
            'description' => "Penalty: {$reason}",
            'shift_id' => $shift?->id,
            'metadata' => ['penalty_reason' => $reason]
        ]);
    }

    /**
     * Calculate user's available balance
     */
    public static function calculateBalance(User $user, string $currency = 'usd'): int
    {
        $credits = static::where('user_id', $user->id)
            ->whereIn('type', [self::TYPE_EARNING, self::TYPE_REFUND, self::TYPE_COMPENSATION])
            ->where('currency', $currency)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount_cents');

        $debits = static::where('user_id', $user->id)
            ->whereIn('type', [self::TYPE_PAYOUT, self::TYPE_PENALTY, self::TYPE_INSTAPAY_FEE])
            ->where('currency', $currency)
            ->where('status', self::STATUS_COMPLETED)
            ->sum('amount_cents');

        $pendingDebits = static::where('user_id', $user->id)
            ->whereIn('type', [self::TYPE_PAYOUT, self::TYPE_PENALTY])
            ->where('currency', $currency)
            ->where('status', self::STATUS_PENDING)
            ->sum('amount_cents');

        return $credits - $debits - $pendingDebits;
    }

    /**
     * Get user's transaction history with pagination
     */
    public static function getTransactionHistory(User $user, int $limit = 50, array $types = null)
    {
        $query = static::where('user_id', $user->id)
            ->with(['shift'])
            ->orderBy('created_at', 'desc');

        if ($types) {
            $query->whereIn('type', $types);
        }

        return $query->paginate($limit);
    }
}