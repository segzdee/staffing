<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SAF-004: Venue Safety Rating Model
 *
 * Represents a worker's safety rating for a venue after completing a shift.
 * Tracks overall safety and specific safety aspects like lighting, parking,
 * emergency exits, staff support, and equipment condition.
 *
 * @property int $id
 * @property int $venue_id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int $overall_safety
 * @property int|null $lighting_rating
 * @property int|null $parking_safety
 * @property int|null $emergency_exits
 * @property int|null $staff_support
 * @property int|null $equipment_condition
 * @property string|null $safety_concerns
 * @property string|null $positive_notes
 * @property bool $would_return
 * @property bool $is_anonymous
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class VenueSafetyRating extends Model
{
    use HasFactory;

    /**
     * The rating scale constants.
     */
    public const RATING_MIN = 1;

    public const RATING_MAX = 5;

    /**
     * Rating labels for display.
     */
    public const RATING_LABELS = [
        1 => 'Very Poor',
        2 => 'Poor',
        3 => 'Average',
        4 => 'Good',
        5 => 'Excellent',
    ];

    /**
     * Safety aspects that can be rated.
     */
    public const SAFETY_ASPECTS = [
        'lighting_rating' => 'Lighting',
        'parking_safety' => 'Parking Safety',
        'emergency_exits' => 'Emergency Exits',
        'staff_support' => 'Staff Support',
        'equipment_condition' => 'Equipment Condition',
    ];

    protected $fillable = [
        'venue_id',
        'user_id',
        'shift_id',
        'overall_safety',
        'lighting_rating',
        'parking_safety',
        'emergency_exits',
        'staff_support',
        'equipment_condition',
        'safety_concerns',
        'positive_notes',
        'would_return',
        'is_anonymous',
    ];

    protected $casts = [
        'overall_safety' => 'integer',
        'lighting_rating' => 'integer',
        'parking_safety' => 'integer',
        'emergency_exits' => 'integer',
        'staff_support' => 'integer',
        'equipment_condition' => 'integer',
        'would_return' => 'boolean',
        'is_anonymous' => 'boolean',
    ];

    protected $appends = [
        'overall_safety_label',
        'average_aspect_rating',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the venue this rating belongs to.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the user who submitted this rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift this rating is associated with.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the label for the overall safety rating.
     */
    public function getOverallSafetyLabelAttribute(): string
    {
        return self::RATING_LABELS[$this->overall_safety] ?? 'Unknown';
    }

    /**
     * Get the average of all aspect ratings.
     */
    public function getAverageAspectRatingAttribute(): ?float
    {
        $aspects = [
            $this->lighting_rating,
            $this->parking_safety,
            $this->emergency_exits,
            $this->staff_support,
            $this->equipment_condition,
        ];

        $validAspects = array_filter($aspects, fn ($v) => $v !== null);

        if (empty($validAspects)) {
            return null;
        }

        return round(array_sum($validAspects) / count($validAspects), 2);
    }

    /**
     * Get all aspect ratings as an array.
     */
    public function getAspectRatingsAttribute(): array
    {
        $ratings = [];

        foreach (self::SAFETY_ASPECTS as $field => $label) {
            if ($this->{$field} !== null) {
                $ratings[$field] = [
                    'label' => $label,
                    'value' => $this->{$field},
                    'value_label' => self::RATING_LABELS[$this->{$field}] ?? 'Unknown',
                ];
            }
        }

        return $ratings;
    }

    /**
     * Get display name for the user (respecting anonymity).
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous Worker';
        }

        return $this->user->name ?? 'Unknown User';
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope to get ratings for a specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope to get ratings by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get ratings with low safety scores.
     */
    public function scopeLowSafety($query, int $threshold = 3)
    {
        return $query->where('overall_safety', '<=', $threshold);
    }

    /**
     * Scope to get ratings with high safety scores.
     */
    public function scopeHighSafety($query, int $threshold = 4)
    {
        return $query->where('overall_safety', '>=', $threshold);
    }

    /**
     * Scope to get ratings where worker would not return.
     */
    public function scopeWouldNotReturn($query)
    {
        return $query->where('would_return', false);
    }

    /**
     * Scope to get ratings within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent ratings.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================
    // Static Methods
    // =========================================

    /**
     * Calculate the average safety score for a venue.
     */
    public static function calculateAverageForVenue(int $venueId): ?float
    {
        $average = self::forVenue($venueId)->avg('overall_safety');

        return $average ? round($average, 2) : null;
    }

    /**
     * Get rating distribution for a venue.
     */
    public static function getRatingDistribution(int $venueId): array
    {
        $distribution = self::forVenue($venueId)
            ->selectRaw('overall_safety, COUNT(*) as count')
            ->groupBy('overall_safety')
            ->pluck('count', 'overall_safety')
            ->toArray();

        // Fill in missing ratings with 0
        for ($i = self::RATING_MIN; $i <= self::RATING_MAX; $i++) {
            if (! isset($distribution[$i])) {
                $distribution[$i] = 0;
            }
        }

        ksort($distribution);

        return $distribution;
    }

    /**
     * Get aspect ratings summary for a venue.
     */
    public static function getAspectSummary(int $venueId): array
    {
        $summary = [];

        foreach (array_keys(self::SAFETY_ASPECTS) as $aspect) {
            $avg = self::forVenue($venueId)
                ->whereNotNull($aspect)
                ->avg($aspect);

            $summary[$aspect] = [
                'label' => self::SAFETY_ASPECTS[$aspect],
                'average' => $avg ? round($avg, 2) : null,
                'count' => self::forVenue($venueId)->whereNotNull($aspect)->count(),
            ];
        }

        return $summary;
    }
}
