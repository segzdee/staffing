<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SL-004: Booking Confirmation Model
 *
 * Manages the dual-confirmation workflow for shift bookings.
 * Both workers and businesses must confirm before a booking is finalized.
 *
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property int $business_id
 * @property string $status
 * @property bool $worker_confirmed
 * @property \Illuminate\Support\Carbon|null $worker_confirmed_at
 * @property bool $business_confirmed
 * @property \Illuminate\Support\Carbon|null $business_confirmed_at
 * @property string $confirmation_code
 * @property string|null $worker_notes
 * @property string|null $business_notes
 * @property int|null $declined_by
 * @property \Illuminate\Support\Carbon|null $declined_at
 * @property string|null $decline_reason
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property bool $auto_confirmed
 * @property string|null $auto_confirm_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User $business
 * @property-read \App\Models\User|null $declinedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ConfirmationReminder> $reminders
 */
class BookingConfirmation extends Model
{
    use HasFactory;

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_WORKER_CONFIRMED = 'worker_confirmed';

    public const STATUS_BUSINESS_CONFIRMED = 'business_confirmed';

    public const STATUS_FULLY_CONFIRMED = 'fully_confirmed';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_id',
        'worker_id',
        'business_id',
        'status',
        'worker_confirmed',
        'worker_confirmed_at',
        'business_confirmed',
        'business_confirmed_at',
        'confirmation_code',
        'worker_notes',
        'business_notes',
        'declined_by',
        'declined_at',
        'decline_reason',
        'expires_at',
        'reminder_sent_at',
        'auto_confirmed',
        'auto_confirm_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'worker_confirmed' => 'boolean',
        'worker_confirmed_at' => 'datetime',
        'business_confirmed' => 'boolean',
        'business_confirmed_at' => 'datetime',
        'declined_at' => 'datetime',
        'expires_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'auto_confirmed' => 'boolean',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the shift this confirmation is for.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker who needs to confirm.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business that needs to confirm.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the user who declined the confirmation (if applicable).
     */
    public function declinedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    /**
     * Get all reminders sent for this confirmation.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(ConfirmationReminder::class);
    }

    // =========================================
    // Status Check Methods
    // =========================================

    /**
     * Check if the confirmation is pending (no confirmations yet).
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if only the worker has confirmed.
     */
    public function isWorkerConfirmed(): bool
    {
        return $this->status === self::STATUS_WORKER_CONFIRMED ||
               ($this->worker_confirmed && ! $this->business_confirmed);
    }

    /**
     * Check if only the business has confirmed.
     */
    public function isBusinessConfirmed(): bool
    {
        return $this->status === self::STATUS_BUSINESS_CONFIRMED ||
               ($this->business_confirmed && ! $this->worker_confirmed);
    }

    /**
     * Check if fully confirmed by both parties.
     */
    public function isFullyConfirmed(): bool
    {
        return $this->status === self::STATUS_FULLY_CONFIRMED ||
               ($this->worker_confirmed && $this->business_confirmed);
    }

    /**
     * Check if the confirmation was declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if the confirmation has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Check if the confirmation is still actionable (not declined/expired/fully confirmed).
     */
    public function isActionable(): bool
    {
        return ! $this->isExpired() && ! $this->isDeclined() && ! $this->isFullyConfirmed();
    }

    // =========================================
    // Time-Related Methods
    // =========================================

    /**
     * Get hours until expiration.
     */
    public function hoursUntilExpiration(): float
    {
        if (! $this->expires_at || $this->expires_at->isPast()) {
            return 0;
        }

        return now()->diffInHours($this->expires_at, false);
    }

