<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $worker_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property string|null $reason
 * @property string|null $notes
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $worker
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate forDateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkerBlackoutDate whereWorkerId($value)
 * @mixin \Eloquent
 */
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
