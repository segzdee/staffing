<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * Escrow Record Model
 * 
 * Tracks all escrow transactions for shift payments
 * Each escrow record represents funds held in platform escrow
 * 
 * FIN-002: Escrow Management System
 */
class EscrowRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shift_payment_id',
        'business_id',
        'worker_id',
        'amount_cents',
        'currency',
        'status',
        'stripe_transfer_id',
        'captured_at',
        'released_at',
        'refunded_at',
        'expires_at',
        'metadata'
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'captured_at' => 'datetime',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * Escrow Status Constants
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_HELD = 'HELD';
    const STATUS_RELEASED = 'RELEASED';
    const STATUS_DISPUTED = 'DISPUTED';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_REFUNDED = 'REFUNDED';

    /**
     * Relationship to shift payment
     */
    public function shiftPayment()
    {
        return $this->belongsTo(ShiftPayment::class);
    }

    /**
     * Relationship to business
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Relationship to worker
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Scope for active escrow records
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_HELD]);
    }

    /**
     * Scope for expired escrow records
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_HELD]);
    }

    /**
     * Check if escrow is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() 
               && in_array($this->status, [self::STATUS_PENDING, self::STATUS_HELD]);
    }

    /**
     * Check if escrow can be released
     */
    public function canBeReleased(): bool
    {
        return $this->status === self::STATUS_HELD;
    }

    /**
     * Check if escrow can be refunded
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_HELD, self::STATUS_PENDING]);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_cents / 100, 2);
    }

    /**
     * Get status with human-readable label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending Capture',
            self::STATUS_HELD => 'Funds Held',
            self::STATUS_RELEASED => 'Released',
            self::STATUS_DISPUTED => 'Under Dispute',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_REFUNDED => 'Refunded',
            default => 'Unknown'
        };
    }

    /**
     * Auto-expire pending escrow records
     */
    public static function expirePendingRecords(): int
    {
        $expired = self::expired()->get();
        
        $expired->each(function ($record) {
            $record->update([
                'status' => self::STATUS_EXPIRED,
                'refunded_at' => now()
            ]);
            
            // Log expiry
            Log::info('Escrow record expired', [
                'escrow_id' => $record->id,
                'shift_payment_id' => $record->shift_payment_id,
                'amount' => $record->formatted_amount
            ]);
        });

        return $expired->count();
    }
}