<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftAssignment extends Model
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
        'assigned_by',
        'check_in_time',
        'check_out_time',
        'hours_worked',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'hours_worked' => 'decimal:2',
    ];

    /**
     * Get the shift this assignment is for.
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the worker assigned.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the user who assigned the worker (business or agent).
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the payment for this assignment.
     */
    public function payment()
    {
        return $this->hasOne(ShiftPayment::class);
    }

    /**
     * Get the rating for this assignment.
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get swap requests for this assignment.
     */
    public function swapRequests()
    {
        return $this->hasMany(ShiftSwap::class);
    }

    /**
     * Check in to shift.
     */
    public function checkIn()
    {
        $this->update([
            'check_in_time' => now(),
            'status' => 'checked_in'
        ]);
    }

    /**
     * Check out from shift.
     */
    public function checkOut()
    {
        $checkOutTime = now();
        $hoursWorked = $this->check_in_time
            ? $this->check_in_time->diffInMinutes($checkOutTime) / 60
            : 0;

        $this->update([
            'check_out_time' => $checkOutTime,
            'hours_worked' => round($hoursWorked, 2),
            'status' => 'checked_out'
        ]);
    }

    /**
     * Mark as completed.
     */
    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    /**
     * Mark as no-show.
     */
    public function markNoShow()
    {
        $this->update(['status' => 'no_show']);
    }

    /**
     * Check if worker is checked in.
     */
    public function isCheckedIn()
    {
        return $this->status === 'checked_in';
    }

    /**
     * Check if worker is checked out.
     */
    public function isCheckedOut()
    {
        return $this->status === 'checked_out';
    }

    /**
     * Check if assignment is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Scope for active assignments.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assigned', 'checked_in']);
    }

    /**
     * Scope for completed assignments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
