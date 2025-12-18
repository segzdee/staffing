<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * COM-001: Enhanced Message Model
 *
 * Supports rich messages with attachments, types, and read receipts.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $from_user_id
 * @property int $to_user_id
 * @property string $message
 * @property string $message_type
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property string|null $attachment_url
 * @property string|null $attachment_type
 * @property array|null $attachments
 * @property array|null $metadata
 * @property bool $is_edited
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $recipient
 * @property-read \App\Models\User|null $sender
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MessageRead> $reads
 * @property-read int|null $reads_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message forRecipient($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message fromSender($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereAttachmentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereAttachmentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereFromUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereToUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Message whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Message extends Model
{
    use HasFactory, SoftDeletes;

    // Message types
    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_FILE = 'file';

    public const TYPE_SYSTEM = 'system';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'from_user_id',
        'to_user_id',
        'message',
        'message_type',
        'is_read',
        'read_at',
        'attachment_url',
        'attachment_type',
        'attachments',
        'metadata',
        'is_edited',
        'edited_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'attachments' => 'array',
            'metadata' => 'array',
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the recipient of this message (legacy support).
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * COM-001: Get read receipts for this message.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Mark message as read (legacy method).
     */
    public function markAsRead(): self
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * COM-001: Mark message as read by a specific user.
     */
    public function markAsReadBy(User $user): MessageRead
    {
        // Update legacy field if this user is the recipient
        if ($this->to_user_id === $user->id && ! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        // Create read receipt
        return MessageRead::recordRead($this->id, $user->id);
    }

    /**
     * COM-001: Check if message was read by a specific user.
     */
    public function wasReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * COM-001: Get the count of users who have read this message.
     */
    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    /**
     * Check if message has attachment (legacy).
     */
    public function hasAttachment(): bool
    {
        return ! empty($this->attachment_url) || ! empty($this->attachments);
    }

    /**
     * COM-001: Check if message has multiple attachments.
     */
    public function hasMultipleAttachments(): bool
    {
        return is_array($this->attachments) && count($this->attachments) > 1;
    }

    /**
     * COM-001: Get all attachment URLs.
     */
    public function getAttachmentUrls(): array
    {
        $urls = [];

        // Include legacy attachment
        if ($this->attachment_url) {
            $urls[] = $this->attachment_url;
        }

        // Include new attachments array
        if (is_array($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                if (isset($attachment['url'])) {
                    $urls[] = $attachment['url'];
                }
            }
        }

        return $urls;
    }

    /**
     * COM-001: Check if this is a text message.
     */
    public function isText(): bool
    {
        return $this->message_type === self::TYPE_TEXT || empty($this->message_type);
    }

    /**
     * COM-001: Check if this is an image message.
     */
    public function isImage(): bool
    {
        return $this->message_type === self::TYPE_IMAGE;
    }

    /**
     * COM-001: Check if this is a file message.
     */
    public function isFile(): bool
    {
        return $this->message_type === self::TYPE_FILE;
    }

    /**
     * COM-001: Check if this is a system message.
     */
    public function isSystem(): bool
    {
        return $this->message_type === self::TYPE_SYSTEM;
    }

    /**
     * COM-001: Edit the message content.
     */
    public function edit(string $newContent): bool
    {
        return $this->update([
            'message' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * COM-001: Get message body (alias for message attribute).
     */
    public function getBodyAttribute(): string
    {
        return $this->message ?? '';
    }

    /**
     * Scope: Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: For specific recipient
     */
    public function scopeForRecipient($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    /**
     * Scope: From specific sender
     */
    public function scopeFromSender($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    /**
     * Scope: Of specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * Scope: System messages only
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('message_type', self::TYPE_SYSTEM);
    }

    /**
     * Scope: User messages only (non-system)
     */
    public function scopeUserMessages($query)
    {
        return $query->where('message_type', '!=', self::TYPE_SYSTEM);
    }

    /**
     * COM-005: Get moderation logs for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function moderationLogs()
    {
        return $this->morphMany(MessageModerationLog::class, 'moderatable');
    }

    /**
     * COM-005: Get communication reports for this message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function reports()
    {
        return $this->morphMany(CommunicationReport::class, 'reportable');
    }

    /**
     * COM-005: Check if this message was flagged for moderation.
     */
    public function wasFlagged(): bool
    {
        return $this->moderationLogs()
            ->whereIn('action', [
                MessageModerationLog::ACTION_FLAGGED,
                MessageModerationLog::ACTION_BLOCKED,
                MessageModerationLog::ACTION_REDACTED,
            ])
            ->exists();
    }

    /**
     * COM-005: Check if this message was blocked.
     */
    public function wasBlocked(): bool
    {
        return $this->moderationLogs()
            ->where('action', MessageModerationLog::ACTION_BLOCKED)
            ->exists();
    }

    /**
     * COM-005: Check if this message has pending reports.
     */
    public function hasPendingReports(): bool
    {
        return $this->reports()
            ->where('status', CommunicationReport::STATUS_PENDING)
            ->exists();
    }

    /**
     * COM-001: Create a system message.
     */
    public static function createSystemMessage(Conversation $conversation, string $content, ?array $metadata = null): self
    {
        return self::create([
            'conversation_id' => $conversation->id,
            'from_user_id' => 0, // System
            'to_user_id' => 0,
            'message' => $content,
            'message_type' => self::TYPE_SYSTEM,
            'metadata' => $metadata,
        ]);
    }
}