    /**
     * Check if a reminder should be sent (based on config).
     */
    public function shouldSendReminder(): bool
    {
        // Don't send if already actioned
        if (! $this->isActionable()) {
            return false;
        }

        $reminderHours = config('booking_confirmation.reminder_hours_before', [12, 4]);
        $hoursUntilExpiry = $this->hoursUntilExpiration();

        foreach ($reminderHours as $reminderAt) {
            // Check if we're within the reminder window
            if ($hoursUntilExpiry <= $reminderAt && $hoursUntilExpiry > ($reminderAt - 1)) {
                // Check if reminder was already sent recently
                if ($this->reminder_sent_at && $this->reminder_sent_at->diffInHours(now()) < 2) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Check if this is expiring soon (within configured warning threshold).
     */
    public function isExpiringSoon(): bool
    {
        $warningHours = config('booking_confirmation.expiring_soon_hours', 4);

        return $this->hoursUntilExpiration() <= $warningHours && $this->hoursUntilExpiration() > 0;
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope for pending confirmations.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for confirmations awaiting worker response.
     */
    public function scopeAwaitingWorker($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_BUSINESS_CONFIRMED])
            ->where('worker_confirmed', false);
    }

    /**
     * Scope for confirmations awaiting business response.
     */
    public function scopeAwaitingBusiness($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_WORKER_CONFIRMED])
            ->where('business_confirmed', false);
    }

    /**
     * Scope for fully confirmed bookings.
     */
    public function scopeFullyConfirmed($query)
    {
        return $query->where('status', self::STATUS_FULLY_CONFIRMED);
    }

    /**
     * Scope for expired confirmations.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope for confirmations that should be expired (past expiry but not marked).
     */
    public function scopeShouldExpire($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_FULLY_CONFIRMED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
        ])
            ->where('expires_at', '<', now());
    }

    /**
     * Scope for confirmations needing reminders.
     */
    public function scopeNeedingReminder($query)
    {
        $reminderHours = config('booking_confirmation.reminder_hours_before', [12, 4]);
        $maxReminderHours = max($reminderHours);

        return $query->whereNotIn('status', [
            self::STATUS_FULLY_CONFIRMED,
            self::STATUS_DECLINED,
            self::STATUS_EXPIRED,
        ])
            ->where('expires_at', '<=', now()->addHours($maxReminderHours))
            ->where('expires_at', '>', now())
            ->where(function ($q) {
                $q->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<', now()->subHours(2));
            });
    }

    /**
     * Scope for confirmations by a specific worker.
     */
    public function scopeForWorker($query, int $workerId)
    {
        return $query->where('worker_id', $workerId);
    }

    /**
     * Scope for confirmations by a specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for active confirmations (not declined/expired).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DECLINED, self::STATUS_EXPIRED]);
    }

    // =========================================
    // QR Code Methods
    // =========================================

    /**
     * Get the URL for QR code verification.
     */
    public function getQrCodeUrl(): string
    {
        return url('/confirm/'.$this->confirmation_code);
    }

    /**
     * Get QR code data for encoding.
     */
    public function getQrCodeData(): array
    {
        return [
            'code' => $this->confirmation_code,
            'shift_id' => $this->shift_id,
            'worker_id' => $this->worker_id,
            'expires_at' => $this->expires_at->toIso8601String(),
            'url' => $this->getQrCodeUrl(),
        ];
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Generate a unique confirmation code.
     */
    public static function generateConfirmationCode(): string
    {
        $length = config('booking_confirmation.code_length', 8);
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluding confusable chars (0,O,I,1)

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('confirmation_code', $code)->exists());

        return $code;
    }

    /**
     * Get formatted status for display.
     */
    public function getFormattedStatus(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Awaiting Confirmation',
            self::STATUS_WORKER_CONFIRMED => 'Worker Confirmed - Awaiting Business',
            self::STATUS_BUSINESS_CONFIRMED => 'Business Confirmed - Awaiting Worker',
            self::STATUS_FULLY_CONFIRMED => 'Fully Confirmed',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_WORKER_CONFIRMED => 'blue',
            self::STATUS_BUSINESS_CONFIRMED => 'blue',
            self::STATUS_FULLY_CONFIRMED => 'green',
            self::STATUS_DECLINED => 'red',
            self::STATUS_EXPIRED => 'gray',
            default => 'gray',
        };
    }
}
