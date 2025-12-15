<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Credit Invoice Item Model
 *
 * Line items on credit invoices representing individual shifts or charges.
 */
class CreditInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'shift_id',
        'shift_payment_id',
        'description',
        'service_date',
        'quantity',
        'unit_price',
        'amount',
        'metadata',
    ];

    protected $casts = [
        'service_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the invoice that owns this item.
     */
    public function invoice()
    {
        return $this->belongsTo(CreditInvoice::class, 'invoice_id');
    }

    /**
     * Get the related shift.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the related shift payment.
     */
    public function shiftPayment()
    {
        return $this->belongsTo(ShiftPayment::class, 'shift_payment_id');
    }
}
