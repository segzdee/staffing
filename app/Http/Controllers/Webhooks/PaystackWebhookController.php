<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ShiftPayment;
use App\Models\WorkerFeaturedStatus;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PaystackWebhookController
 *
 * Handles incoming webhooks from Paystack payment processing (Africa).
 *
 * Webhooks handled:
 * - charge.success - Payment charge successful
 * - transfer.success - Transfer to recipient successful
 * - transfer.failed - Transfer to recipient failed
 * - transfer.reversed - Transfer reversed
 * - refund.processed - Refund processed
 * - refund.failed - Refund failed
 * - subscription.create - Subscription created
 * - subscription.disable - Subscription disabled
 * - invoice.create - Invoice created
 * - invoice.payment_failed - Invoice payment failed
 *
 * Security:
 * - HMAC SHA512 signature validation required
 * - Validates against Paystack secret key
 */
class PaystackWebhookController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Handle incoming Paystack webhook.
     *
     * POST /webhooks/paystack
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();

        Log::info('Paystack webhook received', [
            'event' => $request->input('event'),
        ]);

        // Verify webhook signature
        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('Paystack webhook signature verification failed');

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (! $event || ! isset($event['event'])) {
            Log::warning('Paystack webhook: Invalid payload structure');

            return response('Invalid payload', 400);
        }

        $eventType = $event['event'];
        $data = $event['data'] ?? [];
        $eventId = $data['id'] ?? $data['reference'] ?? uniqid('paystack_', true);

        // PRIORITY-0: Idempotency check
        $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
        $shouldProcess = $idempotencyService->shouldProcess('paystack', $eventId);

        if (! $shouldProcess['should_process']) {
            Log::info('Paystack webhook event already processed, skipping', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'message' => $shouldProcess['message'],
            ]);

            return response('Event already processed', 200);
        }

        // Record event for idempotency
        $webhookEvent = $idempotencyService->recordEvent('paystack', $eventId, $eventType, $payload);

        // Mark as processing
        if (! $idempotencyService->markProcessing($webhookEvent)) {
            return response('Event already processing', 200);
        }

        Log::info('Paystack webhook verified', [
            'event_type' => $eventType,
            'reference' => $data['reference'] ?? null,
            'event_id' => $eventId,
        ]);

        // Route to appropriate handler
        $methodName = $this->getHandlerMethod($eventType);

        if (method_exists($this, $methodName)) {
            try {
                $result = $this->$methodName($event, $data);

                // PRIORITY-0: Mark as processed successfully
                $idempotencyService->markProcessed($webhookEvent, ['success' => true]);

                return $result;
            } catch (\Exception $e) {
                Log::error('Paystack webhook handler error', [
                    'event_type' => $eventType,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // PRIORITY-0: Mark as failed
                $idempotencyService->markFailed($webhookEvent, $e->getMessage(), true);

                return response('Webhook handler error', 500);
            }
        }

        // Log unhandled event types
        Log::info('Unhandled Paystack webhook event', [
            'event_type' => $eventType,
        ]);

        // PRIORITY-0: Mark as processed (unhandled but acknowledged)
        $idempotencyService->markProcessed($webhookEvent, ['unhandled' => true]);

        return response('Webhook received', 200);
    }

    /**
     * Verify Paystack webhook signature.
     *
     * Paystack uses HMAC SHA512 signature verification.
     * The signature is sent in the x-paystack-signature header.
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $secretKey = config('paystack.secret_key');

        // Skip verification in local/testing environments if secret key not set
        if (empty($secretKey) && app()->environment('local', 'testing')) {
            Log::warning('Paystack webhook verification skipped - no secret key configured');

            return true;
        }

        $signature = $request->header('x-paystack-signature');

        if (! $signature) {
            Log::warning('Paystack webhook missing signature header');

            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);

        // Compare signatures (timing-safe comparison)
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Paystack webhook signature mismatch', [
                'expected' => substr($expectedSignature, 0, 20).'...',
                'received' => substr($signature, 0, 20).'...',
            ]);

            return false;
        }

        return true;
    }

    /**
     * Handle charge.success webhook.
     *
     * Fired when a payment charge is successful.
     */
    protected function handleChargeSuccess(array $event, array $data): Response
    {
        $reference = $data['reference'] ?? null;
        $amount = ($data['amount'] ?? 0) / 100; // Paystack amounts are in kobo (smallest unit)
        $currency = $data['currency'] ?? 'NGN';
        $metadata = $data['metadata'] ?? [];
        $transactionId = $data['id'] ?? null;

        Log::info('Paystack charge successful', [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'transaction_id' => $transactionId,
        ]);

        // Handle featured status payment
        $paymentType = $metadata['payment_type'] ?? null;

        if ($paymentType === 'featured_status') {
            return $this->handleFeaturedStatusPayment($transactionId, $metadata, $amount);
        }

        // Handle shift payment
        if ($paymentType === 'shift_payment') {
            return $this->handleShiftPaymentCharge($transactionId, $metadata, $amount, $reference);
        }

        // Generic payment handling based on reference prefix
        if ($reference && str_starts_with($reference, 'featured_')) {
            $statusId = (int) str_replace('featured_', '', $reference);

            return $this->handleFeaturedStatusPayment($transactionId, ['featured_status_id' => $statusId], $amount);
        }

        if ($reference && str_starts_with($reference, 'shift_payment_')) {
            $paymentId = (int) str_replace('shift_payment_', '', $reference);

            return $this->handleShiftPaymentCharge($transactionId, ['payment_id' => $paymentId], $amount, $reference);
        }

        return response('Charge processed', 200);
    }

    /**
     * Handle transfer.success webhook.
     *
     * Fired when a transfer to a recipient is successful.
     */
    protected function handleTransferSuccess(array $event, array $data): Response
    {
        $reference = $data['reference'] ?? null;
        $amount = ($data['amount'] ?? 0) / 100;
        $recipientCode = $data['recipient']['recipient_code'] ?? null;

        Log::info('Paystack transfer successful', [
            'reference' => $reference,
            'amount' => $amount,
            'recipient_code' => $recipientCode,
        ]);

        // Find payment by reference if it's a worker payout
        if ($reference && str_starts_with($reference, 'payout_')) {
            $paymentId = (int) str_replace('payout_', '', $reference);
            $payment = ShiftPayment::find($paymentId);

            if ($payment) {
                $payment->completePayout();

                Log::info('Worker payout completed via Paystack', [
                    'payment_id' => $payment->id,
                    'reference' => $reference,
                ]);
            }
        }

        return response('Transfer recorded', 200);
    }

    /**
     * Handle transfer.failed webhook.
     *
     * Fired when a transfer to a recipient fails.
     */
    protected function handleTransferFailed(array $event, array $data): Response
    {
        $reference = $data['reference'] ?? null;
        $amount = ($data['amount'] ?? 0) / 100;
        $reason = $data['reason'] ?? 'Unknown';

        Log::warning('Paystack transfer failed', [
            'reference' => $reference,
            'amount' => $amount,
            'reason' => $reason,
        ]);

        // Update payment status if it's a worker payout
        if ($reference && str_starts_with($reference, 'payout_')) {
            $paymentId = (int) str_replace('payout_', '', $reference);
            $payment = ShiftPayment::find($paymentId);

            if ($payment) {
                $payment->update([
                    'status' => 'payout_failed',
                    'internal_notes' => "Paystack transfer failed: {$reason}",
                ]);

                Log::info('Worker payout marked as failed', [
                    'payment_id' => $payment->id,
                    'reason' => $reason,
                ]);
            }
        }

        return response('Transfer failure recorded', 200);
    }

    /**
     * Handle transfer.reversed webhook.
     *
     * Fired when a transfer is reversed.
     */
    protected function handleTransferReversed(array $event, array $data): Response
    {
        $reference = $data['reference'] ?? null;
        $amount = ($data['amount'] ?? 0) / 100;

        Log::warning('Paystack transfer reversed', [
            'reference' => $reference,
            'amount' => $amount,
        ]);

        // Update payment status
        if ($reference && str_starts_with($reference, 'payout_')) {
            $paymentId = (int) str_replace('payout_', '', $reference);
            $payment = ShiftPayment::find($paymentId);

            if ($payment) {
                $payment->update([
                    'status' => 'payout_reversed',
                    'internal_notes' => 'Paystack transfer reversed',
                ]);
            }
        }

        return response('Transfer reversal recorded', 200);
    }

    /**
     * Handle refund.processed webhook.
     *
     * Fired when a refund is successfully processed.
     */
    protected function handleRefundProcessed(array $event, array $data): Response
    {
        $transactionReference = $data['transaction_reference'] ?? null;
        $refundAmount = ($data['amount'] ?? 0) / 100;

        Log::info('Paystack refund processed', [
            'transaction_reference' => $transactionReference,
            'refund_amount' => $refundAmount,
        ]);

        // Find payment by transaction reference
        $payment = ShiftPayment::where('paystack_transaction_id', $transactionReference)
            ->orWhere('paystack_reference', $transactionReference)
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
            ]);

            Log::info('Shift payment marked as refunded', [
                'payment_id' => $payment->id,
            ]);
        }

        return response('Refund recorded', 200);
    }

    /**
     * Handle refund.failed webhook.
     *
     * Fired when a refund fails.
     */
    protected function handleRefundFailed(array $event, array $data): Response
    {
        $transactionReference = $data['transaction_reference'] ?? null;
        $reason = $data['message'] ?? 'Unknown reason';

        Log::warning('Paystack refund failed', [
            'transaction_reference' => $transactionReference,
            'reason' => $reason,
        ]);

        return response('Refund failure recorded', 200);
    }

    /**
     * Handle subscription.create webhook.
     *
     * Fired when a subscription is created.
     */
    protected function handleSubscriptionCreate(array $event, array $data): Response
    {
        $subscriptionCode = $data['subscription_code'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;
        $planCode = $data['plan']['plan_code'] ?? null;

        Log::info('Paystack subscription created', [
            'subscription_code' => $subscriptionCode,
            'customer_email' => $customerEmail,
            'plan_code' => $planCode,
        ]);

        return response('Subscription created', 200);
    }

    /**
     * Handle subscription.disable webhook.
     *
     * Fired when a subscription is disabled.
     */
    protected function handleSubscriptionDisable(array $event, array $data): Response
    {
        $subscriptionCode = $data['subscription_code'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;

        Log::info('Paystack subscription disabled', [
            'subscription_code' => $subscriptionCode,
            'customer_email' => $customerEmail,
        ]);

        return response('Subscription disabled', 200);
    }

    /**
     * Handle invoice.create webhook.
     *
     * Fired when an invoice is created.
     */
    protected function handleInvoiceCreate(array $event, array $data): Response
    {
        $invoiceCode = $data['invoice_code'] ?? null;
        $amount = ($data['amount'] ?? 0) / 100;

        Log::info('Paystack invoice created', [
            'invoice_code' => $invoiceCode,
            'amount' => $amount,
        ]);

        return response('Invoice noted', 200);
    }

    /**
     * Handle invoice.payment_failed webhook.
     *
     * Fired when an invoice payment fails.
     */
    protected function handleInvoicePaymentFailed(array $event, array $data): Response
    {
        $invoiceCode = $data['invoice_code'] ?? null;
        $customerEmail = $data['customer']['email'] ?? null;

        Log::warning('Paystack invoice payment failed', [
            'invoice_code' => $invoiceCode,
            'customer_email' => $customerEmail,
        ]);

        return response('Invoice payment failure noted', 200);
    }

    /**
     * Handle featured status payment completion.
     */
    protected function handleFeaturedStatusPayment(string $transactionId, array $metadata, float $amount): Response
    {
        $statusId = $metadata['featured_status_id'] ?? null;

        if (! $statusId) {
            Log::warning('Featured status ID not found in Paystack metadata', [
                'transaction_id' => $transactionId,
                'metadata' => $metadata,
            ]);

            return response('Featured status ID missing', 200);
        }

        $featuredStatus = WorkerFeaturedStatus::find($statusId);

        if (! $featuredStatus) {
            Log::warning('Featured status not found for Paystack payment', [
                'featured_status_id' => $statusId,
                'transaction_id' => $transactionId,
            ]);

            return response('Featured status not found', 200);
        }

        // Activate the featured status
        $featuredStatus->update([
            'payment_reference' => $transactionId,
            'status' => WorkerFeaturedStatus::STATUS_ACTIVE,
        ]);

        Log::info('Featured status activated via Paystack', [
            'featured_status_id' => $featuredStatus->id,
            'worker_id' => $featuredStatus->worker_id,
            'tier' => $featuredStatus->tier,
        ]);

        return response('Featured status activated', 200);
    }

    /**
     * Handle shift payment charge completion.
     */
    protected function handleShiftPaymentCharge(string $transactionId, array $metadata, float $amount, ?string $reference): Response
    {
        $paymentId = $metadata['payment_id'] ?? null;

        if (! $paymentId) {
            Log::warning('Payment ID not found in Paystack metadata', [
                'transaction_id' => $transactionId,
                'metadata' => $metadata,
            ]);

            return response('Payment ID missing', 200);
        }

        $payment = ShiftPayment::find($paymentId);

        if (! $payment) {
            Log::warning('Shift payment not found for Paystack charge', [
                'payment_id' => $paymentId,
                'transaction_id' => $transactionId,
            ]);

            return response('Payment not found', 200);
        }

        DB::transaction(function () use ($payment, $transactionId, $reference) {
            $payment->update([
                'paystack_transaction_id' => $transactionId,
                'paystack_reference' => $reference,
                'status' => 'in_escrow',
                'escrow_held_at' => now(),
            ]);
        });

        Log::info('Shift payment captured via Paystack', [
            'payment_id' => $payment->id,
            'transaction_id' => $transactionId,
        ]);

        return response('Payment captured', 200);
    }

    /**
     * Convert webhook event type to handler method name.
     *
     * Converts "charge.success" to "handleChargeSuccess"
     */
    protected function getHandlerMethod(string $eventType): string
    {
        // Replace dots and underscores with spaces, then capitalize
        $normalized = str_replace(['.', '_'], ' ', strtolower($eventType));
        $parts = explode(' ', $normalized);
        $methodName = 'handle';

        foreach ($parts as $part) {
            $methodName .= ucfirst($part);
        }

        return $methodName;
    }
}
