<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Shift;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * Create an automatic refund for a cancellation >72 hours.
     */
    public function createAutoCancellationRefund(Shift $shift, $reason = 'cancellation_72hr')
    {
        // Get the shift payment
        $payment = $shift->payment;

        if (! $payment) {
            Log::warning("No payment found for shift {$shift->id}, cannot create refund");

            return null;
        }

        // Check if refund already exists
        if (Refund::where('shift_payment_id', $payment->id)->exists()) {
            Log::info("Refund already exists for payment {$payment->id}");

            return null;
        }

        return DB::transaction(function () use ($shift, $payment, $reason) {
            // Determine refund amount (typically 100% for >72hr cancellations)
            $refundAmount = $payment->amount_gross->getAmount() / 100;

            $refund = Refund::create([
                'business_id' => $payment->business_id,
                'shift_id' => $shift->id,
                'shift_payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'original_amount' => $refundAmount,
                'refund_type' => 'auto_cancellation',
                'refund_reason' => $reason,
                'reason_description' => 'Automatic refund for shift cancelled more than 72 hours in advance',
                'refund_method' => 'original_payment_method',
                'status' => 'pending',
                'metadata' => [
                    'shift_title' => $shift->title,
                    'shift_date' => $shift->start_time->toDateString(),
                    'cancelled_at' => $shift->cancelled_at?->toDateTimeString(),
                ],
            ]);

            Log::info("Created auto-cancellation refund {$refund->refund_number}", [
                'shift_id' => $shift->id,
                'amount' => $refundAmount,
            ]);

            return $refund;
        });
    }

    /**
     * Create a refund for dispute resolution.
     */
    public function createDisputeRefund(
        ShiftPayment $payment,
        float $refundAmount,
        ?string $description = null
    ) {
        return DB::transaction(function () use ($payment, $refundAmount, $description) {
            $refund = Refund::create([
                'business_id' => $payment->business_id,
                'shift_id' => $payment->shift_id,
                'shift_payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'original_amount' => $payment->amount_gross->getAmount() / 100,
                'refund_type' => 'dispute_resolution',
                'refund_reason' => 'dispute_resolved',
                'reason_description' => $description ?? 'Refund issued due to dispute resolution',
                'refund_method' => 'original_payment_method',
                'status' => 'pending',
                'metadata' => [
                    'dispute_id' => $payment->dispute_reason,
                ],
            ]);

            Log::info("Created dispute refund {$refund->refund_number}", [
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
            ]);

            return $refund;
        });
    }

    /**
     * Create a refund for overcharge correction.
     */
    public function createOverchargeRefund(
        ShiftPayment $payment,
        float $refundAmount,
        string $description
    ) {
        return DB::transaction(function () use ($payment, $refundAmount, $description) {
            $refund = Refund::create([
                'business_id' => $payment->business_id,
                'shift_id' => $payment->shift_id,
                'shift_payment_id' => $payment->id,
                'refund_amount' => $refundAmount,
                'original_amount' => $payment->amount_gross->getAmount() / 100,
                'refund_type' => 'overcharge_correction',
                'refund_reason' => 'overcharge',
                'reason_description' => $description,
                'refund_method' => 'original_payment_method',
                'status' => 'pending',
            ]);

            Log::info("Created overcharge refund {$refund->refund_number}", [
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
            ]);

            return $refund;
        });
    }

    /**
     * Process a pending refund.
     */
    public function processRefund(Refund $refund)
    {
        if (! $refund->isPending()) {
            Log::warning("Refund {$refund->refund_number} is not pending, skipping");

            return false;
        }

        $refund->markAsProcessing();

        try {
            // Determine refund method
            if ($refund->refund_method === 'credit_balance') {
                return $this->processCreditBalanceRefund($refund);
            } elseif ($refund->refund_method === 'original_payment_method') {
                return $this->processPaymentGatewayRefund($refund);
            } else {
                // Manual handling
                $refund->update(['status' => 'pending']);

                return false;
            }
        } catch (\Exception $e) {
            $refund->markAsFailed($e->getMessage());
            Log::error("Failed to process refund {$refund->refund_number}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process refund to business credit balance.
     */
    protected function processCreditBalanceRefund(Refund $refund)
    {
        return DB::transaction(function () use ($refund) {
            $business = $refund->business;
            $profile = $business->businessProfile;

            // Add to credit balance
            $profile->credit_used -= $refund->refund_amount;
            $profile->credit_available = $profile->credit_limit - $profile->credit_used;
            $profile->credit_utilization = $profile->credit_limit > 0
                ? ($profile->credit_used / $profile->credit_limit) * 100
                : 0;
            $profile->save();

            // Create transaction record
            \App\Models\BusinessCreditTransaction::create([
                'business_id' => $business->id,
                'shift_id' => $refund->shift_id,
                'transaction_type' => 'refund',
                'amount' => -$refund->refund_amount,
                'balance_before' => $profile->credit_used + $refund->refund_amount,
                'balance_after' => $profile->credit_used,
                'description' => "Refund: {$refund->reason_description}",
                'reference_id' => $refund->refund_number,
                'reference_type' => 'refund',
            ]);

            // Generate credit note
            $refund->generateCreditNote();

            // Mark as completed
            $refund->markAsCompleted(null, 'credit_balance');

            Log::info("Processed credit balance refund {$refund->refund_number}");

            // Send notification to business
            $this->sendRefundNotification($refund);

            return true;
        });
    }

    /**
     * Process refund through payment gateway.
     */
    protected function processPaymentGatewayRefund(Refund $refund)
    {
        $payment = $refund->shiftPayment;

        if (! $payment) {
            throw new \Exception('No payment found for refund');
        }

        // Determine gateway from payment intent ID
        if ($payment->stripe_payment_intent_id) {
            return $this->processStripeRefund($refund, $payment);
        } elseif ($payment->paypal_transaction_id ?? false) {
            return $this->processPayPalRefund($refund, $payment);
        } else {
            throw new \Exception('No payment gateway found for payment');
        }
    }

    /**
     * Process Stripe refund.
     */
    protected function processStripeRefund(Refund $refund, ShiftPayment $payment)
    {
        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Create refund in Stripe
            $stripeRefund = \Stripe\Refund::create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => $refund->refund_amount * 100, // Convert to cents
                'reason' => $this->mapRefundReasonToStripe($refund->refund_reason),
                'metadata' => [
                    'refund_number' => $refund->refund_number,
                    'shift_id' => $refund->shift_id,
                ],
            ]);

            // Update payment record
            $payment->update([
                'status' => 'refunded',
                'refund_amount' => $refund->refund_amount,
                'refund_reason' => $refund->reason_description,
                'refunded_at' => now(),
                'stripe_refund_id' => $stripeRefund->id,
            ]);

            // Generate credit note
            $refund->generateCreditNote();

            // Mark refund as completed
            $refund->markAsCompleted($stripeRefund->id, 'stripe');

            Log::info("Processed Stripe refund {$refund->refund_number}", [
                'stripe_refund_id' => $stripeRefund->id,
            ]);

            // Send notification to business
            $this->sendRefundNotification($refund);

            return true;

        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception('Stripe error: '.$e->getMessage());
        }
    }

    /**
     * Process PayPal refund.
     */
    protected function processPayPalRefund(Refund $refund, ShiftPayment $payment)
    {
        try {
            // Initialize PayPal client
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            // Get the capture ID from the payment
            $captureId = $payment->paypal_capture_id ?? $payment->paypal_transaction_id;

            if (! $captureId) {
                throw new \Exception('No PayPal capture ID found for payment');
            }

            // Create refund request
            $refundData = [
                'amount' => [
                    'value' => number_format($refund->refund_amount, 2, '.', ''),
                    'currency_code' => config('paypal.currency', 'USD'),
                ],
                'note_to_payer' => substr($refund->reason_description ?? 'Refund processed', 0, 255),
            ];

            // Process refund through PayPal
            $response = $provider->refundCapturedPayment($captureId, $refundData);

            // Check for successful refund
            if (isset($response['id']) && isset($response['status'])) {
                if ($response['status'] === 'COMPLETED' || $response['status'] === 'PENDING') {
                    // Update payment record
                    $payment->update([
                        'status' => 'refunded',
                        'refund_amount' => $refund->refund_amount,
                        'refund_reason' => $refund->reason_description,
                        'refunded_at' => now(),
                        'paypal_refund_id' => $response['id'],
                    ]);

                    // Generate credit note
                    $refund->generateCreditNote();

                    // Mark refund as completed
                    $refund->markAsCompleted($response['id'], 'paypal');

                    Log::info("Processed PayPal refund {$refund->refund_number}", [
                        'paypal_refund_id' => $response['id'],
                        'status' => $response['status'],
                    ]);

                    // Send notification to business
                    $this->sendRefundNotification($refund);

                    return true;
                }
            }

            // Handle error response
            $errorMessage = $response['message'] ?? $response['error']['message'] ?? 'Unknown PayPal error';
            throw new \Exception('PayPal refund failed: '.$errorMessage);
        } catch (\Exception $e) {
            Log::error("PayPal refund error for {$refund->refund_number}", [
                'error' => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);
            throw new \Exception('PayPal error: '.$e->getMessage());
        }
    }

    /**
     * Map refund reason to Stripe reason code.
     */
    protected function mapRefundReasonToStripe($reason)
    {
        return match ($reason) {
            'duplicate_charge' => 'duplicate',
            'billing_error', 'overcharge' => 'fraudulent',
            default => 'requested_by_customer',
        };
    }

    /**
     * Create a manual refund (admin-initiated).
     */
    public function createManualRefund(
        User $business,
        float $refundAmount,
        string $reason,
        string $description,
        $shiftId = null,
        $paymentId = null,
        $refundMethod = 'credit_balance',
        $adminId = null
    ) {
        return DB::transaction(function () use (
            $business,
            $refundAmount,
            $reason,
            $description,
            $shiftId,
            $paymentId,
            $refundMethod,
            $adminId
        ) {
            $refund = Refund::create([
                'business_id' => $business->id,
                'shift_id' => $shiftId,
                'shift_payment_id' => $paymentId,
                'processed_by_admin_id' => $adminId,
                'refund_amount' => $refundAmount,
                'original_amount' => $refundAmount,
                'refund_type' => 'manual_adjustment',
                'refund_reason' => $reason,
                'reason_description' => $description,
                'refund_method' => $refundMethod,
                'status' => 'pending',
            ]);

            Log::info("Created manual refund {$refund->refund_number}", [
                'business_id' => $business->id,
                'amount' => $refundAmount,
                'admin_id' => $adminId,
            ]);

            return $refund;
        });
    }

    /**
     * Retry a failed refund.
     */
    public function retryRefund(Refund $refund)
    {
        if (! $refund->isFailed()) {
            return false;
        }

        // Reset status to pending
        $refund->update([
            'status' => 'pending',
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        // Process again
        return $this->processRefund($refund);
    }

    /**
     * Send refund completion notification to the business.
     */
    protected function sendRefundNotification(Refund $refund): void
    {
        try {
            $business = $refund->business;

            if ($business) {
                $business->notify(new \App\Notifications\RefundCompletedNotification($refund));

                Log::info("Sent refund notification for {$refund->refund_number}", [
                    'business_id' => $business->id,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the refund if notification fails
            Log::warning("Failed to send refund notification for {$refund->refund_number}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
