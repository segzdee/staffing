<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Model;

/**
 * WithdrawalPolicy
 *
 * SECURITY: Policy for withdrawal operations.
 * Ensures users can only withdraw using their own payout methods.
 */
class WithdrawalPolicy
{
    /**
     * Determine if the user can create a withdrawal.
     */
    public function create(User $user): bool
    {
        // Only workers can create withdrawals
        return $user->user_type === 'worker';
    }

    /**
     * Determine if the user can view the withdrawal.
     *
     * @param  Model|Withdrawal  $withdrawal
     */
    public function view(User $user, $withdrawal): bool
    {
        // Users can only view their own withdrawals
        return $withdrawal->user_id === $user->id;
    }

    /**
     * Determine if the user can withdraw using a specific payout method.
     *
     * @param  mixed  $payoutMethod
     */
    public function withdraw(User $user, $payoutMethod): bool
    {
        // SECURITY: Verify payout method belongs to user
        if (is_object($payoutMethod)) {
            return $payoutMethod->user_id === $user->id && $payoutMethod->is_active;
        }

        // If it's an ID, check via database
        if (is_numeric($payoutMethod)) {
            $method = \DB::table('payout_methods')
                ->where('id', $payoutMethod)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            return $method !== null;
        }

        return false;
    }
}
