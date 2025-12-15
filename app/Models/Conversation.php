<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $shift_id
 * @property int $worker_id
 * @property int $business_id
 * @property string|null $subject
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $business
 * @property-read \App\Models\Message|null $lastMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User|null $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation forUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereBusinessId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereWorkerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation withUnreadFor($userId)
 * @mixin \Eloquent
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'worker_id',
        'business_id',
        'subject',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the shift related to this conversation.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker in this conversation.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business in this conversation.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the last message in this conversation.
     */
    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /**
     * Get unread messages for a specific user.
     */
    public function unreadMessagesFor($userId)
    {
        return $this->messages()->where('to_user_id', $userId)->where('is_read', false);
    }

    /**
     * Mark all messages as read for a user.
     */
    public function markAsReadFor($userId)
    {
        $this->messages()
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    /**
     * Check if user is participant in conversation.
     */
    public function hasParticipant($userId)
    {
        return $this->worker_id === $userId || $this->business_id === $userId;
    }

    /**
     * Get the other participant (not the current user).
     */
    public function getOtherParticipant($userId)
    {
        return $this->worker_id === $userId ? $this->business : $this->worker;
    }

    /**
     * Scope: Active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
            $q->where('worker_id', $userId)
              ->orWhere('business_id', $userId);
        });
    }

    /**
     * Scope: With unread messages for user
     */
    public function scopeWithUnreadFor($query, $userId)
    {
        return $query->whereHas('messages', function($q) use ($userId) {
            $q->where('to_user_id', $userId)
              ->where('is_read', false);
        });
    }
}
