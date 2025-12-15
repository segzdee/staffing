<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $worker_id
 * @property string $broadcast_type
 * @property \Illuminate\Support\Carbon $available_from
 * @property \Illuminate\Support\Carbon $available_to
 * @property array<array-key, mixed>|null $industries
 * @property int|null $max_distance
 * @property string|null $message
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereAvailableFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereAvailableTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereBroadcastType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereIndustries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereMaxDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AvailabilityBroadcast whereWorkerId($value)
 * @mixin \Eloquent
 */
class AvailabilityBroadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'broadcast_type',
        'available_from',
        'available_to',
        'industries',
        'max_distance',
        'message',
        'status',
    ];

    protected $casts = [
        'available_from' => 'datetime',
        'available_to' => 'datetime',
        'industries' => 'array',
        'max_distance' => 'integer',
    ];

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function isActive()
    {
        return $this->status === 'active'
            && $this->available_from->isPast()
            && $this->available_to->isFuture();
    }

    public function expire()
    {
        $this->update(['status' => 'expired']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('available_from', '<=', now())
            ->where('available_to', '>=', now());
    }
}
