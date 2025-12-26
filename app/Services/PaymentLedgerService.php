<?php

namespace App\Services;

use App\Models\PaymentLedger;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment Ledger Service
 *
 * PRIORITY-0: Single source of truth for all payment mutations
 * Immutable ledger entries for audit trail and balance calculation
 *
 * FIN-003: Payment Ledger System
 */
class PaymentLedgerService
{
    /**
     * Create a ledger entry.
     *
     * @param  array  $data  Ledger entry data
     * @return PaymentLedger The created ledger entry
     *
     * @throws \Exception If entry creation fails
     */
    public function createEntry(array $data): PaymentLedger
    {
        return DB::transaction(function () use ($data) {
            // SECURITY: Set created_source if not provided (default based on auth context)
            if (! isset($data['created_source'])) {
                $data['created_source'] = auth()->check() ? 'user' : 'system';
            }

            // PERFORMANCE: Optimize balance calculation - use cached balance if available
            // For now, calculate from ledger (O(n) - will optimize in future migration)
            $balanceAfter = $this->calculateBalanceAfter(
                $data['user_id'] ?? null,
                $data['shift_payment_id'] ?? null,
                $data['amount'] ?? 0
            );

            $data['balance_after'] = $balanceAfter;

            // SECURITY: Ensure provider_payment_id uniqueness for escrow entries
            if (isset($data['provider_payment_id']) &&
                isset($data['entry_type']) &&
                $data['entry_type'] === 'escrow_captured') {
                $existing = PaymentLedger::where('provider', $data['provider'])
                    ->where('provider_payment_id', $data['provider_payment_id'])
                    ->where('entry_type', 'escrow_captured')
                    ->first();

                if ($existing) {
                    Log::warning('Duplicate escrow entry attempted', [
                        'provider' => $data['provider'],
                        'provider_payment_id' => $data['provider_payment_id'],
                    ]);
                    throw new \Exception('Escrow entry already exists for this payment intent');
                }
            }

            return PaymentLedger::create($data);
        });
    }

    /**
     * Record escrow capture.
     *
     * @param  ShiftAssignment  $assignment  The shift assignment
     * @param  string  $provider  Payment provider
     * @param  string  $providerPaymentId  Provider payment ID
     * @param  int  $amount  Amount in cents
     * @param  string  $currency  Currency code
     * @return PaymentLedger The ledger entry
     */
    public function recordEscrowCapture(
        ShiftAssignment $assignment,
        string $provider,
        string $providerPaymentId,
        int $amount,
        string $currency = 'USD',
        ?string $createdSource = null
    ): PaymentLedger {
        return $this->createEntry([
            'shift_payment_id' => $assignment->shiftPayment?->id,
            'shift_assignment_id' => $assignment->id,
            'user_id' => $assignment->shift->business->id,
            'provider' => $provider,
            'provider_payment_id' => $providerPaymentId,
            'entry_type' => 'escrow_captured',
            'amount' => $amount,
            'currency' => $currency,
            'description' => "Escrow captured for shift #{$assignment->shift_id}",
            'created_by' => auth()->id(),
            'created_source' => $createdSource ?? (auth()->check() ? 'user' : 'system'),
        ]);
    }

    /**
     * Record escrow release.
     *
     * @param  ShiftPayment  $payment  The shift payment
     * @param  string  $provider  Payment provider
     * @param  string  $providerTransferId  Provider transfer ID
     * @param  int  $amount  Amount in cents
     * @param  string  $currency  Currency code
     * @return PaymentLedger The ledger entry
     */
    public function recordEscrowRelease(
        ShiftPayment $payment,
        string $provider,
        string $providerTransferId,
        int $amount,
        string $currency = 'USD',
        ?string $createdSource = null
    ): PaymentLedger {
        return $this->createEntry([
            'shift_payment_id' => $payment->id,
            'shift_assignment_id' => $payment->shiftAssignment->id,
            'user_id' => $payment->shiftAssignment->worker->id,
            'provider' => $provider,
            'provider_transfer_id' => $providerTransferId,
            'entry_type' => 'escrow_released',
            'amount' => -$amount, // Negative for release
            'currency' => $currency,
            'description' => "Escrow released to worker for shift #{$payment->shiftAssignment->shift_id}",
            'created_by' => auth()->id(),
            'created_source' => $createdSource ?? (auth()->check() ? 'user' : 'system'),
        ]);
    }

