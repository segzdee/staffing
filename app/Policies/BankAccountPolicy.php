<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;

/**
 * GLO-008: Cross-Border Payments - Bank Account Policy
 *
 * Authorization policy for bank account management.
 */
class BankAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BankAccount $bankAccount): bool
    {
        // Users can only view their own bank accounts
        // Admins can view any bank account
        return $user->id === $bankAccount->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only workers and agencies can add bank accounts (for payouts)
        return $user->isWorker() || $user->isAgency();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BankAccount $bankAccount): bool
    {
        // Users can only update their own bank accounts
        return $user->id === $bankAccount->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BankAccount $bankAccount): bool
    {
        // Users can only delete their own bank accounts
        return $user->id === $bankAccount->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BankAccount $bankAccount): bool
    {
        return $user->id === $bankAccount->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BankAccount $bankAccount): bool
    {
        // Only admins can permanently delete
        return $user->isAdmin();
    }
}
