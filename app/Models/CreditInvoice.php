<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Credit Invoice Model
 *
 * Weekly invoices generated for businesses using credit.
 * Invoices are typically Net 14 days payment terms.
 */
class CreditInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'period_start',
        'period_end',
        'subtotal',
        'late_fees',
        'adjustments',
        'total_amount',
        'amount_paid',
        'amount_due',
        'status',
        'pdf_path',
        'pdf_generated_at',
        'sent_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'pdf_generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
            if (!$invoice->amount_due) {
                $invoice->amount_due = $invoice->total_amount;
            }
        });
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastInvoice = self::where('invoice_number', 'like', "INV-{$year}{$month}%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('INV-%s%s-%04d', $year, $month, $newNumber);
    }

    /**
     * Get the business that owns this invoice.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the invoice items.
     */
    public function items()
    {
        return $this->hasMany(CreditInvoiceItem::class, 'invoice_id');
    }

    /**
     * Get the transactions related to this invoice.
     */
    public function transactions()
    {
        return $this->hasMany(BusinessCreditTransaction::class, 'invoice_id');
    }

    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue()
    {
        return $this->status !== 'paid'
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Check if the invoice is paid in full.
     */
    public function isPaid()
    {
        return $this->status === 'paid' || $this->amount_due <= 0;
    }

    /**
     * Check if the invoice is partially paid.
     */
    public function isPartiallyPaid()
    {
        return $this->amount_paid > 0 && $this->amount_due > 0;
    }

    /**
     * Record a payment on this invoice.
     */
    public function recordPayment($amount, $referenceId = null, $referenceType = null)
    {
        $this->amount_paid += $amount;
        $this->amount_due = $this->total_amount - $this->amount_paid;

        if ($this->amount_due <= 0) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partially_paid';
        }

        $this->save();

        // Create transaction record
        BusinessCreditTransaction::create([
            'business_id' => $this->business_id,
            'invoice_id' => $this->id,
            'transaction_type' => 'payment',
            'amount' => -$amount, // Negative for payments
            'balance_before' => $this->business->businessProfile->credit_used,
            'balance_after' => $this->business->businessProfile->credit_used - $amount,
            'description' => "Payment for invoice {$this->invoice_number}",
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
        ]);

        return $this;
    }

    /**
     * Add late fee to invoice.
     */
    public function addLateFee($amount, $description = null)
    {
        $this->late_fees += $amount;
        $this->total_amount += $amount;
        $this->amount_due += $amount;
        $this->save();

        // Create transaction record
        BusinessCreditTransaction::create([
            'business_id' => $this->business_id,
            'invoice_id' => $this->id,
            'transaction_type' => 'late_fee',
            'amount' => $amount,
            'balance_before' => $this->business->businessProfile->credit_used,
            'balance_after' => $this->business->businessProfile->credit_used + $amount,
            'description' => $description ?? "Late fee for invoice {$this->invoice_number}",
        ]);

        return $this;
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue()
    {
        $this->update(['status' => 'overdue']);
    }

    /**
     * Scope for issued invoices.
     */
    public function scopeIssued($query)
    {
        return $query->whereIn('status', ['issued', 'sent', 'partially_paid', 'overdue']);
    }

    /**
     * Scope for unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['issued', 'sent', 'partially_paid', 'overdue'])
            ->where('amount_due', '>', 0);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'paid')
            ->where('due_date', '<', now());
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
