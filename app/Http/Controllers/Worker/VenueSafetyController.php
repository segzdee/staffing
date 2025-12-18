<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Venue;
use App\Models\VenueSafetyFlag;
use App\Models\VenueSafetyRating;
use App\Services\VenueSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SAF-004: Venue Safety Controller for Workers
 *
 * Handles worker interactions with the venue safety rating system including:
 * - Viewing venue safety information
 * - Submitting safety ratings after shifts
 * - Reporting safety concerns/flags
 * - Viewing their submitted ratings and flags
 */
class VenueSafetyController extends Controller
{
    public function __construct(
        protected VenueSafetyService $safetyService
    ) {}

    /**
     * Display worker's safety ratings history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $ratings = VenueSafetyRating::byUser($user->id)
            ->with(['venue', 'shift'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $flags = VenueSafetyFlag::byReporter($user->id)
            ->with(['venue'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('worker.safety.index', [
            'ratings' => $ratings,
            'flags' => $flags,
        ]);
    }

    /**
     * Show safety information for a venue.
     */
    public function showVenueSafety(Venue $venue): View
    {
        $summary = $this->safetyService->getVenueSafetySummary($venue);
        $recentRatings = VenueSafetyRating::forVenue($venue->id)
            ->with(['user'])
            ->where('is_anonymous', false)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $trend = $this->safetyService->getSafetyTrend($venue, 6);

        return view('worker.safety.venue', [
            'venue' => $venue,
            'summary' => $summary,
            'recentRatings' => $recentRatings,
            'trend' => $trend,
        ]);
    }

    /**
     * Show form to submit a safety rating for a shift.
     */
    public function createRating(Shift $shift): View
    {
        $venue = $shift->venue;

        // Check if user is assigned to this shift
        $user = auth()->user();
        $assignment = $shift->assignments()
            ->where('worker_id', $user->id)
            ->whereIn('status', ['completed', 'checked_out'])
            ->first();

        if (! $assignment) {
            abort(403, 'You can only rate venues for shifts you have completed.');
        }

        // Check if already rated
        $existingRating = VenueSafetyRating::where('venue_id', $venue->id)
            ->where('user_id', $user->id)
            ->where('shift_id', $shift->id)
            ->first();

        return view('worker.safety.rate', [
            'shift' => $shift,
            'venue' => $venue,
            'assignment' => $assignment,
            'existingRating' => $existingRating,
            'safetyAspects' => VenueSafetyRating::SAFETY_ASPECTS,
            'ratingLabels' => VenueSafetyRating::RATING_LABELS,
        ]);
    }

    /**
     * Store a safety rating for a shift.
     */
    public function storeRating(Request $request, Shift $shift): RedirectResponse
    {
        $venue = $shift->venue;
        $user = $request->user();

        // Validate assignment
        $assignment = $shift->assignments()
            ->where('worker_id', $user->id)
            ->whereIn('status', ['completed', 'checked_out'])
            ->first();

        if (! $assignment) {
            return redirect()->back()
                ->with('error', 'You can only rate venues for shifts you have completed.');
        }

        $validated = $request->validate([
            'overall_safety' => 'required|integer|min:1|max:5',
            'lighting_rating' => 'nullable|integer|min:1|max:5',
            'parking_safety' => 'nullable|integer|min:1|max:5',
            'emergency_exits' => 'nullable|integer|min:1|max:5',
            'staff_support' => 'nullable|integer|min:1|max:5',
            'equipment_condition' => 'nullable|integer|min:1|max:5',
            'safety_concerns' => 'nullable|string|max:2000',
            'positive_notes' => 'nullable|string|max:2000',
            'would_return' => 'boolean',
            'is_anonymous' => 'boolean',
        ]);

        $validated['would_return'] = $request->boolean('would_return', true);
        $validated['is_anonymous'] = $request->boolean('is_anonymous', false);

        $this->safetyService->submitSafetyRating($user, $venue, $shift, $validated);

        return redirect()->route('worker.assignments.show', $assignment)
            ->with('success', 'Thank you for submitting your safety rating.');
    }

    /**
     * Show form to report a safety concern at a venue.
     */
    public function createFlag(Request $request, Venue $venue): View
    {
        return view('worker.safety.flag', [
            'venue' => $venue,
            'flagTypes' => VenueSafetyFlag::getTypeOptions(),
            'severityOptions' => VenueSafetyFlag::getSeverityOptions(),
        ]);
    }

