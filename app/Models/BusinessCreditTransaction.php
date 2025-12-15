<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Business Credit Transaction Model
 *
 * Tracks all credit account activity for businesses including:
 * - Charges (shift costs applied to credit)
 * - Payments (payments received)
 * - Late fees (interest on overdue balances)
 * - Refunds (credits issued back)
 * - Adjustments (manual corrections)
 */
class BusinessCreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'shift_id',
        'invoice_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'notes',
        'metadata',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the business that owns this transaction.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the related shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the related invoice.
     */
    public function invoice()
    {
        return $this->belongsTo(CreditInvoice::class, 'invoice_id');
    }

    /**
     * Scope for charges.
     */
    public function scopeCharges($query)
    {
        return $query->where('transaction_type', 'charge');
    }

    /**
     * Scope for payments.
     */
    public function scopePayments($query)
    {
        return $query->where('transaction_type', 'payment');
    }

    /**
     * Scope for late fees.
     */
    public function scopeLateFees($query)
    {
        return $query->where('transaction_type', 'late_fee');
    }

    /**
     * Scope for refunds.
     */
    public function scopeRefunds($query)
    {
        return $query->where('transaction_type', 'refund');
    }
}
