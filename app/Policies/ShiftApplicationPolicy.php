<?php

namespace App\Policies;

use App\Models\ShiftApplication;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShiftApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Workers can view their own applications, businesses can view applications to their shifts
        return $user->isWorker() || $user->isBusiness() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShiftApplication $shiftApplication): bool
    {
        // Worker who applied, business who owns the shift, or admin
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isWorker() && $shiftApplication->worker_id === $user->id) {
            return true;
        }

        if ($user->isBusiness() && $shiftApplication->shift->business_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only workers can apply to shifts
        return $user->isWorker();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShiftApplication $shiftApplication): bool
    {
        // Only the worker who applied can update (withdraw), or admin
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isWorker() && $shiftApplication->worker_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShiftApplication $shiftApplication): bool
    {
        // Same as update
        return $this->update($user, $shiftApplication);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShiftApplication $shiftApplication): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ShiftApplication $shiftApplication): bool
    {
        return $user->isAdmin();
    }
}
