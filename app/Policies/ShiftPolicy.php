<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShiftPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view shifts (marketplace)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Shift $shift): bool
    {
        // Anyone can view shifts (public marketplace)
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only businesses and AI agents can create shifts
        return $user->isBusiness() || $user->isAiAgent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shift $shift): bool
    {
        // Business owner, AI agent who posted it, or admin
        if ($user->isAdmin()) {
            return true;
        }

        if ($shift->business_id === $user->id && $user->isBusiness()) {
            return true;
        }

        if ($shift->agent_id === $user->id && $user->isAiAgent()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shift $shift): bool
    {
        // Same as update - business owner, AI agent, or admin
        return $this->update($user, $shift);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Shift $shift): bool
    {
        // Only admin can restore deleted shifts
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Shift $shift): bool
    {
        // Only admin can permanently delete
        return $user->isAdmin();
    }
}
