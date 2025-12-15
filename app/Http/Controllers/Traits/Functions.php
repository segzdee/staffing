<?php

namespace App\Http\Controllers\Traits;

use App\Models\Deposits;
use App\Models\Transactions;
use Carbon\Carbon;

/**
 * Common functions trait for payment processing and transactions.
 * Used by webhook controllers and jobs for consistent handling.
 */
trait Functions
{
    /**
     * Calculate earnings distribution between admin (platform) and user (creator).
     *
     * @param float|null $customFee User's custom fee percentage (if any)
     * @param float $amount Transaction amount
     * @param float|null $gatewayFee Payment gateway fee percentage
     * @param float|null $gatewayCents Payment gateway fixed fee in cents
     * @return array
     */
    protected function earningsAdminUser($customFee, $amount, $gatewayFee = null, $gatewayCents = null)
    {
        // Default platform fee percentage (35% for OvertimeStaff)
        $platformFeePercentage = config('services.platform.fee_percentage', 35);

        // Apply custom fee if set
        if ($customFee !== null && $customFee > 0) {
            $platformFeePercentage = $customFee;
        }

        // Calculate gateway fees
        $gatewayFeeAmount = 0;
        if ($gatewayFee !== null && $gatewayFee > 0) {
            $gatewayFeeAmount = ($amount * $gatewayFee / 100);
        }
        if ($gatewayCents !== null && $gatewayCents > 0) {
            $gatewayFeeAmount += ($gatewayCents / 100);
        }

        // Calculate platform fee
        $adminEarnings = ($amount * $platformFeePercentage / 100);

        // Calculate user earnings (after platform fee and gateway fees)
        $userEarnings = $amount - $adminEarnings - $gatewayFeeAmount;

        // Ensure no negative earnings
        $userEarnings = max(0, $userEarnings);

        return [
            'user' => round($userEarnings, 2),
            'admin' => round($adminEarnings, 2),
            'percentageApplied' => $platformFeePercentage,
            'gatewayFee' => round($gatewayFeeAmount, 2),
        ];
    }

    /**
     * Create a transaction record.
     *
     * @param string $txnId Transaction ID
     * @param int $userId User ID
     * @param int $subscriptionId Subscription ID
     * @param int $subscriptedUserId Subscribed user ID
     * @param float $amount Transaction amount
     * @param float $userEarnings User earnings
     * @param float $adminEarnings Admin/platform earnings
     * @param string $paymentGateway Payment gateway name
     * @param string $type Transaction type
     * @param float $percentageApplied Fee percentage applied
     * @param string|null $taxes Tax IDs (underscore separated)
     * @return Transactions|null
     */
    protected function transaction(
        $txnId,
        $userId,
        $subscriptionId,
        $subscriptedUserId,
        $amount,
        $userEarnings,
        $adminEarnings,
        $paymentGateway,
        $type,
        $percentageApplied,
        $taxes = null
    ) {
        // Check if Transactions model exists
        if (!class_exists('App\Models\Transactions')) {
            return null;
        }

        try {
            return Transactions::create([
                'txn_id' => $txnId,
                'user_id' => $userId,
                'subscriptions_id' => $subscriptionId,
                'subscribed' => $subscriptedUserId,
                'amount' => $amount,
                'earning_net_user' => $userEarnings,
                'earning_net_admin' => $adminEarnings,
                'payment_gateway' => $paymentGateway,
                'type' => $type,
                'percentage_applied' => $percentageApplied,
                'taxes' => $taxes,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Transaction creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a deposit record.
     *
     * @param int $userId User ID
     * @param string $txnId Transaction ID
     * @param float $amount Deposit amount
     * @param string $paymentGateway Payment gateway name
     * @param string|null $taxes Tax information
     * @return Deposits|null
     */
    protected function deposit($userId, $txnId, $amount, $paymentGateway, $taxes = null)
    {
        // Check if Deposits model exists
        if (!class_exists('App\Models\Deposits')) {
            return null;
        }

        try {
            return Deposits::create([
                'user_id' => $userId,
                'txn_id' => $txnId,
                'amount' => $amount,
                'payment_gateway' => $paymentGateway,
                'taxes' => $taxes,
                'created_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Deposit creation failed: ' . $e->getMessage());
            return null;
        }
    }
}
