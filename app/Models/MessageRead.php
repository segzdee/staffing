<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * COM-001: Message Read Model
 *
 * Tracks when individual messages are read by users.
 * Provides granular read receipt functionality.
 *
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $read_at
 * @property-read \App\Models\Message $message
 * @property-read \App\Models\User $user
 */
class MessageRead extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Get the message that was read.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who read the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a message read event.
     */
    public static function recordRead(int $messageId, int $userId): self
    {
        return self::firstOrCreate(
            [
                'message_id' => $messageId,
                'user_id' => $userId,
            ],
            [
                'read_at' => now(),
            ]
        );
    }

    /**
     * Record multiple message reads at once.
     *
     * @param  array<int>  $messageIds
     */
    public static function recordBulkRead(array $messageIds, int $userId): int
    {
        $now = now();
        $count = 0;

        foreach ($messageIds as $messageId) {
            $created = self::firstOrCreate(
                [
                    'message_id' => $messageId,
                    'user_id' => $userId,
                ],
                [
                    'read_at' => $now,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if a message has been read by a user.
     */
    public static function hasRead(int $messageId, int $userId): bool
    {
        return self::where('message_id', $messageId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get read count for a message.
     */
    public static function readCount(int $messageId): int
    {
        return self::where('message_id', $messageId)->count();
    }

    /**
     * Scope: For specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific message.
     */
    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }
}
