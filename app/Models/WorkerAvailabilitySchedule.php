<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkerAvailabilitySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
        'preferred_shift_types',
        'recurrence',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_available' => 'boolean',
        'preferred_shift_types' => 'array',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    /**
     * Worker relationship
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Check if schedule is currently active
     */
    public function isActive()
    {
        $now = Carbon::now();

        if ($this->effective_from && $now->isBefore($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && $now->isAfter($this->effective_until)) {
            return false;
        }

        return true;
    }

    /**
     * Check if worker is available at a specific time
     */
    public function isAvailableAt($dayOfWeek, $time)
    {
        if (!$this->is_available || $this->day_of_week !== strtolower($dayOfWeek)) {
            return false;
        }

        $timeCarbon = Carbon::parse($time);
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        return $timeCarbon->between($startTime, $endTime);
    }

    /**
     * Scope: Active schedules
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();

        return $query->where('is_available', true)
            ->where(function($q) use ($now) {
                $q->whereNull('effective_from')
                  ->orWhere('effective_from', '<=', $now);
            })
            ->where(function($q) use ($now) {
                $q->whereNull('effective_until')
                  ->orWhere('effective_until', '>=', $now);
            });
    }

    /**
     * Scope: For specific day
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', strtolower($dayOfWeek));
    }
}
