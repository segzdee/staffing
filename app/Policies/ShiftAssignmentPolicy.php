<?php

namespace App\Policies;

use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShiftAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Workers can view their assignments, businesses can view assignments to their shifts
        return $user->isWorker() || $user->isBusiness() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShiftAssignment $shiftAssignment): bool
    {
        // Worker assigned, business who owns the shift, or admin
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isWorker() && $shiftAssignment->worker_id === $user->id) {
            return true;
        }

        if ($user->isBusiness() && $shiftAssignment->shift->business_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only businesses and AI agents can assign workers
        return $user->isBusiness() || $user->isAiAgent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShiftAssignment $shiftAssignment): bool
    {
        // Business who owns the shift, worker for check-in/out, or admin
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isBusiness() && $shiftAssignment->shift->business_id === $user->id) {
            return true;
        }

        if ($user->isWorker() && $shiftAssignment->worker_id === $user->id) {
            return true; // For check-in/check-out
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShiftAssignment $shiftAssignment): bool
    {
        // Only business who owns the shift or admin can unassign
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isBusiness() && $shiftAssignment->shift->business_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShiftAssignment $shiftAssignment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ShiftAssignment $shiftAssignment): bool
    {
        return $user->isAdmin();
    }
}
