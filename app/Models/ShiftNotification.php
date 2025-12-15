<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int|null $assignment_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property array<array-key, mixed>|null $data
 * @property bool $sent_push
 * @property bool $sent_email
 * @property bool $sent_sms
 * @property bool $read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ShiftAssignment|null $assignment
 * @property-read \App\Models\Shift|null $shift
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification forUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification recent($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereAssignmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereSentEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereSentPush($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereSentSms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftNotification whereUserId($value)
 * @mixin \Eloquent
 */
class ShiftNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'assignment_id',
        'type',
        'title',
        'message',
        'data',
        'sent_push',
        'sent_email',
        'sent_sms',
        'read',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_push' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shift relationship
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Assignment relationship
     */
    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class);
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

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    /**
     * Scope: For specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Recent notifications
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
