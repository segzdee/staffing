<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-013: Availability Forecasting - Prediction Model
 *
 * Stores predicted availability for workers on future dates.
 * Predictions are made based on historical patterns and various factors.
 *
 * @property int $id
 * @property int $user_id
 * @property \Carbon\Carbon $prediction_date
 * @property float $morning_probability 6am-12pm
 * @property float $afternoon_probability 12pm-6pm
 * @property float $evening_probability 6pm-12am
 * @property float $night_probability 12am-6am
 * @property float $overall_probability
 * @property array|null $factors
 * @property bool|null $was_accurate
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 */
class AvailabilityPrediction extends Model
{
    use HasFactory;

    /**
     * Time slot constants.
     */
    public const SLOT_MORNING = 'morning'; // 6am-12pm

    public const SLOT_AFTERNOON = 'afternoon'; // 12pm-6pm

    public const SLOT_EVENING = 'evening'; // 6pm-12am

    public const SLOT_NIGHT = 'night'; // 12am-6am

    /**
     * Time slot definitions (hour ranges).
     */
    public const TIME_SLOTS = [
        self::SLOT_MORNING => ['start' => 6, 'end' => 12],
        self::SLOT_AFTERNOON => ['start' => 12, 'end' => 18],
        self::SLOT_EVENING => ['start' => 18, 'end' => 24],
        self::SLOT_NIGHT => ['start' => 0, 'end' => 6],
    ];

    protected $fillable = [
        'user_id',
        'prediction_date',
        'morning_probability',
        'afternoon_probability',
        'evening_probability',
        'night_probability',
        'overall_probability',
        'factors',
        'was_accurate',
    ];

    protected $casts = [
        'prediction_date' => 'date',
        'morning_probability' => 'decimal:4',
        'afternoon_probability' => 'decimal:4',
        'evening_probability' => 'decimal:4',
        'night_probability' => 'decimal:4',
        'overall_probability' => 'decimal:4',
        'factors' => 'array',
        'was_accurate' => 'boolean',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the user (worker) this prediction belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get overall probability as percentage.
     */
    public function getOverallPercentAttribute(): float
    {
        return round($this->overall_probability * 100, 1);
    }

    /**
     * Get all time slot probabilities as array.
     */
    public function getSlotProbabilitiesAttribute(): array
    {
        return [
            self::SLOT_MORNING => $this->morning_probability,
            self::SLOT_AFTERNOON => $this->afternoon_probability,
            self::SLOT_EVENING => $this->evening_probability,
            self::SLOT_NIGHT => $this->night_probability,
        ];
    }

    /**
     * Get best time slot for this prediction.
     */
    public function getBestSlotAttribute(): string
    {
        $probabilities = $this->slot_probabilities;
        $maxSlot = array_keys($probabilities, max($probabilities))[0];

        return $maxSlot;
    }

    /**
     * Get prediction strength label.
     */
    public function getStrengthLabelAttribute(): string
    {
        $prob = $this->overall_probability;

        if ($prob >= 0.8) {
            return 'Very Likely';
        }
        if ($prob >= 0.6) {
            return 'Likely';
        }
        if ($prob >= 0.4) {
            return 'Moderate';
        }
        if ($prob >= 0.2) {
            return 'Unlikely';
        }

        return 'Very Unlikely';
    }

    /**
     * Check if prediction date is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->prediction_date->isPast();
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get predictions for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get predictions for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return $query->where('prediction_date', $dateString);
    }

    /**
     * Scope to get predictions for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('prediction_date', [
            $startDate instanceof Carbon ? $startDate->toDateString() : $startDate,
            $endDate instanceof Carbon ? $endDate->toDateString() : $endDate,
        ]);
    }

    /**
     * Scope to get future predictions only.
     */
    public function scopeFuture($query)
    {
        return $query->where('prediction_date', '>=', Carbon::today());
    }

    /**
     * Scope to get past predictions.
     */
    public function scopePast($query)
    {
        return $query->where('prediction_date', '<', Carbon::today());
    }

    /**
     * Scope to get predictions with high probability.
     */
    public function scopeHighProbability($query, float $threshold = 0.7)
    {
        return $query->where('overall_probability', '>=', $threshold);
    }

    /**
     * Scope to get predictions for a specific time slot.
     */
    public function scopeForTimeSlot($query, string $slot, float $minProbability = 0.5)
    {
        $column = $slot.'_probability';

        return $query->where($column, '>=', $minProbability);
    }

    /**
     * Scope to get predictions needing accuracy update.
     */
    public function scopeNeedingAccuracyUpdate($query)
    {
        return $query->whereNull('was_accurate')
            ->where('prediction_date', '<', Carbon::today());
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Get probability for a specific time.
     */
    public function getProbabilityForTime(string $time): float
    {
        $hour = (int) Carbon::parse($time)->format('H');

        return $this->getProbabilityForHour($hour);
    }

    /**
     * Get probability for a specific hour.
     */
    public function getProbabilityForHour(int $hour): float
    {
        $slot = $this->getSlotForHour($hour);

        return match ($slot) {
            self::SLOT_MORNING => $this->morning_probability,
            self::SLOT_AFTERNOON => $this->afternoon_probability,
            self::SLOT_EVENING => $this->evening_probability,
            self::SLOT_NIGHT => $this->night_probability,
            default => 0,
        };
    }

    /**
     * Get time slot for a specific hour.
     */
    public static function getSlotForHour(int $hour): string
    {
        foreach (self::TIME_SLOTS as $slot => $range) {
            if ($hour >= $range['start'] && $hour < $range['end']) {
                return $slot;
            }
        }

        // Handle midnight edge case
        if ($hour >= 0 && $hour < 6) {
            return self::SLOT_NIGHT;
        }

        return self::SLOT_MORNING;
    }

    /**
     * Mark prediction as accurate or not.
     */
    public function markAccuracy(bool $wasAccurate): void
    {
        $this->was_accurate = $wasAccurate;
        $this->save();
    }

    /**
     * Add a factor that influenced this prediction.
     */
    public function addFactor(string $name, $value, float $weight = 1.0): void
    {
        $factors = $this->factors ?? [];
        $factors[$name] = [
            'value' => $value,
            'weight' => $weight,
        ];
        $this->factors = $factors;
        $this->save();
    }

    /**
     * Get factor influence summary.
     */
    public function getFactorsSummary(): array
    {
        $factors = $this->factors ?? [];
        $summary = [];

        foreach ($factors as $name => $data) {
            $summary[] = [
                'name' => $name,
                'value' => $data['value'] ?? $data,
                'weight' => $data['weight'] ?? 1.0,
            ];
        }

        return $summary;
    }
}
