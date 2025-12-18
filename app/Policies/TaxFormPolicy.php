<?php

namespace App\Policies;

use App\Models\TaxForm;
use App\Models\User;

/**
 * GLO-002: Tax Form Authorization Policy
 */
class TaxFormPolicy
{
    /**
     * Determine whether the user can view any tax forms.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the tax form.
     */
    public function view(User $user, TaxForm $taxForm): bool
    {
        // Users can view their own forms
        if ($user->id === $taxForm->user_id) {
            return true;
        }

        // Admins can view any form
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create tax forms.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the tax form.
     */
    public function update(User $user, TaxForm $taxForm): bool
    {
        // Only owner can update their own rejected forms
        if ($user->id === $taxForm->user_id && $taxForm->status === TaxForm::STATUS_REJECTED) {
            return true;
        }

        // Admins can update any form
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the tax form.
     */
    public function delete(User $user, TaxForm $taxForm): bool
    {
        // Only owner can delete their own pending/rejected forms
        if ($user->id === $taxForm->user_id) {
            return in_array($taxForm->status, [TaxForm::STATUS_PENDING, TaxForm::STATUS_REJECTED]);
        }

        // Admins can delete any form
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can verify the tax form.
     */
    public function verify(User $user, TaxForm $taxForm): bool
    {
        // Only admins can verify forms
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can reject the tax form.
     */
    public function reject(User $user, TaxForm $taxForm): bool
    {
        // Only admins can reject forms
        return $user->isAdmin();
    }
}
