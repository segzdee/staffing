<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * DisputeMessage Model
 *
 * Polymorphic message model for dispute communication threads.
 * Supports messages from workers, businesses, and admins.
 *
 * TASK: ADM-002 Automated Dispute Resolution Escalation
 *
 * @property int $id
 * @property int $dispute_id
 * @property string $sender_type
 * @property int $sender_id
 * @property string $message
 * @property string $message_type
 * @property bool $is_internal
 * @property array|null $attachments
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property int|null $read_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class DisputeMessage extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dispute_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dispute_id',
        'sender_type',
        'sender_id',
        'message',
        'message_type',
        'is_internal',
        'attachments',
        'read_at',
        'read_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Message types.
     */
    public const TYPE_TEXT = 'text';
    public const TYPE_EVIDENCE = 'evidence';
    public const TYPE_SYSTEM = 'system';
    public const TYPE_RESOLUTION = 'resolution';

    /**
     * Get the dispute this message belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dispute()
    {
        return $this->belongsTo(AdminDisputeQueue::class, 'dispute_id');
    }

    /**
     * Get the sender of this message (polymorphic).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sender()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who read this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function readByUser()
    {
        return $this->belongsTo(User::class, 'read_by');
    }

    /**
     * Scope: Messages visible to parties (not internal).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Internal admin messages only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Messages of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope: Unread messages.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Check if message is from admin.
     *
     * @return bool
     */
    public function isFromAdmin(): bool
    {
        return $this->sender_type === User::class &&
            optional($this->sender)->role === 'admin';
    }

    /**
     * Check if message is from worker.
     *
     * @return bool
     */
    public function isFromWorker(): bool
    {
        if ($this->sender_type !== User::class) {
            return false;
        }

        $dispute = $this->dispute;
        return $dispute && $this->sender_id === $dispute->worker_id;
    }

    /**
     * Check if message is from business.
     *
     * @return bool
     */
    public function isFromBusiness(): bool
    {
        if ($this->sender_type !== User::class) {
            return false;
        }

        $dispute = $this->dispute;
        return $dispute && $this->sender_id === $dispute->business_id;
    }

    /**
     * Get sender type label.
     *
     * @return string
     */
    public function getSenderTypeLabel(): string
    {
        if ($this->message_type === self::TYPE_SYSTEM) {
            return 'System';
        }

        if ($this->isFromAdmin()) {
            return 'Admin';
        }

        if ($this->isFromWorker()) {
            return 'Worker';
        }

        if ($this->isFromBusiness()) {
            return 'Business';
        }

        return 'Unknown';
    }

    /**
     * Mark message as read.
     *
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $userId): bool
    {
        if ($this->read_at) {
            return false;
        }

        return $this->update([
            'read_at' => now(),
            'read_by' => $userId,
        ]);
    }

    /**
     * Check if message has attachments.
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get attachment count.
     *
     * @return int
     */
    public function getAttachmentCount(): int
    {
        return is_array($this->attachments) ? count($this->attachments) : 0;
    }

    /**
     * Add attachment to message.
     *
     * @param array $attachment
     * @return void
     */
    public function addAttachment(array $attachment): void
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = $attachment;

        $this->update(['attachments' => $attachments]);
    }

    /**
     * Create a system message.
     *
     * @param int $disputeId
     * @param string $message
     * @return static
     */
    public static function createSystemMessage(int $disputeId, string $message): self
    {
        return self::create([
            'dispute_id' => $disputeId,
            'sender_type' => User::class,
            'sender_id' => 0, // System
            'message' => $message,
            'message_type' => self::TYPE_SYSTEM,
            'is_internal' => false,
        ]);
    }

    /**
     * Create an evidence message.
     *
     * @param int $disputeId
     * @param int $senderId
     * @param string $message
     * @param array $attachments
     * @return static
     */
    public static function createEvidenceMessage(
        int $disputeId,
        int $senderId,
        string $message,
        array $attachments = []
    ): self {
        return self::create([
            'dispute_id' => $disputeId,
            'sender_type' => User::class,
            'sender_id' => $senderId,
            'message' => $message,
            'message_type' => self::TYPE_EVIDENCE,
            'is_internal' => false,
            'attachments' => $attachments,
        ]);
    }
}
