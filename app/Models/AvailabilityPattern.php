<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-013: Availability Forecasting - Pattern Model
 *
 * Stores learned patterns from worker historical availability data.
 * Used for ML-based availability prediction.
 *
 * @property int $id
 * @property int $user_id
 * @property int $day_of_week 0-6 (Sunday-Saturday)
 * @property string|null $typical_start_time
 * @property string|null $typical_end_time
 * @property float $availability_probability 0.0000-1.0000
 * @property int $historical_shifts_count
 * @property int $historical_available_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 */
class AvailabilityPattern extends Model
{
    use HasFactory;

    /**
     * Day of week constants matching PHP Carbon convention.
     */
    public const SUNDAY = 0;

    public const MONDAY = 1;

    public const TUESDAY = 2;

    public const WEDNESDAY = 3;

    public const THURSDAY = 4;

    public const FRIDAY = 5;

    public const SATURDAY = 6;

    /**
     * Day names for display.
     */
    public const DAY_NAMES = [
        self::SUNDAY => 'Sunday',
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
    ];

    protected $fillable = [
        'user_id',
        'day_of_week',
        'typical_start_time',
        'typical_end_time',
        'availability_probability',
        'historical_shifts_count',
        'historical_available_count',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'availability_probability' => 'decimal:4',
        'historical_shifts_count' => 'integer',
        'historical_available_count' => 'integer',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the user (worker) this pattern belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the day name for display.
     */
    public function getDayNameAttribute(): string
    {
        return self::DAY_NAMES[$this->day_of_week] ?? 'Unknown';
    }

    /**
     * Get probability as percentage.
     */
    public function getProbabilityPercentAttribute(): float
    {
        return round($this->availability_probability * 100, 2);
    }

    /**
     * Get formatted typical hours.
     */
    public function getTypicalHoursAttribute(): ?string
    {
        if (! $this->typical_start_time || ! $this->typical_end_time) {
            return null;
        }

        $start = \Carbon\Carbon::parse($this->typical_start_time)->format('g:i A');
        $end = \Carbon\Carbon::parse($this->typical_end_time)->format('g:i A');

        return "{$start} - {$end}";
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get patterns for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get patterns for a specific day.
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope to get patterns with high probability (>= 0.7).
     */
    public function scopeHighProbability($query, float $threshold = 0.7)
    {
        return $query->where('availability_probability', '>=', $threshold);
    }

    /**
     * Scope to get patterns with sufficient historical data.
     */
    public function scopeWithSufficientData($query, int $minShifts = 5)
    {
        return $query->where('historical_shifts_count', '>=', $minShifts);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Update pattern with new data point.
     */
    public function addDataPoint(bool $wasAvailable, ?string $startTime = null, ?string $endTime = null): void
    {
        $this->historical_shifts_count++;

        if ($wasAvailable) {
            $this->historical_available_count++;
        }

        // Recalculate probability
        $this->availability_probability = $this->historical_shifts_count > 0
            ? $this->historical_available_count / $this->historical_shifts_count
            : 0;

        // Update typical times using weighted average
        if ($wasAvailable && $startTime && $endTime) {
            $this->updateTypicalTimes($startTime, $endTime);
        }

        $this->save();
    }

    /**
     * Update typical start/end times.
     */
    protected function updateTypicalTimes(string $startTime, string $endTime): void
    {
        if (! $this->typical_start_time) {
            $this->typical_start_time = $startTime;
            $this->typical_end_time = $endTime;

            return;
        }

        // Use exponential moving average for time updates
        $weight = 0.2; // New data gets 20% weight

        $currentStart = \Carbon\Carbon::parse($this->typical_start_time);
        $newStart = \Carbon\Carbon::parse($startTime);
        $currentEnd = \Carbon\Carbon::parse($this->typical_end_time);
        $newEnd = \Carbon\Carbon::parse($endTime);

        // Calculate weighted average in minutes from midnight
        $avgStartMinutes = (int) ((1 - $weight) * ($currentStart->hour * 60 + $currentStart->minute)
            + $weight * ($newStart->hour * 60 + $newStart->minute));
        $avgEndMinutes = (int) ((1 - $weight) * ($currentEnd->hour * 60 + $currentEnd->minute)
            + $weight * ($newEnd->hour * 60 + $newEnd->minute));

        $this->typical_start_time = sprintf('%02d:%02d:00', (int) ($avgStartMinutes / 60), $avgStartMinutes % 60);
        $this->typical_end_time = sprintf('%02d:%02d:00', (int) ($avgEndMinutes / 60), $avgEndMinutes % 60);
    }

    /**
     * Check if pattern is reliable (has sufficient data).
     */
    public function isReliable(int $minShifts = 5): bool
    {
        return $this->historical_shifts_count >= $minShifts;
    }

    /**
     * Get confidence level based on data quantity.
     */
    public function getConfidenceLevel(): string
    {
        if ($this->historical_shifts_count < 3) {
            return 'very_low';
        }
        if ($this->historical_shifts_count < 5) {
            return 'low';
        }
        if ($this->historical_shifts_count < 10) {
            return 'medium';
        }
        if ($this->historical_shifts_count < 20) {
            return 'high';
        }

        return 'very_high';
    }
}
