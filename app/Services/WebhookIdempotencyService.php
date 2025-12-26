<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Idempotency Service
 *
 * PRIORITY-0: Prevents duplicate webhook processing
 * Ensures idempotency for payment webhooks
 *
 * SEC-004: Webhook Idempotency
 */
class WebhookIdempotencyService
{
    /**
     * Check if webhook event has already been processed.
     *
     * @param  string  $provider  Payment provider (stripe, paypal, etc.)
     * @param  string  $eventId  Provider's event ID
     * @return WebhookEvent|null Existing event or null
     */
    public function getExistingEvent(string $provider, string $eventId): ?WebhookEvent
    {
        return WebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();
    }

    /**
     * Record webhook event and check for duplicates.
     *
     * @param  string  $provider  Payment provider
     * @param  string  $eventId  Provider's event ID
     * @param  string  $eventType  Event type (payment_intent.succeeded, etc.)
     * @param  array|string  $payload  Webhook payload
     * @return WebhookEvent The webhook event record
     */
    public function recordEvent(string $provider, string $eventId, string $eventType, $payload): WebhookEvent
    {
        return DB::transaction(function () use ($provider, $eventId, $eventType, $payload) {
            // Check if already exists (race condition protection)
            $existing = $this->getExistingEvent($provider, $eventId);
            if ($existing) {
                Log::info('Webhook event already recorded', [
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'status' => $existing->status,
                ]);

                return $existing;
            }

            // Create new event record
            return WebhookEvent::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => is_string($payload) ? $payload : json_encode($payload),
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Mark webhook event as processing.
     *
     * @param  WebhookEvent  $event  The webhook event
     * @return bool Success status
     */
    public function markProcessing(WebhookEvent $event): bool
    {
        // Use optimistic locking to prevent race conditions
        $updated = WebhookEvent::where('id', $event->id)
            ->where('status', 'pending')
            ->update(['status' => 'processing']);

        if ($updated) {
            $event->refresh();

            return true;
        }

        // Already processing or processed
        Log::warning('Webhook event already processing or processed', [
            'event_id' => $event->id,
            'current_status' => $event->status,
        ]);

        return false;
    }

    /**
     * Mark webhook event as processed successfully.
     *
     * @param  WebhookEvent  $event  The webhook event
     * @param  array  $result  Processing result
     * @return bool Success status
     */
    public function markProcessed(WebhookEvent $event, array $result = []): bool
    {
        $event->update([
            'status' => 'processed',
            'processing_result' => json_encode($result),
            'processed_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark webhook event as failed.
     *
     * @param  WebhookEvent  $event  The webhook event
     * @param  string  $errorMessage  Error message
     * @param  bool  $retryable  Whether the event can be retried
     * @return bool Success status
     */
    public function markFailed(WebhookEvent $event, string $errorMessage, bool $retryable = true): bool
    {
        $event->update([
            'status' => $retryable ? 'pending' : 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $event->retry_count + 1,
        ]);

        return true;
    }

    /**
     * Check if event should be processed (idempotency check).
     *
     * @param  string  $provider  Payment provider
     * @param  string  $eventId  Provider's event ID
     * @return array{should_process: bool, existing_event: WebhookEvent|null, message: string}
     */
    public function shouldProcess(string $provider, string $eventId): array
    {
        $existing = $this->getExistingEvent($provider, $eventId);

        if (! $existing) {
            return [
                'should_process' => true,
                'existing_event' => null,
                'message' => 'New event, should process',
            ];
        }

        if ($existing->status === 'processed') {
            return [
                'should_process' => false,
                'existing_event' => $existing,
                'message' => 'Event already processed',
            ];
        }

        if ($existing->status === 'processing') {
            return [
                'should_process' => false,
                'existing_event' => $existing,
                'message' => 'Event currently processing',
            ];
        }

        // Pending or failed - can retry
        return [
            'should_process' => true,
            'existing_event' => $existing,
            'message' => 'Event pending or failed, can retry',
        ];
    }
}
