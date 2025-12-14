<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
