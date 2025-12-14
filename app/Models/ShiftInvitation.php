<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
