<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-011: Subscription Invoice Model
 *
 * Represents a billing invoice for a subscription payment.
 * Tracks payment status, amounts, and Stripe invoice details.
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $user_id
 * @property string|null $stripe_invoice_id
 * @property string|null $invoice_number
 * @property float $subtotal
 * @property float $tax
 * @property float $discount
 * @property float $total
 * @property string $currency
 * @property string $status
 * @property string|null $pdf_url
 * @property string|null $hosted_invoice_url
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $payment_method
 * @property string|null $payment_intent_id
 * @property array|null $line_items
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read User $user
 */
class SubscriptionInvoice extends Model
{
    use HasFactory;

    // Invoice Statuses
    public const STATUS_DRAFT = 'draft';

    public const STATUS_OPEN = 'open';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const STATUS_UNCOLLECTIBLE = 'uncollectible';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'stripe_invoice_id',
        'invoice_number',
        'subtotal',
        'tax',
        'discount',
        'total',
        'currency',
        'status',
        'pdf_url',
        'hosted_invoice_url',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'payment_method',
        'payment_intent_id',
        'line_items',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'line_items' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription this invoice belongs to.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user this invoice belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get paid invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get open invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get unpaid invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_DRAFT]);
    }

    /**
     * Scope to get voided invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVoided($query)
    {
        return $query->where('status', self::STATUS_VOID);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if invoice is voided.
     */
    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    /**
     * Check if invoice is uncollectible.
     */
    public function isUncollectible(): bool
    {
        return $this->status === self::STATUS_UNCOLLECTIBLE;
    }

    /**
     * Check if invoice is past due.
     */
    public function isPastDue(): bool
    {
        if (! $this->isOpen()) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Get the formatted total.
     */
    public function getFormattedTotalAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'CAD' => 'C$',
            'AUD' => 'A$',
            default => $this->currency.' ',
        };

        return $symbol.number_format($this->total, 2);
    }

    /**
     * Get the formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            default => $this->currency.' ',
        };

        return $symbol.number_format($this->subtotal, 2);
    }

    /**
     * Get the formatted tax.
     */
    public function getFormattedTaxAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            default => $this->currency.' ',
        };

        return $symbol.number_format($this->tax, 2);
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_OPEN => 'Open',
            self::STATUS_PAID => 'Paid',
            self::STATUS_VOID => 'Void',
            self::STATUS_UNCOLLECTIBLE => 'Uncollectible',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_OPEN => $this->isPastDue() ? 'warning' : 'info',
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_VOID, self::STATUS_UNCOLLECTIBLE => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get the billing period description.
     */
    public function getBillingPeriodAttribute(): string
    {
        if (! $this->period_start || ! $this->period_end) {
            return 'N/A';
        }

        return $this->period_start->format('M j, Y').' - '.$this->period_end->format('M j, Y');
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(?string $paymentIntentId = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payment_intent_id' => $paymentIntentId ?? $this->payment_intent_id,
        ]);
    }

    /**
     * Mark invoice as void.
     */
    public function markAsVoid(): void
    {
        $this->update([
            'status' => self::STATUS_VOID,
        ]);
    }

    /**
     * Mark invoice as uncollectible.
     */
    public function markAsUncollectible(): void
    {
        $this->update([
            'status' => self::STATUS_UNCOLLECTIBLE,
        ]);
    }

    /**
     * Generate invoice number if not set.
     */
    public function generateInvoiceNumber(): string
    {
        if ($this->invoice_number) {
            return $this->invoice_number;
        }

        $prefix = 'INV';
        $year = now()->format('Y');
        $sequence = str_pad($this->id, 6, '0', STR_PAD_LEFT);

        $invoiceNumber = "{$prefix}-{$year}-{$sequence}";

        $this->update(['invoice_number' => $invoiceNumber]);

        return $invoiceNumber;
    }
}
