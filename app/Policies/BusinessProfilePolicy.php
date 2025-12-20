<?php

namespace App\Policies;

use App\Models\BusinessProfile;
use App\Models\User;

class BusinessProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     * Any authenticated user can view business profiles list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Owner can view their own profile, workers can view for applications.
     */
    public function view(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin can view all profiles
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can always view their own profile
        if ($user->id === $businessProfile->user_id) {
            return true;
        }

        // Workers can view business profiles (for shift applications)
        if ($user->isWorker()) {
            return true;
        }

        // Agencies can view business profiles (for placement purposes)
        if ($user->isAgency()) {
            return true;
        }

        // Other businesses can view public business profiles
        if ($user->isBusiness()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Business users can create their profile during registration.
     */
    public function create(User $user): bool
    {
        // Only business users can create business profiles
        if (! $user->isBusiness()) {
            return false;
        }

        // Check if user already has a business profile
        if ($user->businessProfile()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only the owner can update their profile.
     */
    public function update(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin can update any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Only owner can update their profile
        return $user->id === $businessProfile->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Owner or admin can delete the profile.
     */
    public function delete(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin can delete any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can delete their own profile
        return $user->id === $businessProfile->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins or owner can restore deleted profiles.
     */
    public function restore(User $user, BusinessProfile $businessProfile): bool
    {
        // Admin can restore any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can restore their own profile
        return $user->id === $businessProfile->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete profiles.
     */
    public function forceDelete(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify the business profile.
     * Only admins can verify business profiles.
     */
    public function verify(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update venues on the profile.
     * Only the owner can manage their venues.
     */
    public function manageVenues(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage shift templates.
     * Only the owner can manage their templates.
     */
    public function manageTemplates(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage worker preferences.
     * Only the owner can manage preferred/blacklisted workers.
     */
    public function manageWorkerPreferences(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can view billing information.
     * Only owner or admin can view billing details.
     */
    public function viewBilling(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update payment methods.
     * Only owner can update their payment methods.
     */
    public function updatePayment(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id;
    }

    /**
     * Determine whether the user can view analytics.
     * Only owner or admin can view analytics.
     */
    public function viewAnalytics(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage subscription.
     * Only owner can manage their subscription.
     */
    public function manageSubscription(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can submit verification documents.
     * Only owner can submit verification documents.
     */
    public function submitVerification(User $user, BusinessProfile $businessProfile): bool
    {
        return $user->id === $businessProfile->user_id;
    }

    /**
     * Determine whether the user can post shifts.
     * Only verified businesses in good standing can post shifts.
     */
    public function postShifts(User $user, BusinessProfile $businessProfile): bool
    {
        // Must be the owner
        if ($user->id !== $businessProfile->user_id) {
            return false;
        }

        // Check if business can post shifts
        if (method_exists($businessProfile, 'canPostShifts')) {
            return $businessProfile->canPostShifts();
        }

        // Fallback check
        return $businessProfile->account_in_good_standing && $businessProfile->can_post_shifts;
    }
}
