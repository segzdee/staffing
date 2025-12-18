<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * COM-001: Enhanced Conversation Model
 *
 * Supports both legacy worker-business direct conversations
 * and new multi-participant conversations with types.
 *
 * @property int $id
 * @property string $type
 * @property int|null $shift_id
 * @property int $worker_id
 * @property int $business_id
 * @property string|null $subject
 * @property string $status
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $business
 * @property-read \App\Models\Message|null $lastMessage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $messages
 * @property-read int|null $messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ConversationParticipant> $participants
 * @property-read int|null $participants_count
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User|null $worker
 *
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
 *
 * @mixin \Eloquent
 */
class Conversation extends Model
{
    use HasFactory;

    // Conversation types
    public const TYPE_DIRECT = 'direct';

    public const TYPE_SHIFT = 'shift';

    public const TYPE_SUPPORT = 'support';

    public const TYPE_BROADCAST = 'broadcast';

    // Conversation statuses
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'type',
        'shift_id',
        'worker_id',
        'business_id',
        'subject',
        'status',
        'is_archived',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Get the shift related to this conversation.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker in this conversation (legacy support).
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the business in this conversation (legacy support).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * COM-001: Get all participants in this conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * COM-001: Get active participants (haven't left).
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    /**
     * COM-001: Get participant users directly.
     */
    public function participantUsers()
    {
        return User::whereIn('id', $this->participants()->pluck('user_id'));
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the last message in this conversation.
     */
    public function lastMessage(): HasOne
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
    public function markAsReadFor($userId): void
    {
        // Update legacy is_read field
        $this->messages()
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        // Update participant's last_read_at
        $this->participants()
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Check if user is participant in conversation.
     * Supports both legacy (worker/business) and new (participants) models.
     */
    public function hasParticipant($userId): bool
    {
        // Check legacy columns first
        if ($this->worker_id === $userId || $this->business_id === $userId) {
            return true;
        }

        // Check participants table
        return $this->participants()->where('user_id', $userId)->whereNull('left_at')->exists();
    }

    /**
     * Get the other participant (not the current user).
     * For legacy direct conversations only.
     */
    public function getOtherParticipant($userId)
    {
        return $this->worker_id === $userId ? $this->business : $this->worker;
    }

    /**
     * COM-001: Get all other participants (not the current user).
     * For multi-participant conversations.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOtherParticipants($userId)
    {
        return $this->activeParticipants()
            ->where('user_id', '!=', $userId)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    /**
     * COM-001: Get participant record for a user.
     */
    public function getParticipant($userId): ?ConversationParticipant
    {
        return $this->participants()->where('user_id', $userId)->first();
    }

    /**
     * COM-001: Add a participant to the conversation.
     */
    public function addParticipant(User $user, string $role = ConversationParticipant::ROLE_PARTICIPANT): ConversationParticipant
    {
        return $this->participants()->firstOrCreate(
            ['user_id' => $user->id],
            ['role' => $role]
        );
    }

    /**
     * COM-001: Remove a participant from the conversation.
     */
    public function removeParticipant(User $user): bool
    {
        return $this->participants()
            ->where('user_id', $user->id)
            ->update(['left_at' => now()]) > 0;
    }

    /**
     * COM-001: Get unread count for a user.
     */
    public function getUnreadCountFor($userId): int
    {
        $participant = $this->getParticipant($userId);

        if (! $participant) {
            // Fall back to legacy check
            return $this->messages()
                ->where('to_user_id', $userId)
                ->where('is_read', false)
                ->count();
        }

        $query = $this->messages()
            ->where('from_user_id', '!=', $userId);

        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }

    /**
     * COM-001: Check if this is a direct (1:1) conversation.
     */
    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    /**
     * COM-001: Check if this is a shift conversation.
     */
    public function isShiftConversation(): bool
    {
        return $this->type === self::TYPE_SHIFT;
    }

    /**
     * COM-001: Check if this is a support conversation.
     */
    public function isSupport(): bool
    {
        return $this->type === self::TYPE_SUPPORT;
    }

    /**
     * COM-001: Check if this is a broadcast conversation.
     */
    public function isBroadcast(): bool
    {
        return $this->type === self::TYPE_BROADCAST;
    }

    /**
     * Scope: Active conversations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: For specific user (supports both legacy and participant-based)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // Legacy support
            $q->where('worker_id', $userId)
                ->orWhere('business_id', $userId)
                // New participant-based
                ->orWhereHas('participants', function ($pq) use ($userId) {
                    $pq->where('user_id', $userId)->whereNull('left_at');
                });
        });
    }

    /**
     * Scope: Not archived
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: Archived
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: With unread messages for user
     */
    public function scopeWithUnreadFor($query, $userId)
    {
        return $query->whereHas('messages', function ($q) use ($userId) {
            $q->where('to_user_id', $userId)
                ->where('is_read', false);
        });
    }

    /**
     * COM-001: Archive the conversation.
     */
    public function archive(): bool
    {
        return $this->update(['is_archived' => true]);
    }

    /**
     * COM-001: Unarchive the conversation.
     */
    public function unarchive(): bool
    {
        return $this->update(['is_archived' => false]);
    }

    /**
     * COM-001: Close the conversation.
     */
    public function close(): bool
    {
        return $this->update(['status' => self::STATUS_CLOSED]);
    }

    /**
     * COM-001: Reopen a closed conversation.
     */
    public function reopen(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * COM-001: Update the last message timestamp.
     */
    public function touchLastMessage(): bool
    {
        return $this->update(['last_message_at' => now()]);
    }

    /**
     * COM-001: Get the display name for the conversation.
     */
    public function getDisplayNameFor($userId): string
    {
        if ($this->subject) {
            return $this->subject;
        }

        // For direct conversations, show the other person's name
        if ($this->isDirect()) {
            $other = $this->getOtherParticipant($userId);

            return $other ? $other->name : 'Unknown';
        }

        // For multi-participant, show participant names
        $others = $this->getOtherParticipants($userId);
        if ($others->count() === 0) {
            return 'Empty Conversation';
        }
        if ($others->count() === 1) {
            return $others->first()->name;
        }
        if ($others->count() <= 3) {
            return $others->pluck('name')->join(', ');
        }

        return $others->take(2)->pluck('name')->join(', ').' + '.($others->count() - 2).' others';
    }
}
