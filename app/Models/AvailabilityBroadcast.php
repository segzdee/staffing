<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
