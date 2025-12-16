<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * BIZ-REG-006: Venue Operating Hours Model
 *
 * Stores operating hours for each day of the week.
 * Supports multiple time slots per day.
 *
 * @property int $id
 * @property int $venue_id
 * @property int $day_of_week
 * @property string $open_time
 * @property string $close_time
 * @property bool $is_primary
 * @property bool $is_open
 * @property string|null $notes
 */
class VenueOperatingHour extends Model
{
    use HasFactory;

    /**
     * Day names for display.
     */
    public const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Short day names.
     */
    public const DAY_NAMES_SHORT = [
        0 => 'Sun',
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
    ];

    protected $fillable = [
        'venue_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_primary',
        'is_open',
        'notes',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_primary' => 'boolean',
        'is_open' => 'boolean',
    ];

    protected $appends = [
        'day_name',
        'day_name_short',
        'formatted_hours',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the venue these hours belong to.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get day name.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get short day name.
     */
    public function getDayNameShortAttribute(): string
    {
        return self::DAY_NAMES_SHORT[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get formatted hours string.
     */
    public function getFormattedHoursAttribute(): string
    {
        if (!$this->is_open) {
            return 'Closed';
        }

        $open = Carbon::parse($this->open_time)->format('g:i A');
        $close = Carbon::parse($this->close_time)->format('g:i A');

        return "{$open} - {$close}";
    }

    /**
     * Get duration in hours.
     */
    public function getDurationHoursAttribute(): float
    {
        if (!$this->is_open) {
            return 0;
        }

        $open = Carbon::parse($this->open_time);
        $close = Carbon::parse($this->close_time);

        // Handle overnight hours
        if ($close->lt($open)) {
            $close->addDay();
        }

        return $open->diffInMinutes($close) / 60;
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for open days only.
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    /**
     * Scope for specific day.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope for weekdays.
     */
    public function scopeWeekdays($query)
    {
        return $query->whereIn('day_of_week', [1, 2, 3, 4, 5]);
    }

    /**
     * Scope for weekends.
     */
    public function scopeWeekends($query)
    {
        return $query->whereIn('day_of_week', [0, 6]);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Check if a time falls within these hours.
     */
    public function containsTime(string $time): bool
    {
        if (!$this->is_open) {
            return false;
        }

        $checkTime = Carbon::parse($time);
        $open = Carbon::parse($this->open_time);
        $close = Carbon::parse($this->close_time);

        // Handle overnight hours
        if ($close->lt($open)) {
            // Overnight: check if time is after open OR before close
            return $checkTime->gte($open) || $checkTime->lte($close);
        }

        return $checkTime->gte($open) && $checkTime->lte($close);
    }

    /**
     * Check if a time range overlaps with these hours.
     */
    public function overlapsWithRange(string $startTime, string $endTime): bool
    {
        if (!$this->is_open) {
            return false;
        }

        $rangeStart = Carbon::parse($startTime);
        $rangeEnd = Carbon::parse($endTime);
        $open = Carbon::parse($this->open_time);
        $close = Carbon::parse($this->close_time);

        // Simple overlap check
        return $rangeStart->lt($close) && $rangeEnd->gt($open);
    }

    /**
     * Create default operating hours for a venue (9-5, Mon-Fri).
     */
    public static function createDefaultHours(int $venueId): void
    {
        for ($day = 0; $day < 7; $day++) {
            $isWeekday = $day >= 1 && $day <= 5;

            self::create([
                'venue_id' => $venueId,
                'day_of_week' => $day,
                'open_time' => '09:00:00',
                'close_time' => '17:00:00',
                'is_primary' => true,
                'is_open' => $isWeekday,
            ]);
        }
    }

    /**
     * Create 24/7 hours for a venue.
     */
    public static function create247Hours(int $venueId): void
    {
        for ($day = 0; $day < 7; $day++) {
            self::create([
                'venue_id' => $venueId,
                'day_of_week' => $day,
                'open_time' => '00:00:00',
                'close_time' => '23:59:59',
                'is_primary' => true,
                'is_open' => true,
            ]);
        }
    }
}
