<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Refund Model
 *
 * Handles automated and manual refund processing for:
 * - Cancellations (>72 hours)
 * - Dispute resolutions
 * - Overcharge corrections
 * - Penalty waivers
 *
 * @property int $id
 * @property int $business_id
 * @property int|null $shift_id
 * @property int|null $shift_payment_id
 * @property int|null $processed_by_admin_id
 * @property string $refund_number
 * @property float $refund_amount
 * @property float $original_amount
 * @property string $refund_type
 * @property string $refund_reason
 * @property string|null $reason_description
 * @property string $refund_method
 * @property string $status
 * @property string|null $stripe_refund_id
 * @property string|null $paypal_refund_id
 * @property string|null $payment_gateway
 * @property string|null $credit_note_number
 * @property string|null $credit_note_pdf_path
 * @property \Illuminate\Support\Carbon|null $credit_note_generated_at
 * @property string|null $failure_reason
 * @property array|null $metadata
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $initiated_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 */
class Refund extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'shift_id',
        'shift_payment_id',
        'processed_by_admin_id',
        'refund_number',
        'refund_amount',
        'original_amount',
        'refund_type',
        'refund_reason',
        'reason_description',
        'refund_method',
        'status',
        'stripe_refund_id',
        'paypal_refund_id',
        'payment_gateway',
        'credit_note_number',
        'credit_note_pdf_path',
        'credit_note_generated_at',
        'failure_reason',
        'metadata',
        'admin_notes',
        'initiated_at',
        'processed_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'metadata' => 'array',
        'credit_note_generated_at' => 'datetime',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (! $refund->refund_number) {
                $refund->refund_number = self::generateRefundNumber();
            }
            if (! $refund->initiated_at) {
                $refund->initiated_at = now();
            }
        });
    }

    /**
     * Generate a unique refund number.
     */
    public static function generateRefundNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastRefund = self::where('refund_number', 'like', "REF-{$year}{$month}%")
            ->orderBy('refund_number', 'desc')
            ->first();

        if ($lastRefund) {
            $lastNumber = (int) substr($lastRefund->refund_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('REF-%s%s-%04d', $year, $month, $newNumber);
    }

    /**
     * Generate a unique credit note number.
     */
    public static function generateCreditNoteNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastNote = self::where('credit_note_number', 'like', "CN-{$year}{$month}%")
            ->orderBy('credit_note_number', 'desc')
            ->first();

        if ($lastNote) {
            $lastNumber = (int) substr($lastNote->credit_note_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('CN-%s%s-%04d', $year, $month, $newNumber);
    }

    /**
     * Get the business receiving the refund.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the shift related to this refund.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the shift payment being refunded.
     */
    public function shiftPayment()
    {
        return $this->belongsTo(ShiftPayment::class, 'shift_payment_id');
    }

    /**
     * Get the admin who processed this refund.
     */
    public function processedByAdmin()
    {
        return $this->belongsTo(User::class, 'processed_by_admin_id');
    }

    /**
     * Check if refund is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund is processing.
     */
    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    /**
     * Check if refund is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if refund failed.
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Mark refund as processing.
     */
    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark refund as completed.
     */
    public function markAsCompleted($gatewayRefundId = null, $gateway = null)
    {
        $updates = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($gatewayRefundId && $gateway) {
            $updates["{$gateway}_refund_id"] = $gatewayRefundId;
            $updates['payment_gateway'] = $gateway;
        }

        $this->update($updates);
    }

    /**
     * Mark refund as failed.
     */
    public function markAsFailed($reason)
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Generate credit note for this refund.
     */
    public function generateCreditNote()
    {
        if (! $this->credit_note_number) {
            $this->credit_note_number = self::generateCreditNoteNumber();
            $this->save();
        }

        // Generate PDF using the credit note service
        try {
            $pdfPath = app(\App\Services\CreditNotePdfService::class)->generate($this);

            return $this;
        } catch (\Exception $e) {
            \Log::warning("Failed to generate credit note PDF for refund {$this->refund_number}: ".$e->getMessage());

            return $this;
        }
    }

    /**
     * Check if credit note has been generated.
     */
    public function hasCreditNote()
    {
        return ! empty($this->credit_note_number);
    }

    /**
     * Scope for pending refunds.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing refunds.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for completed refunds.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed refunds.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for automatic refunds.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('refund_type', 'auto_cancellation');
    }

    /**
     * Scope for refunds by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('refund_type', $type);
    }

    /**
     * Scope for refunds by method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('refund_method', $method);
    }
}
