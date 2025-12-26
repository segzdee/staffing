<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * FIN-011: Stripe Subscription Webhook Controller
 *
 * Handles incoming Stripe webhooks for subscription events:
 * - invoice.paid
 * - invoice.payment_failed
 * - customer.subscription.updated
 * - customer.subscription.deleted
 * - customer.subscription.trial_will_end
 */
class StripeSubscriptionWebhookController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle incoming Stripe webhook.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // PRIORITY-0: Verify webhook signature (middleware should handle this, but double-check)
        if ($webhookSecret) {
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $signature,
                    $webhookSecret
                );
            } catch (SignatureVerificationException $e) {
                Log::warning('Stripe webhook signature verification failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Invalid signature'], 400);
            } catch (\UnexpectedValueException $e) {
                Log::warning('Stripe webhook payload invalid', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json(['error' => 'Invalid payload'], 400);
            }
        } else {
            // No webhook secret configured, parse payload directly (development only)
            $event = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON'], 400);
            }
        }

        // Convert to array if Stripe Event object
        $eventData = $event instanceof \Stripe\Event ? $event->toArray() : $event;

        $eventType = $eventData['type'] ?? null;
        $eventId = $eventData['id'] ?? null;

        // PRIORITY-0: Idempotency check
        $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
        $shouldProcess = $idempotencyService->shouldProcess('stripe', $eventId);

        if (! $shouldProcess['should_process']) {
            Log::info('Stripe webhook event already processed, skipping', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'message' => $shouldProcess['message'],
            ]);

            return response()->json([
                'received' => true,
                'processed' => false,
                'message' => 'Event already processed',
            ]);
        }

        // Record event for idempotency
        $webhookEvent = $idempotencyService->recordEvent('stripe', $eventId, $eventType, $eventData);

        // Mark as processing
        if (! $idempotencyService->markProcessing($webhookEvent)) {
            return response()->json([
                'received' => true,
                'processed' => false,
                'message' => 'Event already processing',
            ]);
        }

        Log::info('Stripe subscription webhook received', [
            'type' => $eventType,
            'id' => $eventId,
        ]);

        // Only handle subscription-related events
        $subscriptionEvents = [
            'invoice.paid',
            'invoice.payment_failed',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.created',
            'customer.subscription.trial_will_end',
            'customer.subscription.paused',
            'customer.subscription.resumed',
            'invoice.created',
            'invoice.finalized',
            'invoice.upcoming',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
        ];

        if (! in_array($eventType, $subscriptionEvents)) {
            // Not a subscription event, return success but don't process
            return response()->json([
                'received' => true,
                'message' => 'Event type not handled by subscription webhook',
            ]);
        }

        try {
            $result = $this->subscriptionService->processWebhook($eventData);

            if (! $result['success']) {
                Log::error('Webhook processing failed', [
                    'type' => $eventType,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // PRIORITY-0: Mark as failed (retryable)
                $idempotencyService->markFailed($webhookEvent, $result['error'] ?? 'Processing failed', true);

                // Still return 200 to prevent Stripe retries for business logic errors
                return response()->json([
                    'received' => true,
                    'processed' => false,
                    'message' => $result['error'] ?? 'Processing failed',
                ]);
            }

            // PRIORITY-0: Mark as processed successfully
            $idempotencyService->markProcessed($webhookEvent, $result);

            return response()->json([
                'received' => true,
                'processed' => true,
                'message' => $result['message'] ?? 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // PRIORITY-0: Mark as failed
            $idempotencyService->markFailed($webhookEvent, $e->getMessage(), true);

            // Return 500 to trigger Stripe retry for unexpected errors
            return response()->json([
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
