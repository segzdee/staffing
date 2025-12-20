<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkerProfile;

class WorkerProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     * Any authenticated user can view worker profiles list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Owner can view their own profile, businesses can view for applications.
     */
    public function view(User $user, WorkerProfile $workerProfile): bool
    {
        // Admin can view all profiles
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can always view their own profile
        if ($user->id === $workerProfile->user_id) {
            return true;
        }

        // Businesses can view worker profiles (for shift applications/assignments)
        if ($user->isBusiness()) {
            return true;
        }

        // Agencies can view worker profiles (for managing workers)
        if ($user->isAgency()) {
            return true;
        }

        // Other workers can view public profiles (for referrals, etc.)
        if ($user->isWorker()) {
            // Check if profile is public or has public profile enabled
            if (isset($workerProfile->public_profile_enabled) && $workerProfile->public_profile_enabled) {
                return true;
            }

            // Allow viewing basic profile info for other workers
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Workers can create their own profiles during registration.
     */
    public function create(User $user): bool
    {
        // Only workers can create worker profiles
        if (! $user->isWorker()) {
            return false;
        }

        // Check if user already has a worker profile
        if ($user->workerProfile()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Only the owner can update their profile.
     */
    public function update(User $user, WorkerProfile $workerProfile): bool
    {
        // Admin can update any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Only owner can update their profile
        return $user->id === $workerProfile->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Owner or admin can delete the profile.
     */
    public function delete(User $user, WorkerProfile $workerProfile): bool
    {
        // Admin can delete any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can delete their own profile
        return $user->id === $workerProfile->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins or owner can restore deleted profiles.
     */
    public function restore(User $user, WorkerProfile $workerProfile): bool
    {
        // Admin can restore any profile
        if ($user->isAdmin()) {
            return true;
        }

        // Owner can restore their own profile
        return $user->id === $workerProfile->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete profiles.
     */
    public function forceDelete(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify the worker profile.
     * Only admins can verify profiles.
     */
    public function verify(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update skills on the profile.
     * Only the owner can update their skills.
     */
    public function updateSkills(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update certifications on the profile.
     * Only the owner can update their certifications.
     */
    public function updateCertifications(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update availability on the profile.
     * Only the owner can update their availability.
     */
    public function updateAvailability(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id;
    }

    /**
     * Determine whether the user can view sensitive profile information.
     * Only owner or admin can view sensitive info (SSN, bank details, etc.).
     */
    public function viewSensitive(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can view earnings information.
     * Only owner or admin can view earnings.
     */
    public function viewEarnings(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update payment details.
     * Only owner can update their payment information.
     */
    public function updatePayment(User $user, WorkerProfile $workerProfile): bool
    {
        return $user->id === $workerProfile->user_id;
    }
}
