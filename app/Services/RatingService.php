<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WKR-004: Rating Service
 *
 * Handles all rating-related business logic including:
 * - Submitting ratings with category scores
 * - Calculating weighted scores
 * - Updating user profile averages
 * - Rating analytics and trends
 */
class RatingService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Submit a rating with category scores.
     *
     * @param  User  $rater  The user giving the rating
     * @param  User  $ratee  The user receiving the rating
     * @param  ShiftAssignment  $assignment  The shift assignment being rated
     * @param  array  $categoryRatings  Array of category => score (1-5)
     * @param  string|null  $review  Optional review text
     */
    public function submitRating(
        User $rater,
        User $ratee,
        ShiftAssignment $assignment,
        array $categoryRatings,
        ?string $review = null
    ): Rating {
        $raterType = $rater->isBusiness() ? 'business' : 'worker';
        $type = $raterType === 'business' ? 'worker' : 'business';

        // Calculate weighted score based on rater type
        $weightedScore = $this->calculateWeightedScore($categoryRatings, $type);

        // Calculate overall rating (average of all categories)
        $overallRating = $this->calculateOverallRating($categoryRatings);

        return DB::transaction(function () use (
            $rater,
            $ratee,
            $assignment,
            $categoryRatings,
            $review,
            $raterType,
            $weightedScore,
            $overallRating
        ) {
            // Prepare category rating fields
            $ratingData = [
                'shift_assignment_id' => $assignment->id,
                'rater_id' => $rater->id,
                'rated_id' => $ratee->id,
                'rater_type' => $raterType,
                'rating' => $overallRating,
                'review_text' => $review,
                'weighted_score' => $weightedScore,
                'categories' => $categoryRatings, // Keep JSON backup for backwards compatibility
            ];

            // Add individual category fields
            foreach ($categoryRatings as $category => $score) {
                $field = $category.'_rating';
                $ratingData[$field] = $score;
            }

            // Create the rating
            $rating = Rating::create($ratingData);

            // Check for low ratings and flag if necessary
            $this->flagLowRating($rating);

            // Update the ratee's profile averages
            $this->updateUserAverages($ratee);

            // Send notification to ratee
            $this->notifyRatee($ratee, $rating);

            Log::info('Rating submitted', [
                'rating_id' => $rating->id,
                'rater_id' => $rater->id,
                'ratee_id' => $ratee->id,
                'weighted_score' => $weightedScore,
                'overall_rating' => $overallRating,
            ]);

            return $rating;
        });
    }

    /**
     * Calculate weighted score based on category weights.
     *
     * @param  array  $categoryRatings  Array of category => score
     * @param  string  $type  'worker' or 'business'
     */
    public function calculateWeightedScore(array $categoryRatings, string $type): float
    {
        $categories = config("ratings.{$type}_categories", []);
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($categoryRatings as $category => $score) {
            if (isset($categories[$category])) {
                $weight = $categories[$category]['weight'];
                $weightedSum += $score * $weight;
                $totalWeight += $weight;
            }
        }

        // Normalize in case not all categories are provided
        if ($totalWeight > 0 && $totalWeight < 1) {
            $weightedSum = $weightedSum / $totalWeight;
        }

        return round($weightedSum, 2);
    }

    /**
     * Calculate simple average of all category ratings.
     */
    protected function calculateOverallRating(array $categoryRatings): int
    {
        if (empty($categoryRatings)) {
            return 0;
        }

        $average = array_sum($categoryRatings) / count($categoryRatings);

        return (int) round($average);
    }

    /**
     * Update a user's profile rating averages.
     */
    public function updateUserAverages(User $user): void
    {
        if ($user->isWorker()) {
            $this->updateWorkerAverages($user);
        } elseif ($user->isBusiness()) {
            $this->updateBusinessAverages($user);
        }
    }

    /**
     * Update worker profile rating averages.
     */
    protected function updateWorkerAverages(User $worker): void
    {
        $profile = $worker->workerProfile;

        if (! $profile) {
            return;
        }

        // Get all ratings received by this worker (from businesses)
        $ratings = Rating::where('rated_id', $worker->id)
            ->where('rater_type', 'business')
            ->get();

        if ($ratings->isEmpty()) {
            return;
        }

        $categories = config('ratings.worker_categories');
        $totalCount = $ratings->count();

        // Calculate category averages
        $avgPunctuality = $ratings->whereNotNull('punctuality_rating')->avg('punctuality_rating');
        $avgQuality = $ratings->whereNotNull('quality_rating')->avg('quality_rating');
        $avgProfessionalism = $ratings->whereNotNull('professionalism_rating')->avg('professionalism_rating');
        $avgReliability = $ratings->whereNotNull('reliability_rating')->avg('reliability_rating');

        // Calculate weighted rating
        $weightedRating = 0;
        if ($avgPunctuality) {
            $weightedRating += $avgPunctuality * ($categories['punctuality']['weight'] ?? 0.25);
        }
        if ($avgQuality) {
            $weightedRating += $avgQuality * ($categories['quality']['weight'] ?? 0.30);
        }
        if ($avgProfessionalism) {
            $weightedRating += $avgProfessionalism * ($categories['professionalism']['weight'] ?? 0.25);
        }
        if ($avgReliability) {
            $weightedRating += $avgReliability * ($categories['reliability']['weight'] ?? 0.20);
        }

        // Update profile
        $profile->update([
            'avg_punctuality' => $avgPunctuality ? round($avgPunctuality, 2) : null,
            'avg_quality' => $avgQuality ? round($avgQuality, 2) : null,
            'avg_professionalism' => $avgProfessionalism ? round($avgProfessionalism, 2) : null,
            'avg_reliability' => $avgReliability ? round($avgReliability, 2) : null,
            'weighted_rating' => round($weightedRating, 2),
            'total_ratings_count' => $totalCount,
            'rating_average' => round($ratings->avg('rating'), 2),
        ]);

        // Also update the user's cached rating
        $worker->update([
            'rating_as_worker' => round($ratings->avg('rating'), 2),
        ]);
    }

    /**
     * Update business profile rating averages.
     */
    protected function updateBusinessAverages(User $business): void
    {
        $profile = $business->businessProfile;

        if (! $profile) {
            return;
        }

        // Get all ratings received by this business (from workers)
        $ratings = Rating::where('rated_id', $business->id)
            ->where('rater_type', 'worker')
            ->get();

        if ($ratings->isEmpty()) {
            return;
        }

        $categories = config('ratings.business_categories');
        $totalCount = $ratings->count();

        // Calculate category averages
        $avgPunctuality = $ratings->whereNotNull('punctuality_rating')->avg('punctuality_rating');
        $avgCommunication = $ratings->whereNotNull('communication_rating')->avg('communication_rating');
        $avgProfessionalism = $ratings->whereNotNull('professionalism_rating')->avg('professionalism_rating');
        $avgPaymentReliability = $ratings->whereNotNull('payment_reliability_rating')->avg('payment_reliability_rating');

        // Calculate weighted rating
        $weightedRating = 0;
        if ($avgPunctuality) {
            $weightedRating += $avgPunctuality * ($categories['punctuality']['weight'] ?? 0.20);
        }
        if ($avgCommunication) {
            $weightedRating += $avgCommunication * ($categories['communication']['weight'] ?? 0.30);
        }
        if ($avgProfessionalism) {
            $weightedRating += $avgProfessionalism * ($categories['professionalism']['weight'] ?? 0.25);
        }
        if ($avgPaymentReliability) {
            $weightedRating += $avgPaymentReliability * ($categories['payment_reliability']['weight'] ?? 0.25);
        }

        // Update profile
        $profile->update([
            'avg_punctuality' => $avgPunctuality ? round($avgPunctuality, 2) : null,
            'avg_communication' => $avgCommunication ? round($avgCommunication, 2) : null,
            'avg_professionalism' => $avgProfessionalism ? round($avgProfessionalism, 2) : null,
            'avg_payment_reliability' => $avgPaymentReliability ? round($avgPaymentReliability, 2) : null,
            'weighted_rating' => round($weightedRating, 2),
            'total_ratings_count' => $totalCount,
            'rating_average' => round($ratings->avg('rating'), 2),
        ]);
    }

    /**
     * Get rating summary for a worker.
     */
    public function getWorkerRatingSummary(User $worker): array
    {
        $profile = $worker->workerProfile;
        $minRatingsForBreakdown = config('ratings.min_ratings_for_breakdown', 3);

        $summary = [
            'overall_rating' => $profile?->rating_average ?? 0,
            'weighted_rating' => $profile?->weighted_rating ?? 0,
            'total_ratings' => $profile?->total_ratings_count ?? 0,
            'show_breakdown' => ($profile?->total_ratings_count ?? 0) >= $minRatingsForBreakdown,
            'categories' => [],
        ];

        if ($summary['show_breakdown']) {
            $categories = config('ratings.worker_categories');
            foreach ($categories as $key => $category) {
                $avgField = 'avg_'.$key;
                $summary['categories'][$key] = [
                    'label' => $category['label'],
                    'description' => $category['description'],
                    'weight' => $category['weight'],
                    'average' => $profile?->$avgField ?? 0,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get rating summary for a business.
     */
    public function getBusinessRatingSummary(User $business): array
    {
        $profile = $business->businessProfile;
        $minRatingsForBreakdown = config('ratings.min_ratings_for_breakdown', 3);

        $summary = [
            'overall_rating' => $profile?->rating_average ?? 0,
            'weighted_rating' => $profile?->weighted_rating ?? 0,
            'total_ratings' => $profile?->total_ratings_count ?? 0,
            'show_breakdown' => ($profile?->total_ratings_count ?? 0) >= $minRatingsForBreakdown,
            'categories' => [],
        ];

        if ($summary['show_breakdown']) {
            $categories = config('ratings.business_categories');
            $categoryMap = [
                'punctuality' => 'avg_punctuality',
                'communication' => 'avg_communication',
                'professionalism' => 'avg_professionalism',
                'payment_reliability' => 'avg_payment_reliability',
            ];

            foreach ($categories as $key => $category) {
                $avgField = $categoryMap[$key];
                $summary['categories'][$key] = [
                    'label' => $category['label'],
                    'description' => $category['description'],
                    'weight' => $category['weight'],
                    'average' => $profile?->$avgField ?? 0,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get category breakdown for a user.
     */
    public function getCategoryBreakdown(User $user): array
    {
        if ($user->isWorker()) {
            return $this->getWorkerRatingSummary($user)['categories'];
        } elseif ($user->isBusiness()) {
            return $this->getBusinessRatingSummary($user)['categories'];
        }

        return [];
    }

    /**
     * Get recent ratings for a user.
     */
    public function getRecentRatings(User $user, int $limit = 10): Collection
    {
        return Rating::where('rated_id', $user->id)
            ->with(['rater', 'assignment.shift'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get rating trend over time.
     */
    public function getRatingTrend(User $user, int $months = 6): array
    {
        $maxMonths = config('ratings.trend.max_months', 12);
        $months = min($months, $maxMonths);

        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $ratings = Rating::where('rated_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('AVG(rating) as avg_rating'),
                DB::raw('AVG(weighted_score) as avg_weighted'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte(Carbon::now())) {
            $key = $currentDate->format('Y-m');
            $monthData = $ratings->first(function ($r) use ($currentDate) {
                return $r->year == $currentDate->year && $r->month == $currentDate->month;
            });

            $trend[] = [
                'month' => $currentDate->format('M Y'),
                'key' => $key,
                'avg_rating' => $monthData ? round($monthData->avg_rating, 2) : null,
                'avg_weighted' => $monthData ? round($monthData->avg_weighted, 2) : null,
                'count' => $monthData ? $monthData->count : 0,
            ];

            $currentDate->addMonth();
        }

        return $trend;
    }

    /**
     * Flag a rating if any category is below threshold.
     */
    public function flagLowRating(Rating $rating): void
    {
        $threshold = config('ratings.flag_threshold', 2);
        $lowCategories = [];

        // Check worker categories
        $workerCategories = ['punctuality_rating', 'quality_rating', 'professionalism_rating', 'reliability_rating'];

        // Check business categories
        $businessCategories = ['punctuality_rating', 'communication_rating', 'professionalism_rating', 'payment_reliability_rating'];

        $allCategories = array_unique(array_merge($workerCategories, $businessCategories));

        foreach ($allCategories as $category) {
            $value = $rating->$category;
            if ($value !== null && $value < $threshold) {
                $lowCategories[] = str_replace('_rating', '', $category);
            }
        }

        if (! empty($lowCategories)) {
            $rating->update([
                'is_flagged' => true,
                'flag_reason' => 'Low ratings in: '.implode(', ', $lowCategories),
                'flagged_at' => now(),
            ]);

            Log::warning('Rating flagged for low scores', [
                'rating_id' => $rating->id,
                'low_categories' => $lowCategories,
                'rated_id' => $rating->rated_id,
            ]);
        }
    }

    /**
     * Notify the ratee about a new rating.
     */
    protected function notifyRatee(User $ratee, Rating $rating): void
    {
        $title = $rating->rater_type === 'business' ? 'New Rating from Business' : 'New Rating from Worker';
        $message = sprintf(
            'You received a %d-star rating for your recent shift.',
            $rating->rating
        );

        $this->notificationService->send(
            $ratee,
            'rating_received',
            $title,
            $message,
            [
                'rating_id' => $rating->id,
                'rating' => $rating->rating,
                'weighted_score' => $rating->weighted_score,
            ]
        );
    }

    /**
     * Recalculate all averages for a user (used for migration).
     */
    public function recalculateAllAverages(User $user): void
    {
        $this->updateUserAverages($user);
    }

    /**
     * Get rating distribution for a user.
     */
    public function getRatingDistribution(User $user): array
    {
        $ratings = Rating::where('rated_id', $user->id)
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $ratings[$i] ?? 0;
        }

        return $distribution;
    }

    /**
     * Check if a user can rate another user for a specific assignment.
     *
     * @return array ['can_rate' => bool, 'reason' => string|null]
     */
    public function canRate(User $rater, User $ratee, ShiftAssignment $assignment): array
    {
        // Check if already rated
        $existingRating = Rating::where('shift_assignment_id', $assignment->id)
            ->where('rater_id', $rater->id)
            ->first();

        if ($existingRating) {
            return ['can_rate' => false, 'reason' => 'You have already rated this shift.'];
        }

        // Check deadline
        $deadlineDays = config('ratings.deadline_days', 14);
        $deadline = $assignment->shift->shift_date->addDays($deadlineDays);

        if (now()->gt($deadline)) {
            return ['can_rate' => false, 'reason' => "Rating deadline has passed ({$deadlineDays} days after shift)."];
        }

        // Check assignment status
        if ($assignment->status !== 'completed') {
            return ['can_rate' => false, 'reason' => 'You can only rate completed shifts.'];
        }

        return ['can_rate' => true, 'reason' => null];
    }
}
