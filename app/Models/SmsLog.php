<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-004: SMS and WhatsApp Message Log Model
 *
 * Tracks all outbound SMS and WhatsApp messages with delivery status,
 * cost tracking, and retry handling.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $phone_number
 * @property string $channel
 * @property string $type
 * @property string $content
 * @property string|null $template_id
 * @property array|null $template_params
 * @property string|null $provider
 * @property string|null $provider_message_id
 * @property string $status
 * @property int $segments
 * @property float|null $cost
 * @property string $currency
 * @property string|null $error_message
 * @property string|null $error_code
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $queued_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 */
class SmsLog extends Model
{
    use HasFactory;

    protected $table = 'sms_logs';

    protected $fillable = [
        'user_id',
        'phone_number',
        'channel',
        'type',
        'content',
        'template_id',
        'template_params',
        'provider',
        'provider_message_id',
        'status',
        'segments',
        'cost',
        'currency',
        'error_message',
        'error_code',
        'retry_count',
        'queued_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'template_params' => 'array',
        'segments' => 'integer',
        'cost' => 'decimal:4',
        'retry_count' => 'integer',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Channel constants
     */
    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    /**
     * Type constants
     */
    public const TYPE_OTP = 'otp';

    public const TYPE_SHIFT_REMINDER = 'shift_reminder';

    public const TYPE_URGENT_ALERT = 'urgent_alert';

    public const TYPE_MARKETING = 'marketing';

    public const TYPE_TRANSACTIONAL = 'transactional';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_READ = 'read';

    /**
     * Provider constants
     */
    public const PROVIDER_TWILIO = 'twilio';

    public const PROVIDER_VONAGE = 'vonage';

    public const PROVIDER_MESSAGEBIRD = 'messagebird';

    public const PROVIDER_META = 'meta';

    public const PROVIDER_SNS = 'sns';

    /**
     * Maximum retry attempts
     */
    public const MAX_RETRIES = 3;

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: SMS messages only
     */
    public function scopeSms($query)
    {
        return $query->channel(self::CHANNEL_SMS);
    }

    /**
     * Scope: WhatsApp messages only
     */
    public function scopeWhatsapp($query)
    {
        return $query->channel(self::CHANNEL_WHATSAPP);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending messages
     */
    public function scopePending($query)
    {
        return $query->status(self::STATUS_PENDING);
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->status(self::STATUS_FAILED);
    }

    /**
     * Scope: Delivered messages
     */
    public function scopeDelivered($query)
    {
        return $query->status(self::STATUS_DELIVERED);
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific phone number
     */
    public function scopeForPhone($query, string $phone)
    {
        return $query->where('phone_number', $phone);
    }

    /**
     * Scope: Recent messages
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Retryable messages
     */
    public function scopeRetryable($query)
    {
        return $query->status(self::STATUS_FAILED)
            ->where('retry_count', '<', self::MAX_RETRIES);
    }

    /**
     * Check if message was sent successfully
     */
    public function wasSent(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_READ,
        ]);
    }

    /**
     * Check if message was delivered
     */
    public function wasDelivered(): bool
    {
        return in_array($this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_READ,
        ]);
    }

    /**
     * Check if message was read (WhatsApp only)
     */
    public function wasRead(): bool
    {
        return $this->status === self::STATUS_READ;
    }

    /**
     * Check if message can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->retry_count < self::MAX_RETRIES;
    }

    /**
     * Mark as queued
     */
    public function markQueued(): bool
    {
        return $this->update([
            'status' => self::STATUS_QUEUED,
            'queued_at' => now(),
        ]);
    }

    /**
     * Mark as sent
     */
    public function markSent(?string $providerMessageId = null): bool
    {
        $data = [
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ];

        if ($providerMessageId) {
            $data['provider_message_id'] = $providerMessageId;
        }

        return $this->update($data);
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as read (WhatsApp)
     */
    public function markRead(): bool
    {
        return $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage, ?string $errorCode = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
            'failed_at' => now(),
        ]);
    }

    /**
     * Increment retry count and reset for retry
     */
    public function prepareForRetry(): bool
    {
        if (! $this->canRetry()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_PENDING,
            'retry_count' => $this->retry_count + 1,
            'error_message' => null,
            'error_code' => null,
            'failed_at' => null,
        ]);
    }

    /**
     * Update cost
     */
    public function setCost(float $cost, string $currency = 'USD'): bool
    {
        return $this->update([
            'cost' => $cost,
            'currency' => $currency,
        ]);
    }

    /**
     * Find by provider message ID
     */
    public static function findByProviderMessageId(string $messageId): ?self
    {
        return static::where('provider_message_id', $messageId)->first();
    }

    /**
     * Get delivery time in seconds (from sent to delivered)
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if (! $this->sent_at || ! $this->delivered_at) {
            return null;
        }

        return $this->delivered_at->diffInSeconds($this->sent_at);
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_QUEUED => 'Queued',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_READ => 'Read',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'secondary',
            self::STATUS_QUEUED => 'info',
            self::STATUS_SENT => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_READ => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get channel label
     */
    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            default => ucfirst($this->channel),
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_OTP => 'OTP',
            self::TYPE_SHIFT_REMINDER => 'Shift Reminder',
            self::TYPE_URGENT_ALERT => 'Urgent Alert',
            self::TYPE_MARKETING => 'Marketing',
            self::TYPE_TRANSACTIONAL => 'Transactional',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get cost statistics for a date range
     */
    public static function getCostStats(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $query = static::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('cost');

        return [
            'total_cost' => $query->sum('cost'),
            'sms_cost' => (clone $query)->sms()->sum('cost'),
            'whatsapp_cost' => (clone $query)->whatsapp()->sum('cost'),
            'message_count' => $query->count(),
            'average_cost' => $query->avg('cost'),
        ];
    }

    /**
     * Get delivery statistics
     */
    public static function getDeliveryStats(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $total = static::whereBetween('created_at', [$startDate, $endDate])->count();
        $delivered = static::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_READ])
            ->count();
        $failed = static::whereBetween('created_at', [$startDate, $endDate])
            ->status(self::STATUS_FAILED)
            ->count();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }
}
