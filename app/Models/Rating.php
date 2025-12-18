<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * WKR-004: Rating Model with 4-Category Rating System
 *
 * @property int $id
 * @property int $shift_assignment_id
 * @property int $rater_id
 * @property int $rated_id
 * @property string $rater_type
 * @property int $rating
 * @property int|null $punctuality_rating
 * @property int|null $quality_rating
 * @property int|null $professionalism_rating
 * @property int|null $reliability_rating
 * @property int|null $communication_rating
 * @property int|null $payment_reliability_rating
 * @property float|null $weighted_score
 * @property bool $is_flagged
 * @property string|null $flag_reason
 * @property \Illuminate\Support\Carbon|null $flagged_at
 * @property string|null $review_text
 * @property string|null $response_text
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property array<array-key, mixed>|null $categories
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ShiftAssignment $assignment
 * @property-read \App\Models\User $rated
 * @property-read \App\Models\User $rater
 * @property-read \App\Models\Shift|null $shift
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating forBusiness($businessId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating forWorker($workerId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating withCategories()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating lowRatings()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating flagged()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rating query()
 *
 * @mixin \Eloquent
 */
class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_assignment_id',
        'rater_id',
        'rated_id',
        'rater_type',
        'rating',
        'review_text',
        'categories',
        'response_text',
        'responded_at',
        // WKR-004: Category rating fields
        'punctuality_rating',
        'quality_rating',
        'professionalism_rating',
        'reliability_rating',
        'communication_rating',
        'payment_reliability_rating',
        'weighted_score',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'punctuality_rating' => 'integer',
        'quality_rating' => 'integer',
        'professionalism_rating' => 'integer',
        'reliability_rating' => 'integer',
        'communication_rating' => 'integer',
        'payment_reliability_rating' => 'integer',
        'weighted_score' => 'decimal:2',
        'is_flagged' => 'boolean',
        'flagged_at' => 'datetime',
        'categories' => 'array',
        'responded_at' => 'datetime',
    ];

    /**
     * Boot method to auto-calculate weighted score on save.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Rating $rating) {
            // Only calculate if category ratings are present and weighted_score is not set
            if ($rating->hasAnyCategoryRatings() && $rating->weighted_score === null) {
                $rating->weighted_score = $rating->calculateWeightedScore();
            }
        });
    }

    /**
     * Check if any category ratings are set.
     */
    public function hasAnyCategoryRatings(): bool
    {
        return $this->punctuality_rating !== null
            || $this->quality_rating !== null
            || $this->professionalism_rating !== null
            || $this->reliability_rating !== null
            || $this->communication_rating !== null
            || $this->payment_reliability_rating !== null;
    }

    /**
     * Calculate weighted score based on rater type and category weights.
     */
    public function calculateWeightedScore(): float
    {
        // Determine which category set to use based on rater type
        $type = $this->rater_type === 'business' ? 'worker' : 'business';
        $categories = config("ratings.{$type}_categories", []);

        $weightedSum = 0;
        $totalWeight = 0;

        // Map category keys to rating fields
        $categoryMap = $type === 'worker' ? [
            'punctuality' => $this->punctuality_rating,
            'quality' => $this->quality_rating,
            'professionalism' => $this->professionalism_rating,
            'reliability' => $this->reliability_rating,
        ] : [
            'punctuality' => $this->punctuality_rating,
            'communication' => $this->communication_rating,
            'professionalism' => $this->professionalism_rating,
            'payment_reliability' => $this->payment_reliability_rating,
        ];

        foreach ($categoryMap as $category => $value) {
            if ($value !== null && isset($categories[$category])) {
                $weight = $categories[$category]['weight'];
                $weightedSum += $value * $weight;
                $totalWeight += $weight;
            }
        }

        // Normalize if not all categories are present
        if ($totalWeight > 0 && $totalWeight < 1) {
            $weightedSum = $weightedSum / $totalWeight;
        }

        return round($weightedSum, 2);
    }

    /**
     * Get category ratings as an array.
     */
    public function getCategoryRatingsAttribute(): array
    {
        $type = $this->rater_type === 'business' ? 'worker' : 'business';

        if ($type === 'worker') {
            return [
                'punctuality' => $this->punctuality_rating,
                'quality' => $this->quality_rating,
                'professionalism' => $this->professionalism_rating,
                'reliability' => $this->reliability_rating,
            ];
        }

        return [
            'punctuality' => $this->punctuality_rating,
            'communication' => $this->communication_rating,
            'professionalism' => $this->professionalism_rating,
            'payment_reliability' => $this->payment_reliability_rating,
        ];
    }

    /**
     * Get the rating label (e.g., "Excellent", "Good").
     */
    public function getRatingLabelAttribute(): string
    {
        $labels = config('ratings.labels', []);

        return $labels[$this->rating] ?? 'Unknown';
    }

    /**
     * Get the lowest category rating value.
     */
    public function getLowestCategoryRatingAttribute(): ?int
    {
        $ratings = array_filter($this->category_ratings, fn ($r) => $r !== null);

        return ! empty($ratings) ? min($ratings) : null;
    }

    /**
     * Get the highest category rating value.
     */
    public function getHighestCategoryRatingAttribute(): ?int
    {
        $ratings = array_filter($this->category_ratings, fn ($r) => $r !== null);

        return ! empty($ratings) ? max($ratings) : null;
    }

    // ===== Relationships =====

    public function assignment()
    {
        return $this->belongsTo(ShiftAssignment::class, 'shift_assignment_id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * Alias for rater() - used in views for semantic clarity.
     */
    public function ratedBy()
    {
        return $this->rater();
    }

    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    /**
     * Get the shift associated with this rating (via assignment).
     */
    public function shift()
    {
        return $this->hasOneThrough(
            Shift::class,
            ShiftAssignment::class,
            'id',                    // Foreign key on ShiftAssignment
            'id',                    // Foreign key on Shift
            'shift_assignment_id',   // Local key on Rating
            'shift_id'               // Local key on ShiftAssignment
        );
    }

    // ===== Scopes =====

    /**
     * Scope for ratings on a specific worker.
     */
    public function scopeForWorker($query, $workerId)
    {
        return $query->where('rated_id', $workerId)->where('rater_type', 'business');
    }

    /**
     * Scope for ratings on a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('rated_id', $businessId)->where('rater_type', 'worker');
    }

    /**
     * Scope for ratings that have category breakdown.
     */
    public function scopeWithCategories($query)
    {
        return $query->whereNotNull('punctuality_rating')
            ->orWhereNotNull('quality_rating')
            ->orWhereNotNull('professionalism_rating')
            ->orWhereNotNull('reliability_rating')
            ->orWhereNotNull('communication_rating')
            ->orWhereNotNull('payment_reliability_rating');
    }

    /**
     * Scope for low ratings (any category below threshold).
     */
    public function scopeLowRatings($query, ?int $threshold = null)
    {
        $threshold = $threshold ?? config('ratings.flag_threshold', 2);

        return $query->where(function ($q) use ($threshold) {
            $q->where('punctuality_rating', '<', $threshold)
                ->orWhere('quality_rating', '<', $threshold)
                ->orWhere('professionalism_rating', '<', $threshold)
                ->orWhere('reliability_rating', '<', $threshold)
                ->orWhere('communication_rating', '<', $threshold)
                ->orWhere('payment_reliability_rating', '<', $threshold);
        });
    }

    /**
     * Scope for flagged ratings.
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope for high ratings (weighted score above threshold).
     */
    public function scopeHighRatings($query, ?float $threshold = null)
    {
        $threshold = $threshold ?? config('ratings.thresholds.top_performer', 4.5);

        return $query->where('weighted_score', '>=', $threshold);
    }

    /**
     * Scope for ratings in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ===== Helper Methods =====

    /**
     * Check if this rating is below the threshold for any category.
     */
    public function hasLowCategoryRating(?int $threshold = null): bool
    {
        $threshold = $threshold ?? config('ratings.flag_threshold', 2);
        $lowestRating = $this->lowest_category_rating;

        return $lowestRating !== null && $lowestRating < $threshold;
    }

    /**
     * Get categories that are below threshold.
     */
    public function getLowCategories(?int $threshold = null): array
    {
        $threshold = $threshold ?? config('ratings.flag_threshold', 2);
        $lowCategories = [];

        foreach ($this->category_ratings as $category => $rating) {
            if ($rating !== null && $rating < $threshold) {
                $lowCategories[] = $category;
            }
        }

        return $lowCategories;
    }

    /**
     * Check if rating has been responded to.
     */
    public function hasResponse(): bool
    {
        return ! empty($this->response_text);
    }

    /**
     * Add a response to this rating.
     */
    public function addResponse(string $responseText): self
    {
        $this->update([
            'response_text' => $responseText,
            'responded_at' => now(),
        ]);

        return $this;
    }
}
