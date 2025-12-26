<?php

namespace App\Services\Interfaces;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;

/**
 * Payment Service Interface
 *
 * Defines the contract for payment processing operations.
 * All payment services must implement this interface.
 *
 * ARCH-002: Unified Payment Service Interface
 */
interface PaymentServiceInterface
{
    /**
     * Process a payment for a shift assignment.
     *
     * @param  ShiftAssignment  $assignment  The shift assignment
     * @param  array  $paymentData  Payment method and details
     * @return ShiftPayment The created payment record
     *
     * @throws \Exception If payment processing fails
     */
    public function processPayment(ShiftAssignment $assignment, array $paymentData): ShiftPayment;

    /**
     * Release escrow funds to worker.
     *
     * @param  ShiftPayment  $payment  The payment to release
     * @return bool Success status
     *
     * @throws \Exception If release fails
     */
    public function releaseEscrow(ShiftPayment $payment): bool;

    /**
     * Process a refund.
     *
     * @param  ShiftPayment  $payment  The payment to refund
     * @param  float  $amount  Refund amount (null for full refund)
     * @param  string  $reason  Refund reason
     * @return bool Success status
     *
     * @throws \Exception If refund fails
     */
    public function processRefund(ShiftPayment $payment, ?float $amount = null, string $reason = ''): bool;

    /**
     * Process a dispute.
     *
     * @param  ShiftPayment  $payment  The payment in dispute
     * @param  array  $disputeData  Dispute details
     * @return bool Success status
     *
     * @throws \Exception If dispute processing fails
     */
    public function processDispute(ShiftPayment $payment, array $disputeData): bool;

    /**
     * Get payment status.
     *
     * @param  ShiftPayment  $payment  The payment to check
     * @return string Payment status
     */
    public function getPaymentStatus(ShiftPayment $payment): string;

    /**
     * Verify webhook signature.
     *
     * @param  string  $payload  Webhook payload
     * @param  string  $signature  Webhook signature
     * @param  string  $provider  Payment provider
     * @return bool Verification result
     */
    public function verifyWebhookSignature(string $payload, string $signature, string $provider): bool;

    /**
     * Process webhook event with idempotency.
     *
     * @param  array  $eventData  Webhook event data
     * @param  string  $provider  Payment provider
     * @return array Processing result
     */
    public function processWebhook(array $eventData, string $provider): array;
}
