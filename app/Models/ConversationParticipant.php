<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-001: Conversation Participant Model
 *
 * Represents a user's participation in a conversation.
 * Supports multi-participant conversations with roles.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $last_read_at
 * @property bool $is_muted
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Conversation $conversation
 * @property-read \App\Models\User $user
 */
class ConversationParticipant extends Model
{
    use HasFactory;

    public const ROLE_OWNER = 'owner';

    public const ROLE_PARTICIPANT = 'participant';

    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_at',
        'is_muted',
        'left_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_read_at' => 'datetime',
        'left_at' => 'datetime',
        'is_muted' => 'boolean',
    ];

    /**
     * Get the conversation this participation belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who is participating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the participant is still active (hasn't left).
     */
    public function isActive(): bool
    {
        return is_null($this->left_at);
    }

    /**
     * Check if the participant is the owner.
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if the participant is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if the participant can manage the conversation.
     */
    public function canManage(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * Mark the conversation as read up to now.
     */
    public function markAsRead(): self
    {
        $this->update(['last_read_at' => now()]);

        return $this;
    }

    /**
     * Toggle mute status.
     */
    public function toggleMute(): self
    {
        $this->update(['is_muted' => ! $this->is_muted]);

        return $this;
    }

    /**
     * Leave the conversation.
     */
    public function leave(): self
    {
        $this->update(['left_at' => now()]);

        return $this;
    }

    /**
     * Rejoin the conversation.
     */
    public function rejoin(): self
    {
        $this->update(['left_at' => null]);

        return $this;
    }

    /**
     * Get unread messages count for this participant.
     */
    public function getUnreadCountAttribute(): int
    {
        $query = $this->conversation->messages()
            ->where('from_user_id', '!=', $this->user_id);

        if ($this->last_read_at) {
            $query->where('created_at', '>', $this->last_read_at);
        }

        return $query->count();
    }

    /**
     * Scope: Active participants (haven't left).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    /**
     * Scope: With specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope: Not muted.
     */
    public function scopeNotMuted($query)
    {
        return $query->where('is_muted', false);
    }
}
