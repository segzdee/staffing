<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBusinessRatingRequest;
use App\Http\Requests\StoreWorkerRatingRequest;
use App\Models\Rating;
use App\Models\ShiftAssignment;
use App\Services\BadgeService;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * WKR-004: Rating Controller with 4-Category Rating System
 */
class RatingController extends Controller
{
    protected RatingService $ratingService;

    protected BadgeService $badgeService;

    public function __construct(RatingService $ratingService, BadgeService $badgeService)
    {
        $this->middleware('auth');
        $this->ratingService = $ratingService;
        $this->badgeService = $badgeService;
    }

    /**
     * Show rating form for worker rating business.
     */
    public function createWorkerRating(ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        if ($assignment->worker_id !== Auth::id()) {
            abort(403, 'You can only rate shifts you worked.');
        }

        // Check if can rate
        $canRate = $this->ratingService->canRate(
            Auth::user(),
            $assignment->shift->business,
            $assignment
        );

        if (! $canRate['can_rate']) {
            return redirect()->route('worker.assignments.show', $assignment->id)
                ->with('error', $canRate['reason']);
        }

        // Get business category config
        $categories = config('ratings.business_categories');

        return view('worker.shifts.rate', [
            'assignment' => $assignment,
            'rated' => $assignment->shift->business,
            'raterType' => 'worker',
            'categories' => $categories,
        ]);
    }

    /**
     * Show rating form for business rating worker.
     */
    public function createBusinessRating(ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        if ($assignment->shift->business_id !== Auth::id()) {
            abort(403, 'You can only rate shifts you posted.');
        }

        // Check if can rate
        $canRate = $this->ratingService->canRate(
            Auth::user(),
            $assignment->worker,
            $assignment
        );

        if (! $canRate['can_rate']) {
            return redirect()->route('business.shifts.show', $assignment->shift->id)
                ->with('error', $canRate['reason']);
        }

        // Get worker category config
        $categories = config('ratings.worker_categories');

        return view('business.shifts.rate', [
            'assignment' => $assignment,
            'rated' => $assignment->worker,
            'raterType' => 'business',
            'categories' => $categories,
        ]);
    }

    /**
     * Store worker rating of business.
     */
    public function storeWorkerRating(StoreBusinessRatingRequest $request, ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        if ($assignment->worker_id !== Auth::id()) {
            abort(403, 'You can only rate shifts you worked.');
        }

        // Submit rating using RatingService
        $rating = $this->ratingService->submitRating(
            Auth::user(),
            $assignment->shift->business,
            $assignment,
            $request->getCategoryRatings(),
            $request->input('review_text')
        );

        return redirect()->route('worker.assignments.show', $assignment->id)
            ->with('success', 'Thank you for your rating!');
    }

    /**
     * Store business rating of worker.
     */
    public function storeBusinessRating(StoreWorkerRatingRequest $request, ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        if ($assignment->shift->business_id !== Auth::id()) {
            abort(403, 'You can only rate shifts you posted.');
        }

        // Submit rating using RatingService
        $rating = $this->ratingService->submitRating(
            Auth::user(),
            $assignment->worker,
            $assignment,
            $request->getCategoryRatings(),
            $request->input('review_text')
        );

        // Check for rating-based badges
        $this->badgeService->checkAndAward($assignment->worker, 'rating_received');

        return redirect()->route('business.shifts.show', $assignment->shift->id)
            ->with('success', 'Rating submitted successfully!');
    }

    /**
     * Add response to a rating.
     */
    public function respond(Request $request, Rating $rating)
    {
        // Check if user is the rated party
        if ($rating->rated_id !== Auth::id()) {
            abort(403, 'You can only respond to ratings about you.');
        }

        // Check if already responded
        if ($rating->hasResponse()) {
            return redirect()->back()
                ->with('error', 'You have already responded to this rating.');
        }

        $validated = $request->validate([
            'response_text' => 'required|string|max:500',
        ]);

        $rating->addResponse($validated['response_text']);

        return redirect()->back()
            ->with('success', 'Response added successfully!');
    }

    /**
     * Get rating summary for a user (API endpoint).
     */
    public function getSummary(Request $request, int $userId)
    {
        $user = \App\Models\User::findOrFail($userId);

        if ($user->isWorker()) {
            $summary = $this->ratingService->getWorkerRatingSummary($user);
        } elseif ($user->isBusiness()) {
            $summary = $this->ratingService->getBusinessRatingSummary($user);
        } else {
            return response()->json(['error' => 'Invalid user type'], 400);
        }

        return response()->json($summary);
    }

    /**
     * Get rating trend for a user (API endpoint).
     */
    public function getTrend(Request $request, int $userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $months = $request->input('months', config('ratings.trend.default_months', 6));

        $trend = $this->ratingService->getRatingTrend($user, $months);

        return response()->json($trend);
    }

    /**
     * Get rating distribution for a user (API endpoint).
     */
    public function getDistribution(Request $request, int $userId)
    {
        $user = \App\Models\User::findOrFail($userId);

        $distribution = $this->ratingService->getRatingDistribution($user);

        return response()->json($distribution);
    }

    /**
     * Get recent ratings for a user (API endpoint).
     */
    public function getRecent(Request $request, int $userId)
    {
        $user = \App\Models\User::findOrFail($userId);
        $limit = $request->input('limit', 10);

        $ratings = $this->ratingService->getRecentRatings($user, $limit);

        return response()->json($ratings);
    }

    /**
     * Display rating breakdown for worker profile.
     */
    public function workerBreakdown()
    {
        $user = Auth::user();

        if (! $user->isWorker()) {
            abort(403);
        }

        $summary = $this->ratingService->getWorkerRatingSummary($user);
        $trend = $this->ratingService->getRatingTrend($user);
        $distribution = $this->ratingService->getRatingDistribution($user);
        $recentRatings = $this->ratingService->getRecentRatings($user);

        return view('worker.ratings.breakdown', [
            'summary' => $summary,
            'trend' => $trend,
            'distribution' => $distribution,
            'recentRatings' => $recentRatings,
            'categories' => config('ratings.worker_categories'),
        ]);
    }

    /**
     * Display rating breakdown for business profile.
     */
    public function businessBreakdown()
    {
        $user = Auth::user();

        if (! $user->isBusiness()) {
            abort(403);
        }

        $summary = $this->ratingService->getBusinessRatingSummary($user);
        $trend = $this->ratingService->getRatingTrend($user);
        $distribution = $this->ratingService->getRatingDistribution($user);
        $recentRatings = $this->ratingService->getRecentRatings($user);

        return view('business.ratings.breakdown', [
            'summary' => $summary,
            'trend' => $trend,
            'distribution' => $distribution,
            'recentRatings' => $recentRatings,
            'categories' => config('ratings.business_categories'),
        ]);
    }
}
