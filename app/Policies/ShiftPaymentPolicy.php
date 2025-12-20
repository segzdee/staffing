<?php

namespace App\Policies;

use App\Models\ShiftPayment;
use App\Models\User;
use Carbon\Carbon;

class ShiftPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admin can view all, workers/businesses can view their own payments.
     */
    public function viewAny(User $user): bool
    {
        // Admin can view all payments
        if ($user->isAdmin()) {
            return true;
        }

        // Workers can view payments they received
        if ($user->isWorker()) {
            return true;
        }

        // Businesses can view payments they made
        if ($user->isBusiness()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     * User must be the worker or business associated with the payment.
     */
    public function view(User $user, ShiftPayment $shiftPayment): bool
    {
        // Admin can view all payments
        if ($user->isAdmin()) {
            return true;
        }

        // Worker can view their received payments
        if ($user->id === $shiftPayment->worker_id) {
            return true;
        }

        // Business can view their made payments
        if ($user->id === $shiftPayment->business_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     * Only business users can create shift payments.
     */
    public function create(User $user): bool
    {
        return $user->isBusiness();
    }

    /**
     * Determine whether the user can update the model.
     * Only admins can update shift payments (adjustments, etc.).
     */
    public function update(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Only admins can delete shift payments.
     */
    public function delete(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     * Only admins can restore deleted payments.
     */
    public function restore(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only admins can force delete payments.
     */
    public function forceDelete(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can dispute the payment.
     * Worker can dispute within 7 days of payment release.
     */
    public function dispute(User $user, ShiftPayment $shiftPayment): bool
    {
        // Only the worker who received the payment can dispute
        if ($user->id !== $shiftPayment->worker_id) {
            return false;
        }

        // Cannot dispute an already disputed payment
        if ($shiftPayment->disputed) {
            return false;
        }

        // Cannot dispute if payment is still in escrow (nothing released yet)
        if ($shiftPayment->status === 'in_escrow' || $shiftPayment->status === 'pending') {
            return false;
        }

        // Check if within 7-day dispute window
        $releaseDate = $shiftPayment->released_at ?? $shiftPayment->created_at;
        if ($releaseDate) {
            $disputeDeadline = Carbon::parse($releaseDate)->addDays(7);
            if (Carbon::now()->greaterThan($disputeDeadline)) {
                return false; // Past 7-day window
            }
        }

        return true;
    }

    /**
     * Determine whether the user can release the payment.
     * Business can release from escrow, admin can also release.
     */
    public function release(User $user, ShiftPayment $shiftPayment): bool
    {
        // Admin can always release payments
        if ($user->isAdmin()) {
            return true;
        }

        // Only the business who made the payment can release
        if ($user->id !== $shiftPayment->business_id) {
            return false;
        }

        // Can only release payments that are in escrow
        if ($shiftPayment->status !== 'in_escrow') {
            return false;
        }

        // Cannot release disputed payments
        if ($shiftPayment->disputed) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can refund the payment.
     * Only admin can process refunds.
     */
    public function refund(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can resolve a dispute.
     * Only admin can resolve disputes.
     */
    public function resolveDispute(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can initiate payout.
     * Only admin or system can initiate payouts.
     */
    public function initiatePayout(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can adjust the payment amount.
     * Only admin can make adjustments.
     */
    public function adjust(User $user, ShiftPayment $shiftPayment): bool
    {
        return $user->isAdmin();
    }
}
