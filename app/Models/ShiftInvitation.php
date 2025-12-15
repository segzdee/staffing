<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $shift_id
 * @property int $worker_id
 * @property int $invited_by
 * @property string|null $message
 * @property string $status
 * @property \Illuminate\Support\Carbon $sent_at
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $inviter
 * @property-read \App\Models\Shift $shift
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShiftInvitation whereWorkerId($value)
 * @mixin \Eloquent
 */
class ShiftInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'worker_id',
        'invited_by',
        'message',
        'status',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now()
        ]);
    }

    public function decline()
    {
        $this->update([
            'status' => 'declined',
            'responded_at' => now()
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
