<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
