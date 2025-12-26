<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Payment Ledger Model
 *
 * PRIORITY-0: Immutable ledger entries for payment mutations
 * Single source of truth for all payment state changes
 *
 * @property int $id
 * @property int|null $shift_payment_id
 * @property int|null $shift_assignment_id
 * @property int $user_id
 * @property string $provider
 * @property string|null $provider_payment_id
 * @property string|null $provider_transfer_id
 * @property string $entry_type
 * @property int $amount
 * @property int $balance_after
 * @property string $currency
 * @property array|null $metadata
 * @property string|null $reference
 * @property string|null $description
 * @property int|null $created_by
 * @property string|null $webhook_event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentLedger extends Model
{
    use HasFactory;

    protected $table = 'payment_ledger';

    protected $fillable = [
        'shift_payment_id',
        'shift_assignment_id',
        'user_id',
        'provider',
        'provider_payment_id',
        'provider_transfer_id',
        'entry_type',
        'amount',
        'balance_after',
        'currency',
        'metadata',
        'reference',
        'description',
        'created_by',
        'created_source', // user, webhook, cron, system
        'webhook_event_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Entry type constants
     */
    const TYPE_ESCROW_CAPTURED = 'escrow_captured';

    const TYPE_ESCROW_RELEASED = 'escrow_released';

    const TYPE_REFUND_INITIATED = 'refund_initiated';

    const TYPE_REFUND_COMPLETED = 'refund_completed';

    const TYPE_DISPUTE_OPENED = 'dispute_opened';

    const TYPE_DISPUTE_RESOLVED = 'dispute_resolved';

    const TYPE_PAYOUT_INITIATED = 'payout_initiated';

    const TYPE_PAYOUT_SUCCEEDED = 'payout_succeeded';

    const TYPE_PAYOUT_FAILED = 'payout_failed';

    const TYPE_FEE_DEDUCTED = 'fee_deducted';

    const TYPE_COMMISSION_DEDUCTED = 'commission_deducted';

    /**
     * Get the shift payment.
     */
    public function shiftPayment(): BelongsTo
    {
        return $this->belongsTo(ShiftPayment::class);
    }

    /**
     * Get the shift assignment.
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format amount as currency string.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2).' '.strtoupper($this->currency);
    }
}
