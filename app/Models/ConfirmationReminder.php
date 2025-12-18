<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SL-004: Confirmation Reminder Model
 *
 * Tracks reminders sent for booking confirmations.
 *
 * @property int $id
 * @property int $booking_confirmation_id
 * @property string $type
 * @property string $recipient_type
 * @property \Illuminate\Support\Carbon $sent_at
 * @property bool $delivered
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property string|null $failure_reason
 * @property string|null $notification_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BookingConfirmation $bookingConfirmation
 */
class ConfirmationReminder extends Model
{
    use HasFactory;

    /**
     * Type constants
     */
    public const TYPE_EMAIL = 'email';

    public const TYPE_SMS = 'sms';

    public const TYPE_PUSH = 'push';

    /**
     * Recipient type constants
     */
    public const RECIPIENT_WORKER = 'worker';

    public const RECIPIENT_BUSINESS = 'business';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_confirmation_id',
        'type',
        'recipient_type',
        'sent_at',
        'delivered',
        'delivered_at',
        'failure_reason',
        'notification_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'delivered' => 'boolean',
        'delivered_at' => 'datetime',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the booking confirmation this reminder is for.
     */
    public function bookingConfirmation(): BelongsTo
    {
        return $this->belongsTo(BookingConfirmation::class);
    }

    // =========================================
    // Status Methods
    // =========================================

    /**
     * Check if the reminder was delivered successfully.
     */
    public function wasDelivered(): bool
    {
        return $this->delivered === true;
    }

    /**
     * Check if the reminder failed to deliver.
     */
    public function hasFailed(): bool
    {
        return $this->delivered === false && $this->failure_reason !== null;
    }

    /**
     * Mark the reminder as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'delivered' => true,
            'delivered_at' => now(),
            'failure_reason' => null,
        ]);
    }

    /**
     * Mark the reminder as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'delivered' => false,
            'failure_reason' => $reason,
        ]);
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope for email reminders.
     */
    public function scopeEmail($query)
    {
        return $query->where('type', self::TYPE_EMAIL);
    }

    /**
     * Scope for SMS reminders.
     */
    public function scopeSms($query)
    {
        return $query->where('type', self::TYPE_SMS);
    }

    /**
     * Scope for push reminders.
     */
    public function scopePush($query)
    {
        return $query->where('type', self::TYPE_PUSH);
    }

    /**
     * Scope for reminders to workers.
     */
    public function scopeToWorker($query)
    {
        return $query->where('recipient_type', self::RECIPIENT_WORKER);
    }

    /**
     * Scope for reminders to businesses.
     */
    public function scopeToBusiness($query)
    {
        return $query->where('recipient_type', self::RECIPIENT_BUSINESS);
    }

    /**
     * Scope for delivered reminders.
     */
    public function scopeDelivered($query)
    {
        return $query->where('delivered', true);
    }

    /**
     * Scope for failed reminders.
     */
    public function scopeFailed($query)
    {
        return $query->where('delivered', false)
            ->whereNotNull('failure_reason');
    }

    // =========================================
    // Static Helper Methods
    // =========================================

    /**
     * Create a new reminder record for a confirmation.
     */
    public static function createForConfirmation(
        BookingConfirmation $confirmation,
        string $type,
        string $recipientType,
        ?string $notificationId = null
    ): self {
        return self::create([
            'booking_confirmation_id' => $confirmation->id,
            'type' => $type,
            'recipient_type' => $recipientType,
            'sent_at' => now(),
            'notification_id' => $notificationId,
        ]);
    }

    /**
     * Get formatted type for display.
     */
    public function getFormattedType(): string
    {
        return match ($this->type) {
            self::TYPE_EMAIL => 'Email',
            self::TYPE_SMS => 'SMS',
            self::TYPE_PUSH => 'Push Notification',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted recipient type for display.
     */
    public function getFormattedRecipientType(): string
    {
        return match ($this->recipient_type) {
            self::RECIPIENT_WORKER => 'Worker',
            self::RECIPIENT_BUSINESS => 'Business',
            default => 'Unknown',
        };
    }
}
