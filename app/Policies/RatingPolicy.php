<?php

namespace App\Policies;

use App\Models\Rating;
use App\Models\ShiftAssignment;
use App\Models\User;
use Carbon\Carbon;

class RatingPolicy
{
    /**
     * Determine whether the user can view any models.
     * Any authenticated user can view ratings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Any authenticated user can view individual ratings.
     */
    public function view(User $user, Rating $rating): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * User must have completed a shift assignment with the party they want to rate.
     */
    public function create(User $user, ?ShiftAssignment $assignment = null): bool
    {
        // If no assignment provided, allow (controller will validate)
        if ($assignment === null) {
            return true;
        }

        // Assignment must be completed
        if ($assignment->status !== 'completed') {
            return false;
        }

        // Load the shift relationship if not already loaded
        if (! $assignment->relationLoaded('shift')) {
            $assignment->load('shift');
        }

        // Worker can rate the business (after completing the shift)
        if ($user->id === $assignment->worker_id) {
            // Check if worker already rated this assignment
            $existingRating = Rating::where('shift_assignment_id', $assignment->id)
                ->where('rater_id', $user->id)
                ->where('rater_type', 'worker')
                ->exists();

            return ! $existingRating;
        }

        // Business can rate the worker (after worker completes the shift)
        if ($assignment->shift && $user->id === $assignment->shift->business_id) {
            // Check if business already rated this assignment
            $existingRating = Rating::where('shift_assignment_id', $assignment->id)
                ->where('rater_id', $user->id)
                ->where('rater_type', 'business')
                ->exists();

            return ! $existingRating;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     * Only the rater can update within 48 hours.
     */
    public function update(User $user, Rating $rating): bool
    {
        // Only the original rater can update
        if ($user->id !== $rating->rater_id) {
            return false;
        }

        // Check if within 48-hour edit window
        $editDeadline = Carbon::parse($rating->created_at)->addHours(48);
        if (Carbon::now()->greaterThan($editDeadline)) {
            return false; // Past 48-hour window
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     * Only admins can delete ratings.
     */
    public function delete(User $user, Rating $rating): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins can restore deleted ratings.
     */
    public function restore(User $user, Rating $rating): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete ratings.
     */
    public function forceDelete(User $user, Rating $rating): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can respond to the rating.
     * The rated party can respond to a rating.
     */
    public function respond(User $user, Rating $rating): bool
    {
        // Only the person who was rated can respond
        if ($user->id !== $rating->rated_id) {
            return false;
        }

        // Cannot respond if already responded
        if (! empty($rating->response_text)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can flag a rating.
     * The rated party can flag inappropriate ratings.
     */
    public function flag(User $user, Rating $rating): bool
    {
        // Only the person who was rated can flag
        if ($user->id !== $rating->rated_id) {
            return $user->isAdmin();
        }

        // Cannot flag if already flagged
        if ($rating->is_flagged) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can unflag a rating.
     * Only admins can unflag ratings.
     */
    public function unflag(User $user, Rating $rating): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can review flagged ratings.
     * Only admins can review flagged ratings.
     */
    public function reviewFlagged(User $user, Rating $rating): bool
    {
        return $user->isAdmin();
    }
}
