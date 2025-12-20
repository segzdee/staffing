<?php

namespace App\Policies;

use App\Models\AgencyProfile;
use App\Models\AgencyWorker;
use App\Models\User;

class AgencyProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     * Any authenticated user can view agency profiles list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Owner can view their own profile, workers associated with agency can view.
     */
    public function view(User $user, AgencyProfile $agencyProfile): bool
    {
        // Admin can view all profiles
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can always view their own profile
        if ($user->id === $agencyProfile->user_id) {
            return true;
        }

        // Workers associated with this agency can view the profile
        if ($user->isWorker()) {
            $isAssociatedWorker = AgencyWorker::where('agency_id', $agencyProfile->user_id)
                ->where('worker_id', $user->id)
                ->whereIn('status', ['active', 'pending'])
                ->exists();

            if ($isAssociatedWorker) {
                return true;
            }
        }

        // Businesses can view agency profiles (for contracting purposes)
        if ($user->isBusiness()) {
            return true;
        }

        // Other agencies can view profiles
        if ($user->isAgency()) {
            return true;
        }

        // Workers browsing agencies can view public profiles
        if ($user->isWorker()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Agency users can create their profile during registration.
     */
    public function create(User $user): bool
    {
        // Only agency users can create agency profiles
        if (! $user->isAgency()) {
            return false;
        }

        // Check if user already has an agency profile
        if ($user->agencyProfile()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only the owner can update their profile.
     */
    public function update(User $user, AgencyProfile $agencyProfile): bool
    {
        // Admin can update any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Only owner can update their profile
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Owner or admin can delete the profile.
     */
    public function delete(User $user, AgencyProfile $agencyProfile): bool
    {
        // Admin can delete any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can delete their own profile
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins or owner can restore deleted profiles.
     */
    public function restore(User $user, AgencyProfile $agencyProfile): bool
    {
        // Admin can restore any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can restore their own profile
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete profiles.
     */
    public function forceDelete(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify the agency profile.
     * Only admins can verify agency profiles.
     */
    public function verify(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can manage workers in the agency.
     * Only the owner can manage their agency's workers.
     */
    public function manageWorkers(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can add workers to the agency.
     * Only the owner can add workers.
     */
    public function addWorker(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can remove workers from the agency.
     * Only the owner can remove workers.
     */
    public function removeWorker(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage clients.
     * Only the owner can manage agency clients.
     */
    public function manageClients(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can view commission information.
     * Only owner or admin can view commission details.
     */
    public function viewCommissions(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update Stripe Connect settings.
     * Only owner can update their payment settings.
     */
    public function updatePayment(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can view payout history.
     * Only owner or admin can view payouts.
     */
    public function viewPayouts(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can submit go-live request.
     * Only owner can submit go-live request.
     */
    public function submitGoLive(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can approve go-live request.
     * Only admin can approve go-live requests.
     */
    public function approveGoLive(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view compliance information.
     * Only owner or admin can view compliance status.
     */
    public function viewCompliance(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update compliance documents.
     * Only owner can update compliance documents.
     */
    public function updateCompliance(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->id === $agencyProfile->user_id;
    }

    /**
     * Determine whether the user can manage the agency tier.
     * Only admin can change agency tiers.
     */
    public function manageTier(User $user, AgencyProfile $agencyProfile): bool
    {
        return $user->isAdmin();
    }
}
