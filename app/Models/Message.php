<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $from_user_id
 * @property int $to_user_id
 * @property string $message
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property string|null $attachment_url
 * @property string|null $attachment_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User|null $recipient
 * @property-read \App\Models\User|null $sender
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
 * @mixin \Eloquent
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'from_user_id',
        'to_user_id',
        'message',
        'is_read',
        'read_at',
        'attachment_url',
        'attachment_type',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the recipient of this message.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Mark message as read.
     */
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Check if message has attachment.
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_url);
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
}
