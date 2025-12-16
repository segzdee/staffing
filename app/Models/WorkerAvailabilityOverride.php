<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Worker Availability Override Model
 * STAFF-REG-009: Worker Availability Setup
 *
 * Stores specific date overrides for worker availability.
 */
class WorkerAvailabilityOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'type',
        'start_time',
        'end_time',
        'is_one_time',
        'reason',
        'notes',
        'priority',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_one_time' => 'boolean',
    ];

    /**
     * Override types.
     */
    public const TYPES = [
        'available' => 'Available',
        'unavailable' => 'Unavailable',
        'custom' => 'Custom Hours',
    ];

    /**
     * Get the user that owns the override.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this override applies to a specific date.
     */
    public function appliesToDate(Carbon $date): bool
    {
        return $this->date->isSameDay($date);
    }

    /**
     * Check if worker is available at a specific time on this override date.
     */
    public function isAvailableAt(string $time): bool
    {
        if ($this->type === 'unavailable') {
            return false;
        }

        if ($this->type === 'available' && !$this->start_time && !$this->end_time) {
            return true; // Available all day
        }

        if ($this->start_time && $this->end_time) {
            $checkTime = Carbon::parse($time);
            $startTime = Carbon::parse($this->start_time);
            $endTime = Carbon::parse($this->end_time);

            return $checkTime->between($startTime, $endTime);
        }

        return $this->type === 'available';
    }

    /**
     * Get the duration of availability in hours.
     */
    public function getDurationHours(): ?float
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->floatDiffInHours($end);
    }

    /**
     * Scope: Active overrides (future or today).
     */
    public function scopeActive($query)
    {
        return $query->where('date', '>=', Carbon::today());
    }

    /**
     * Scope: For specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', Carbon::parse($date)->toDateString());
    }

    /**
     * Scope: For date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [
            Carbon::parse($startDate)->toDateString(),
            Carbon::parse($endDate)->toDateString(),
        ]);
    }

    /**
     * Scope: Available overrides only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('type', '!=', 'unavailable');
    }

    /**
     * Scope: Unavailable overrides only.
     */
    public function scopeUnavailable($query)
    {
        return $query->where('type', 'unavailable');
    }

    /**
     * Scope: Ordered by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Get the highest priority override for a user on a specific date.
     */
    public static function getForUserAndDate(int $userId, Carbon $date): ?self
    {
        return self::where('user_id', $userId)
            ->forDate($date)
            ->byPriority()
            ->first();
    }
}