    /**
     * Store a safety flag for a venue.
     */
    public function storeFlag(Request $request, Venue $venue): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'flag_type' => 'required|in:'.implode(',', array_keys(VenueSafetyFlag::TYPE_LABELS)),
            'severity' => 'required|in:'.implode(',', array_keys(VenueSafetyFlag::SEVERITY_LABELS)),
            'description' => 'required|string|min:20|max:5000',
            'evidence_urls' => 'nullable|array|max:10',
            'evidence_urls.*' => 'nullable|url|max:500',
        ]);

        $flag = $this->safetyService->flagSafetyConcern($user, $venue, $validated);

        return redirect()->route('worker.safety.flag.show', $flag)
            ->with('success', 'Your safety concern has been reported. We take these reports seriously and will investigate.');
    }

    /**
     * Show details of a submitted flag.
     */
    public function showFlag(VenueSafetyFlag $flag): View
    {
        $user = auth()->user();

        // Ensure user owns this flag
        if ($flag->reported_by !== $user->id) {
            abort(403, 'You can only view your own safety reports.');
        }

        return view('worker.safety.flag-show', [
            'flag' => $flag->load(['venue', 'assignee']),
        ]);
    }

    /**
     * API: Get safety summary for a venue.
     */
    public function getVenueSafetySummary(Venue $venue): JsonResponse
    {
        $summary = $this->safetyService->getVenueSafetySummary($venue);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * API: Get safety warning for a venue (used in shift browsing).
     */
    public function getSafetyWarning(Venue $venue): JsonResponse
    {
        $warning = $this->safetyService->getSafetyWarningForWorker($venue);

        return response()->json([
            'success' => true,
            'data' => [
                'has_warning' => $warning !== null,
                'warning_message' => $warning,
                'safety_score' => $venue->safety_score,
                'safety_status' => $venue->safety_status,
                'safety_status_label' => $venue->safety_status_label,
                'safety_status_color' => $venue->safety_status_color,
            ],
        ]);
    }

    /**
     * API: Check if user can rate a venue for a shift.
     */
    public function canRateVenue(Request $request, Shift $shift): JsonResponse
    {
        $user = $request->user();
        $venue = $shift->venue;

        // Check if user completed this shift
        $assignment = $shift->assignments()
            ->where('worker_id', $user->id)
            ->whereIn('status', ['completed', 'checked_out'])
            ->first();

        if (! $assignment) {
            return response()->json([
                'success' => true,
                'data' => [
                    'can_rate' => false,
                    'reason' => 'You have not completed this shift.',
                ],
            ]);
        }

        // Check if already rated
        $existingRating = VenueSafetyRating::where('venue_id', $venue->id)
            ->where('user_id', $user->id)
            ->where('shift_id', $shift->id)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'can_rate' => true,
                'has_existing_rating' => $existingRating,
                'rating_url' => route('worker.safety.rate', $shift),
            ],
        ]);
    }

    /**
     * API: Submit rating via AJAX.
     */
    public function submitRatingAjax(Request $request, Shift $shift): JsonResponse
    {
        $venue = $shift->venue;
        $user = $request->user();

        // Validate assignment
        $assignment = $shift->assignments()
            ->where('worker_id', $user->id)
            ->whereIn('status', ['completed', 'checked_out'])
            ->first();

        if (! $assignment) {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate venues for shifts you have completed.',
            ], 403);
        }

        $validated = $request->validate([
            'overall_safety' => 'required|integer|min:1|max:5',
            'lighting_rating' => 'nullable|integer|min:1|max:5',
            'parking_safety' => 'nullable|integer|min:1|max:5',
            'emergency_exits' => 'nullable|integer|min:1|max:5',
            'staff_support' => 'nullable|integer|min:1|max:5',
            'equipment_condition' => 'nullable|integer|min:1|max:5',
            'safety_concerns' => 'nullable|string|max:2000',
            'positive_notes' => 'nullable|string|max:2000',
            'would_return' => 'boolean',
            'is_anonymous' => 'boolean',
        ]);

        $validated['would_return'] = $request->boolean('would_return', true);
        $validated['is_anonymous'] = $request->boolean('is_anonymous', false);

        $rating = $this->safetyService->submitSafetyRating($user, $venue, $shift, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for submitting your safety rating.',
            'data' => [
                'rating_id' => $rating->id,
                'overall_safety' => $rating->overall_safety,
                'venue_new_score' => $venue->fresh()->safety_score,
            ],
        ]);
    }

    /**
     * API: Submit flag via AJAX.
     */
    public function submitFlagAjax(Request $request, Venue $venue): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'flag_type' => 'required|in:'.implode(',', array_keys(VenueSafetyFlag::TYPE_LABELS)),
            'severity' => 'required|in:'.implode(',', array_keys(VenueSafetyFlag::SEVERITY_LABELS)),
            'description' => 'required|string|min:20|max:5000',
            'evidence_urls' => 'nullable|array|max:10',
            'evidence_urls.*' => 'nullable|url|max:500',
        ]);

        $flag = $this->safetyService->flagSafetyConcern($user, $venue, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Your safety concern has been reported.',
            'data' => [
                'flag_id' => $flag->id,
                'status' => $flag->status,
                'view_url' => route('worker.safety.flag.show', $flag),
            ],
        ]);
    }
}