    /**
     * Record refund.
     *
     * @param  ShiftPayment  $payment  The shift payment
     * @param  string  $provider  Payment provider
     * @param  int  $amount  Refund amount in cents
     * @param  string  $reason  Refund reason
     * @return PaymentLedger The ledger entry
     */
    public function recordRefund(
        ShiftPayment $payment,
        string $provider,
        int $amount,
        string $reason = '',
        ?string $createdSource = null
    ): PaymentLedger {
        return $this->createEntry([
            'shift_payment_id' => $payment->id,
            'shift_assignment_id' => $payment->shiftAssignment->id,
            'user_id' => $payment->shiftAssignment->shift->business->id,
            'provider' => $provider,
            'entry_type' => 'refund_completed',
            'amount' => -$amount, // Negative for refund
            'currency' => $payment->currency ?? 'USD',
            'description' => "Refund processed: {$reason}",
            'metadata' => ['reason' => $reason],
            'created_by' => auth()->id(),
            'created_source' => $createdSource ?? (auth()->check() ? 'user' : 'webhook'),
        ]);
    }

    /**
     * Record fee deduction.
     *
     * @param  ShiftPayment  $payment  The shift payment
     * @param  int  $feeAmount  Fee amount in cents
     * @param  string  $feeType  Fee type (platform_fee, commission, etc.)
     * @return PaymentLedger The ledger entry
     */
    public function recordFee(
        ShiftPayment $payment,
        int $feeAmount,
        string $feeType = 'platform_fee',
        ?string $createdSource = null
    ): PaymentLedger {
        return $this->createEntry([
            'shift_payment_id' => $payment->id,
            'shift_assignment_id' => $payment->shiftAssignment->id,
            'user_id' => $payment->shiftAssignment->shift->business->id,
            'provider' => $payment->provider ?? 'stripe',
            'entry_type' => 'fee_deducted',
            'amount' => -$feeAmount, // Negative for fee
            'currency' => $payment->currency ?? 'USD',
            'description' => ucfirst(str_replace('_', ' ', $feeType)).' deducted',
            'metadata' => ['fee_type' => $feeType],
            'created_by' => auth()->id(),
            'created_source' => $createdSource ?? (auth()->check() ? 'user' : 'system'),
        ]);
    }

    /**
     * Get ledger entries for a payment.
     *
     * @param  ShiftPayment  $payment  The shift payment
     * @return \Illuminate\Database\Eloquent\Collection Ledger entries
     */
    public function getPaymentLedger(ShiftPayment $payment)
    {
        return PaymentLedger::where('shift_payment_id', $payment->id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get current balance for a user.
     *
     * @param  User  $user  The user
     * @return int Balance in cents
     */
    public function getBalance(User $user): int
    {
        return PaymentLedger::where('user_id', $user->id)
            ->sum('amount');
    }

    /**
     * Calculate balance after a new entry.
     *
     * @param  int|null  $userId  User ID
     * @param  int|null  $shiftPaymentId  Shift payment ID
     * @param  int  $newAmount  New entry amount
     * @return int Balance after entry
     */
    protected function calculateBalanceAfter(?int $userId, ?int $shiftPaymentId, int $newAmount): int
    {
        $currentBalance = 0;

        if ($userId) {
            $currentBalance = PaymentLedger::where('user_id', $userId)->sum('amount');
        } elseif ($shiftPaymentId) {
            $payment = ShiftPayment::find($shiftPaymentId);
            if ($payment && $payment->shiftAssignment) {
                $currentBalance = PaymentLedger::where('shift_payment_id', $shiftPaymentId)
                    ->sum('amount');
            }
        }

        return $currentBalance + $newAmount;
    }
}
