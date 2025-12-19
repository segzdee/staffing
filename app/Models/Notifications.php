<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $destination
 * @property int $author
 * @property int $type
 * @property int|null $target
 * @property bool $read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notifications whereType($value)
 *
 * @mixin \Eloquent
 */
class Notifications extends Model
{
    protected $guarded = ['id'];

    const UPDATED_AT = null;

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Notifications>
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Override to sync read and read_at columns
     */
    public function setReadAttribute($value)
    {
        $this->attributes['read'] = $value;
        // Sync read_at with read boolean
        $this->attributes['read_at'] = $value ? now() : null;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    public static function send($destination, $session_id, $type, $target)
    {
        $user = User::find($destination);

        if ($type == 5 && $user->notify_new_tip == 'no'
                || $type == 6 && $user->notify_new_ppv == 'no') {
            return false;
        }

        self::create([
            'destination' => $destination,
            'author' => $session_id,
            'type' => $type,
            'target' => $target,
            'read' => false,
            'read_at' => null,
        ]);
    }
}
