<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftApplication extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shift_id',
        'worker_id',
        'status',
        'application_note',
        'applied_at',
        'responded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'applied_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the shift this application is for.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker who applied.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Check if application is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if application is accepted.
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if application is rejected.
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Accept the application.
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'responded_at' => now()
        ]);
    }

    /**
     * Reject the application.
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'responded_at' => now()
        ]);
    }

    /**
     * Withdraw the application.
     */
    public function withdraw()
    {
        $this->update([
            'status' => 'withdrawn',
            'responded_at' => now()
        ]);
    }

    /**
     * Scope for pending applications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for accepted applications.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for applications by worker.
     */
    public function scopeByWorker($query, $workerId)
    {
        return $query->where('worker_id', $workerId);
    }
}
