<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BIZ-010: Template Send Model
 *
 * Records each instance of a template being sent to a worker.
 *
 * @property int $id
 * @property int $template_id
 * @property int $sender_id
 * @property int $recipient_id
 * @property int|null $shift_id
 * @property string $channel
 * @property string|null $subject
 * @property string $rendered_content
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\CommunicationTemplate $template
 * @property-read \App\Models\User $sender
 * @property-read \App\Models\User $recipient
 * @property-read \App\Models\Shift|null $shift
 */
class TemplateSend extends Model
{
    use HasFactory;

    // Status Constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'template_id',
        'sender_id',
        'recipient_id',
        'shift_id',
        'channel',
        'subject',
        'rendered_content',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SENT => 'Sent',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Template used for this send.
     */
    public function template()
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    /**
     * User who sent the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * User who received the message.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Related shift (if applicable).
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to pending sends.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to sent sends.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to delivered sends.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope to failed sends.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to sends by a specific sender.
     */
    public function scopeBySender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope to sends for a specific recipient.
     */
    public function scopeForRecipient($query, int $recipientId)
    {
        return $query->where('recipient_id', $recipientId);
    }

    /**
     * Scope to sends for a specific shift.
     */
    public function scopeForShift($query, int $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope to sends via a specific channel.
     */
    public function scopeViaChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to unread sends.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to recent sends.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== ACCESSORS ====================

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Check if send was successful.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * Check if send is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if send failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if message has been read.
     */
    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    // ==================== METHODS ====================

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Get a preview of the rendered content.
     */
    public function getPreview(int $length = 100): string
    {
        $content = strip_tags($this->rendered_content);

        return strlen($content) > $length
            ? substr($content, 0, $length).'...'
            : $content;
    }
}
