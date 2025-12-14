<?php

namespace App\Http\Controllers;

use App\Models\ShiftAssignment;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RatingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show rating form for worker rating business
     */
    public function createWorkerRating(ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);
        
        if (!$assignment->worker_id === Auth::id()) {
            abort(403, 'You can only rate shifts you worked.');
        }

        // Check if already rated
        $existingRating = Rating::where('shift_assignment_id', $assignment->id)
            ->where('rater_id', Auth::id())
            ->where('rater_type', 'worker')
            ->first();

        if ($existingRating) {
            return redirect()->route('worker.assignments.show', $assignment->id)
                ->with('info', 'You have already rated this shift.');
        }

        // Check deadline (14 days)
        $deadline = $assignment->shift->shift_date->addDays(14);
        if (now()->gt($deadline)) {
            return redirect()->route('worker.assignments.show', $assignment->id)
                ->with('error', 'Rating deadline has passed (14 days after shift).');
        }

        return view('worker.shifts.rate', [
            'assignment' => $assignment,
            'rated' => $assignment->shift->business,
            'raterType' => 'worker',
        ]);
    }

    /**
     * Show rating form for business rating worker
     */
    public function createBusinessRating(ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);
        
        if ($assignment->shift->business_id !== Auth::id()) {
            abort(403, 'You can only rate shifts you posted.');
        }

        // Check if already rated
        $existingRating = Rating::where('shift_assignment_id', $assignment->id)
            ->where('rater_id', Auth::id())
            ->where('rater_type', 'business')
            ->first();

        if ($existingRating) {
            return redirect()->route('business.shifts.show', $assignment->shift->id)
                ->with('info', 'You have already rated this worker.');
        }

        // Check deadline (14 days)
        $deadline = $assignment->shift->shift_date->addDays(14);
        if (now()->gt($deadline)) {
            return redirect()->route('business.shifts.show', $assignment->shift->id)
                ->with('error', 'Rating deadline has passed (14 days after shift).');
        }

        return view('business.shifts.rate', [
            'assignment' => $assignment,
            'rated' => $assignment->worker,
            'raterType' => 'business',
        ]);
    }

    /**
     * Store worker rating of business
     */
    public function storeWorkerRating(Request $request, ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        $validated = $request->validate([
            'overall' => 'required|integer|min:1|max:5',
            'communication' => 'nullable|integer|min:1|max:5',
            'work_environment' => 'nullable|integer|min:1|max:5',
            'would_work_again' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = Rating::create([
            'shift_assignment_id' => $assignment->id,
            'rater_id' => Auth::id(),
            'rated_id' => $assignment->shift->business_id,
            'rater_type' => 'worker',
            'rating' => $validated['overall'],
            'review_text' => $validated['comment'] ?? null,
            'categories' => [
                'communication' => $validated['communication'] ?? null,
                'work_environment' => $validated['work_environment'] ?? null,
                'would_work_again' => $validated['would_work_again'],
            ],
        ]);

        // Update business rating average
        $this->updateBusinessRating($assignment->shift->business_id);

        return redirect()->route('worker.assignments.show', $assignment->id)
            ->with('success', 'Thank you for your rating!');
    }

    /**
     * Store business rating of worker
     */
    public function storeBusinessRating(Request $request, ShiftAssignment $assignment)
    {
        $this->authorize('view', $assignment);

        $validated = $request->validate([
            'overall' => 'required|integer|min:1|max:5',
            'punctuality' => 'nullable|integer|min:1|max:5',
            'professionalism' => 'nullable|integer|min:1|max:5',
            'skill_level' => 'nullable|integer|min:1|max:5',
            'would_hire_again' => 'required|boolean',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = Rating::create([
            'shift_assignment_id' => $assignment->id,
            'rater_id' => Auth::id(),
            'rated_id' => $assignment->worker_id,
            'rater_type' => 'business',
            'rating' => $validated['overall'],
            'review_text' => $validated['comment'] ?? null,
            'categories' => [
                'punctuality' => $validated['punctuality'] ?? null,
                'professionalism' => $validated['professionalism'] ?? null,
                'skill_level' => $validated['skill_level'] ?? null,
                'would_hire_again' => $validated['would_hire_again'],
            ],
        ]);

        // Update worker rating average and trigger badge check
        $this->updateWorkerRating($assignment->worker_id);
        
        // Check for rating-based badges
        $badgeService = app(\App\Services\BadgeService::class);
        $badgeService->checkAndAward($assignment->worker, 'rating_received');

        return redirect()->route('business.shifts.show', $assignment->shift->id)
            ->with('success', 'Rating submitted successfully!');
    }

    /**
     * Add response to a rating
     */
    public function respond(Request $request, Rating $rating)
    {
        // Check if user is the rated party
        if ($rating->rated_id !== Auth::id()) {
            abort(403, 'You can only respond to ratings about you.');
        }

        // Check if already responded
        if ($rating->response_text) {
            return redirect()->back()
                ->with('error', 'You have already responded to this rating.');
        }

        $validated = $request->validate([
            'response_text' => 'required|string|max:500',
        ]);

        $rating->update([
            'response_text' => $validated['response_text'],
            'responded_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Response added successfully!');
    }

    /**
     * Update business rating average
     */
    protected function updateBusinessRating($businessId)
    {
        $avgRating = Rating::where('rated_id', $businessId)
            ->where('rater_type', 'worker')
            ->avg('rating');

        $business = \App\Models\User::find($businessId);
        if ($business && $business->businessProfile) {
            $business->businessProfile->update([
                'rating_average' => round($avgRating, 2),
            ]);
        }
    }

    /**
     * Update worker rating average
     */
    protected function updateWorkerRating($workerId)
    {
        $avgRating = Rating::where('rated_id', $workerId)
            ->where('rater_type', 'business')
            ->avg('rating');

        $worker = \App\Models\User::find($workerId);
        if ($worker) {
            $worker->update([
                'rating_as_worker' => round($avgRating, 2),
            ]);
            
            if ($worker->workerProfile) {
                $worker->workerProfile->update([
                    'rating_average' => round($avgRating, 2),
                ]);
            }
        }
    }
}
