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
 * PayPalWebhookController
 *
 * Handles incoming webhooks from PayPal payment processing.
 *
 * Webhooks handled:
 * - PAYMENT.CAPTURE.COMPLETED - Payment successfully captured
 * - PAYMENT.CAPTURE.DENIED - Payment capture denied
 * - PAYMENT.CAPTURE.REFUNDED - Payment refunded
 * - CHECKOUT.ORDER.APPROVED - Checkout order approved by customer
 * - PAYMENT.SALE.COMPLETED - Sale completed (legacy)
 * - CUSTOMER.DISPUTE.CREATED - Customer opened a dispute
 * - CUSTOMER.DISPUTE.RESOLVED - Dispute resolved
 *
 * Security:
 * - Webhook signature validation required
 * - Verifies webhook against PayPal API
 */
class PayPalWebhookController extends Controller
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Handle incoming PayPal webhook.
     *
     * POST /webhooks/paypal
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();

        Log::info('PayPal webhook received', [
            'event_type' => $request->header('PayPal-Event-Type'),
            'transmission_id' => $request->header('PayPal-Transmission-Id'),
        ]);

        // Verify webhook signature
        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('PayPal webhook signature verification failed');

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (! $event || ! isset($event['event_type'])) {
            Log::warning('PayPal webhook: Invalid payload structure');

            return response('Invalid payload', 400);
        }

        $eventType = $event['event_type'];
        $resource = $event['resource'] ?? [];
        $eventId = $event['id'] ?? $request->header('PayPal-Transmission-Id');

        // PRIORITY-0: Idempotency check
        $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
        $shouldProcess = $idempotencyService->shouldProcess('paypal', $eventId);

        if (! $shouldProcess['should_process']) {
            Log::info('PayPal webhook event already processed, skipping', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'message' => $shouldProcess['message'],
            ]);

            return response('Event already processed', 200);
        }

        // Record event for idempotency
        $webhookEvent = $idempotencyService->recordEvent('paypal', $eventId, $eventType, $payload);

        // Mark as processing
        if (! $idempotencyService->markProcessing($webhookEvent)) {
            return response('Event already processing', 200);
        }

        Log::info('PayPal webhook verified', [
            'event_type' => $eventType,
            'event_id' => $eventId,
        ]);

        // Route to appropriate handler
        $methodName = $this->getHandlerMethod($eventType);

        if (method_exists($this, $methodName)) {
            try {
                $result = $this->$methodName($event, $resource);

                // PRIORITY-0: Mark as processed successfully
                $idempotencyService->markProcessed($webhookEvent, ['success' => true]);

                return $result;
            } catch (\Exception $e) {
                Log::error('PayPal webhook handler error', [
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
        Log::info('Unhandled PayPal webhook event', [
            'event_type' => $eventType,
        ]);

        // PRIORITY-0: Mark as processed (unhandled but acknowledged)
        $idempotencyService->markProcessed($webhookEvent, ['unhandled' => true]);

        return response('Webhook received', 200);
    }

    /**
     * Verify PayPal webhook signature.
     *
     * PayPal uses a certificate-based signature verification.
     * Headers required:
     * - PAYPAL-TRANSMISSION-ID
     * - PAYPAL-TRANSMISSION-TIME
     * - PAYPAL-TRANSMISSION-SIG
     * - PAYPAL-CERT-URL
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $webhookId = config('paypal.webhook_id');

        // Skip verification in local/testing environments if webhook ID not set
        if (empty($webhookId) && app()->environment('local', 'testing')) {
            Log::warning('PayPal webhook verification skipped - no webhook ID configured');

            return true;
        }

        $transmissionId = $request->header('PayPal-Transmission-Id');
        $transmissionTime = $request->header('PayPal-Transmission-Time');
        $transmissionSig = $request->header('PayPal-Transmission-Sig');
        $certUrl = $request->header('PayPal-Cert-Url');

        // All headers are required
        if (! $transmissionId || ! $transmissionTime || ! $transmissionSig || ! $certUrl) {
            Log::warning('PayPal webhook missing required headers', [
                'has_transmission_id' => ! empty($transmissionId),
                'has_transmission_time' => ! empty($transmissionTime),
                'has_transmission_sig' => ! empty($transmissionSig),
                'has_cert_url' => ! empty($certUrl),
            ]);

            return false;
        }

        try {
            // Initialize PayPal client
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            // Verify webhook signature via PayPal API
            $response = $provider->verifyWebHook([
                'auth_algo' => $request->header('PayPal-Auth-Algo', 'SHA256withRSA'),
                'cert_url' => $certUrl,
                'transmission_id' => $transmissionId,
                'transmission_sig' => $transmissionSig,
                'transmission_time' => $transmissionTime,
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($request->getContent(), true),
            ]);

            $verificationStatus = $response['verification_status'] ?? null;

            if ($verificationStatus === 'SUCCESS') {
                return true;
            }

            Log::warning('PayPal webhook verification failed', [
                'status' => $verificationStatus,
                'response' => $response,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('PayPal webhook signature verification error', [
                'error' => $e->getMessage(),
            ]);

            // In production, fail closed (reject unverified webhooks)
            // In development, allow through with warning
            return app()->environment('local', 'testing');
        }
    }

    /**
     * Handle PAYMENT.CAPTURE.COMPLETED webhook.
     *
     * Fired when a payment capture is successfully completed.
     */
    protected function handlePaymentCaptureCompleted(array $event, array $resource): Response
    {
        $captureId = $resource['id'] ?? null;
        $amount = $resource['amount']['value'] ?? null;
        $currency = $resource['amount']['currency_code'] ?? 'USD';
        $customId = $resource['custom_id'] ?? null;

        Log::info('PayPal payment capture completed', [
            'capture_id' => $captureId,
            'amount' => $amount,
            'currency' => $currency,
            'custom_id' => $customId,
        ]);

        // Handle featured status payment
        if ($customId && str_starts_with($customId, 'featured_')) {
            return $this->handleFeaturedStatusPayment($captureId, $customId, $amount);
        }

        // Handle shift payment
        if ($customId && str_starts_with($customId, 'shift_payment_')) {
            return $this->handleShiftPaymentCapture($captureId, $customId, $amount);
        }

        return response('Capture processed', 200);
    }

    /**
     * Handle PAYMENT.CAPTURE.DENIED webhook.
     *
     * Fired when a payment capture is denied.
     */
    protected function handlePaymentCaptureDenied(array $event, array $resource): Response
    {
        $captureId = $resource['id'] ?? null;
        $customId = $resource['custom_id'] ?? null;

        Log::warning('PayPal payment capture denied', [
            'capture_id' => $captureId,
            'custom_id' => $customId,
        ]);

        // Update payment status if we can identify it
        if ($customId && str_starts_with($customId, 'shift_payment_')) {
            $paymentId = (int) str_replace('shift_payment_', '', $customId);
            $payment = ShiftPayment::find($paymentId);

            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'internal_notes' => 'PayPal capture denied: '.($resource['status_details']['reason'] ?? 'Unknown reason'),
                ]);
            }
        }

        return response('Denial recorded', 200);
    }

    /**
     * Handle PAYMENT.CAPTURE.REFUNDED webhook.
     *
     * Fired when a payment capture is refunded.
     */
    protected function handlePaymentCaptureRefunded(array $event, array $resource): Response
    {
        $captureId = $resource['id'] ?? null;
        $refundAmount = $resource['amount']['value'] ?? null;

        Log::info('PayPal payment capture refunded', [
            'capture_id' => $captureId,
            'refund_amount' => $refundAmount,
        ]);

        // Find payment by PayPal capture ID
        $payment = ShiftPayment::where('paypal_capture_id', $captureId)
            ->orWhere('paypal_transaction_id', $captureId)
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
     * Handle CHECKOUT.ORDER.APPROVED webhook.
     *
     * Fired when customer approves the checkout order.
     */
    protected function handleCheckoutOrderApproved(array $event, array $resource): Response
    {
        $orderId = $resource['id'] ?? null;
        $status = $resource['status'] ?? null;

        Log::info('PayPal checkout order approved', [
            'order_id' => $orderId,
            'status' => $status,
        ]);

        // Order approved - ready to capture
        // The actual capture should be triggered from the frontend callback
        // This webhook is mainly for logging/tracking purposes

        return response('Order approval noted', 200);
    }

    /**
     * Handle PAYMENT.SALE.COMPLETED webhook (legacy).
     *
     * Fired when a sale is completed (older PayPal integration).
     */
    protected function handlePaymentSaleCompleted(array $event, array $resource): Response
    {
        $saleId = $resource['id'] ?? null;
        $amount = $resource['amount']['total'] ?? null;
        $currency = $resource['amount']['currency'] ?? 'USD';

        Log::info('PayPal sale completed (legacy)', [
            'sale_id' => $saleId,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return response('Sale recorded', 200);
    }

    /**
     * Handle CUSTOMER.DISPUTE.CREATED webhook.
     *
     * Fired when a customer opens a dispute.
     */
    protected function handleCustomerDisputeCreated(array $event, array $resource): Response
    {
        $disputeId = $resource['dispute_id'] ?? null;
        $reason = $resource['reason'] ?? 'Unknown';
        $disputeAmount = $resource['dispute_amount']['value'] ?? null;

        // Get the disputed transaction(s)
        $transactions = $resource['disputed_transactions'] ?? [];

        Log::warning('PayPal customer dispute created', [
            'dispute_id' => $disputeId,
            'reason' => $reason,
            'amount' => $disputeAmount,
            'transactions' => $transactions,
        ]);

        // Find and mark related payments as disputed
        foreach ($transactions as $transaction) {
            $captureId = $transaction['seller_transaction_id'] ?? null;

            if ($captureId) {
                $payment = ShiftPayment::where('paypal_capture_id', $captureId)
                    ->orWhere('paypal_transaction_id', $captureId)
                    ->first();

                if ($payment) {
                    $payment->dispute("PayPal dispute: {$reason} (ID: {$disputeId})");

                    Log::info('Shift payment marked as disputed', [
                        'payment_id' => $payment->id,
                        'dispute_id' => $disputeId,
                    ]);
                }
            }
        }

        return response('Dispute recorded', 200);
    }

    /**
     * Handle CUSTOMER.DISPUTE.RESOLVED webhook.
     *
     * Fired when a dispute is resolved.
     */
    protected function handleCustomerDisputeResolved(array $event, array $resource): Response
    {
        $disputeId = $resource['dispute_id'] ?? null;
        $outcome = $resource['dispute_outcome']['outcome_code'] ?? 'Unknown';
        $transactions = $resource['disputed_transactions'] ?? [];

        Log::info('PayPal dispute resolved', [
            'dispute_id' => $disputeId,
            'outcome' => $outcome,
        ]);

        // Update related payments based on resolution
        foreach ($transactions as $transaction) {
            $captureId = $transaction['seller_transaction_id'] ?? null;

            if ($captureId) {
                $payment = ShiftPayment::where('paypal_capture_id', $captureId)
                    ->orWhere('paypal_transaction_id', $captureId)
                    ->first();

                if ($payment && $payment->isDisputed()) {
                    $payment->update([
                        'dispute_status' => 'resolved',
                        'dispute_resolution_notes' => "PayPal outcome: {$outcome}",
                    ]);

                    // If resolved in seller's favor, release the payment
                    if (in_array($outcome, ['RESOLVED_SELLER_FAVOUR', 'CANCELED_BY_BUYER'])) {
                        $payment->resolveDispute();
                    }

                    Log::info('Dispute resolution applied to payment', [
                        'payment_id' => $payment->id,
                        'outcome' => $outcome,
                    ]);
                }
            }
        }

        return response('Dispute resolution recorded', 200);
    }

    /**
     * Handle featured status payment completion.
     */
    protected function handleFeaturedStatusPayment(string $captureId, string $customId, ?string $amount): Response
    {
        // Extract featured status ID from custom_id (format: featured_STATUS_ID)
        $statusId = (int) str_replace('featured_', '', $customId);

        $featuredStatus = WorkerFeaturedStatus::find($statusId);

        if (! $featuredStatus) {
            Log::warning('Featured status not found for PayPal payment', [
                'custom_id' => $customId,
                'capture_id' => $captureId,
            ]);

            return response('Featured status not found', 200);
        }

        // Activate the featured status
        $featuredStatus->update([
            'payment_reference' => $captureId,
            'status' => WorkerFeaturedStatus::STATUS_ACTIVE,
        ]);

        Log::info('Featured status activated via PayPal', [
            'featured_status_id' => $featuredStatus->id,
            'worker_id' => $featuredStatus->worker_id,
            'tier' => $featuredStatus->tier,
        ]);

        return response('Featured status activated', 200);
    }

    /**
     * Handle shift payment capture completion.
     */
    protected function handleShiftPaymentCapture(string $captureId, string $customId, ?string $amount): Response
    {
        $paymentId = (int) str_replace('shift_payment_', '', $customId);

        $payment = ShiftPayment::find($paymentId);

        if (! $payment) {
            Log::warning('Shift payment not found for PayPal capture', [
                'custom_id' => $customId,
                'capture_id' => $captureId,
            ]);

            return response('Payment not found', 200);
        }

        DB::transaction(function () use ($payment, $captureId) {
            $payment->update([
                'paypal_capture_id' => $captureId,
                'status' => 'in_escrow',
                'escrow_held_at' => now(),
            ]);
        });

        Log::info('Shift payment captured via PayPal', [
            'payment_id' => $payment->id,
            'capture_id' => $captureId,
        ]);

        return response('Payment captured', 200);
    }

    /**
     * Convert webhook event type to handler method name.
     *
     * Converts "PAYMENT.CAPTURE.COMPLETED" to "handlePaymentCaptureCompleted"
     */
    protected function getHandlerMethod(string $eventType): string
    {
        // Convert dots to spaces, lowercase, then capitalize each word
        $parts = explode('.', strtolower($eventType));
        $methodName = 'handle';

        foreach ($parts as $part) {
            $methodName .= ucfirst($part);
        }

        return $methodName;
    }
}
