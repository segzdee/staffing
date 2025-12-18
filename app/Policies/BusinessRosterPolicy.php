<?php

namespace App\Policies;

use App\Models\BusinessRoster;
use App\Models\User;

/**
 * BIZ-005: Business Roster Policy
 *
 * Authorization policy for business roster management.
 */
class BusinessRosterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isBusiness();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BusinessRoster $roster): bool
    {
        return $user->id === $roster->business_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isBusiness();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BusinessRoster $roster): bool
    {
        return $user->id === $roster->business_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BusinessRoster $roster): bool
    {
        return $user->id === $roster->business_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BusinessRoster $roster): bool
    {
        return $user->id === $roster->business_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BusinessRoster $roster): bool
    {
        return $user->id === $roster->business_id;
    }
}
