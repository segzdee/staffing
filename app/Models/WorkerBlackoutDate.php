<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkerBlackoutDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'start_date',
        'end_date',
        'reason',
        'notes',
        'type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Worker relationship
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Check if date falls within blackout period
     */
    public function includesDate($date)
    {
        $dateCarbon = Carbon::parse($date);

        return $dateCarbon->between($this->start_date, $this->end_date);
    }

    /**
     * Check if blackout is currently active
     */
    public function isActive()
    {
        $now = Carbon::now();

        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Check if blackout overlaps with a date range
     */
    public function overlaps($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $this->start_date->lte($end) && $this->end_date->gte($start);
    }

    /**
     * Scope: Active blackout dates
     */
    public function scopeActive($query)
    {
        return $query->where('end_date', '>=', Carbon::today());
    }

    /**
     * Scope: Upcoming blackout dates
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', Carbon::today())
            ->orderBy('start_date', 'asc');
    }

    /**
     * Scope: For specific date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $query->where(function($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end])
              ->orWhere(function($q2) use ($start, $end) {
                  $q2->where('start_date', '<=', $start)
                     ->where('end_date', '>=', $end);
              });
        });
    }

    /**
     * Get number of days in blackout period
     */
    public function getDurationDays()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
