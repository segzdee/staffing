<?php

namespace App\Services;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BIZ-012: Integration APIs - Outbound Webhook Service
 *
 * Handles registration and delivery of outbound webhooks to external systems.
 * Supports event-based notifications for shifts, payments, workers, and ratings.
 */
class WebhookService
{
    /**
     * Default timeout for webhook requests in seconds.
     */
    protected int $timeout = 30;

    /**
     * Number of retry attempts for failed webhooks.
     */
    protected int $maxRetries = 3;

    /**
     * Register a new webhook.
     */
    public function registerWebhook(User $business, string $url, array $events): Webhook
    {
        if (! $business->isBusiness()) {
            throw new \InvalidArgumentException('User must be a business account');
        }

        // Validate URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid webhook URL');
        }

        // Validate events
        $availableEvents = array_keys(Webhook::getAvailableEvents());
        foreach ($events as $event) {
            if (! in_array($event, $availableEvents)) {
                throw new \InvalidArgumentException("Invalid event: {$event}");
            }
        }

        // Generate a secret for signature verification
        $secret = Str::random(64);

        $webhook = Webhook::create([
            'business_id' => $business->id,
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'is_active' => true,
        ]);

        Log::info('Webhook registered', [
            'webhook_id' => $webhook->id,
            'business_id' => $business->id,
            'url' => $url,
            'events' => $events,
        ]);

        return $webhook;
    }

    /**
     * Update webhook configuration.
     */
    public function updateWebhook(Webhook $webhook, array $data): Webhook
    {
        $updateData = [];

        if (isset($data['url'])) {
            if (! filter_var($data['url'], FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Invalid webhook URL');
            }
            $updateData['url'] = $data['url'];
        }

        if (isset($data['events'])) {
            $availableEvents = array_keys(Webhook::getAvailableEvents());
            foreach ($data['events'] as $event) {
                if (! in_array($event, $availableEvents)) {
                    throw new \InvalidArgumentException("Invalid event: {$event}");
                }
            }
            $updateData['events'] = $data['events'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        $webhook->update($updateData);

        return $webhook->fresh();
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(Webhook $webhook): void
    {
        $webhook->delete();

        Log::info('Webhook deleted', [
            'webhook_id' => $webhook->id,
            'business_id' => $webhook->business_id,
        ]);
    }

    /**
     * Regenerate webhook secret.
     */
    public function regenerateSecret(Webhook $webhook): string
    {
        $newSecret = Str::random(64);
        $webhook->update(['secret' => $newSecret]);

        return $newSecret;
    }

    /**
     * Trigger a single webhook.
     */
    public function triggerWebhook(string $event, array $payload): void
    {
        // Get all active webhooks subscribed to this event
        $webhooks = Webhook::active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $payload);
        }
    }

    /**
     * Dispatch webhooks for a model event.
     */
    public function dispatchWebhooks(string $event, Model $model): void
    {
        $payload = $this->buildPayloadFromModel($event, $model);

        // Get the business ID from the model
        $businessId = $this->getBusinessIdFromModel($model);

        if (! $businessId) {
            Log::warning('Could not determine business ID for webhook dispatch', [
                'event' => $event,
                'model' => get_class($model),
                'model_id' => $model->id,
            ]);

            return;
        }

        // Get active webhooks for this business and event
        $webhooks = Webhook::where('business_id', $businessId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->deliverWebhook($webhook, $event, $payload);
        }
    }

    /**
     * Sign a payload with the webhook secret.
     */
    public function signPayload(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload);

        return hash_hmac('sha256', $payloadJson, $secret);
    }

    /**
     * Retry failed webhooks.
     */
    public function retryFailedWebhooks(): int
    {
        $retried = 0;

        // Get webhooks that have failed but are still active
        $webhooks = Webhook::where('is_active', true)
            ->where('failure_count', '>', 0)
            ->where('failure_count', '<', Webhook::MAX_FAILURES)
            ->get();

        foreach ($webhooks as $webhook) {
            try {
                // Send a test ping
                $success = $this->sendTestPing($webhook);

                if ($success) {
                    $webhook->update([
                        'failure_count' => 0,
                        'last_error' => null,
                    ]);
                    $retried++;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retry webhook', [
                    'webhook_id' => $webhook->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $retried;
    }

    /**
     * Get webhooks for a business.
     */
    public function getBusinessWebhooks(User $business): \Illuminate\Database\Eloquent\Collection
    {
        return Webhook::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Test a webhook by sending a ping.
     */
    public function testWebhook(Webhook $webhook): bool
    {
        return $this->sendTestPing($webhook);
    }

    /**
     * Deliver a webhook to its endpoint.
     */
    protected function deliverWebhook(Webhook $webhook, string $event, array $payload): void
    {
        $fullPayload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $webhook->id,
            'data' => $payload,
        ];

        $signature = $this->signPayload($fullPayload, $webhook->secret ?? '');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'X-Webhook-ID' => (string) $webhook->id,
                    'X-Webhook-Timestamp' => now()->timestamp,
                    'User-Agent' => 'OvertimeStaff-Webhook/1.0',
                ])
                ->post($webhook->url, $fullPayload);

            if ($response->successful()) {
                $webhook->recordSuccess();

                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status' => $response->status(),
                ]);
            } else {
                $error = "HTTP {$response->status()}: {$response->body()}";
                $webhook->recordFailure($error);

                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status' => $response->status(),
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            $webhook->recordFailure($e->getMessage());

            Log::error('Webhook delivery exception', [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send a test ping to verify webhook endpoint.
     */
    protected function sendTestPing(Webhook $webhook): bool
    {
        $payload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'webhook_id' => $webhook->id,
            'data' => [
                'message' => 'This is a test ping from OvertimeStaff',
            ],
        ];

        $signature = $this->signPayload($payload, $webhook->secret ?? '');

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'webhook.test',
                    'X-Webhook-ID' => (string) $webhook->id,
                    'User-Agent' => 'OvertimeStaff-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Build payload from a model based on event type.
     */
    protected function buildPayloadFromModel(string $event, Model $model): array
    {
        $modelClass = get_class($model);

        return match (true) {
            str_contains($event, 'shift.') => $this->buildShiftPayload($model),
            str_contains($event, 'application.') => $this->buildApplicationPayload($model),
            str_contains($event, 'worker.') => $this->buildWorkerPayload($model),
            str_contains($event, 'payment.') => $this->buildPaymentPayload($model),
            str_contains($event, 'rating.') => $this->buildRatingPayload($model),
            default => $this->buildGenericPayload($model),
        };
    }

    /**
     * Build shift payload.
     */
    protected function buildShiftPayload(Model $shift): array
    {
        return [
            'shift_id' => $shift->id,
            'title' => $shift->title ?? null,
            'status' => $shift->status ?? null,
            'shift_date' => $shift->shift_date?->toDateString(),
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'location' => [
                'address' => $shift->location_address ?? null,
                'city' => $shift->location_city ?? null,
                'state' => $shift->location_state ?? null,
                'country' => $shift->location_country ?? null,
            ],
            'required_workers' => $shift->required_workers ?? 0,
            'filled_workers' => $shift->filled_workers ?? 0,
            'base_rate' => $shift->base_rate ?? null,
        ];
    }

    /**
     * Build application payload.
     */
    protected function buildApplicationPayload(Model $application): array
    {
        return [
            'application_id' => $application->id,
            'shift_id' => $application->shift_id,
            'worker_id' => $application->worker_id,
            'status' => $application->status ?? null,
            'applied_at' => $application->applied_at?->toIso8601String(),
        ];
    }

    /**
     * Build worker/assignment payload.
     */
    protected function buildWorkerPayload(Model $assignment): array
    {
        return [
            'assignment_id' => $assignment->id,
            'shift_id' => $assignment->shift_id,
            'worker_id' => $assignment->worker_id,
            'status' => $assignment->status ?? null,
            'check_in_time' => $assignment->check_in_time?->toIso8601String(),
            'check_out_time' => $assignment->check_out_time?->toIso8601String(),
            'hours_worked' => $assignment->hours_worked ?? null,
        ];
    }

    /**
     * Build payment payload.
     */
    protected function buildPaymentPayload(Model $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'shift_assignment_id' => $payment->shift_assignment_id ?? null,
            'worker_id' => $payment->worker_id ?? null,
            'business_id' => $payment->business_id ?? null,
            'amount' => $payment->amount ?? null,
            'status' => $payment->status ?? null,
        ];
    }

    /**
     * Build rating payload.
     */
    protected function buildRatingPayload(Model $rating): array
    {
        return [
            'rating_id' => $rating->id,
            'shift_assignment_id' => $rating->shift_assignment_id ?? null,
            'rater_id' => $rating->rater_id ?? null,
            'rated_id' => $rating->rated_id ?? null,
            'rating' => $rating->rating ?? null,
            'comment' => $rating->comment ?? null,
        ];
    }

    /**
     * Build generic payload for unknown models.
     */
    protected function buildGenericPayload(Model $model): array
    {
        return [
            'id' => $model->id,
            'type' => get_class($model),
            'attributes' => $model->toArray(),
        ];
    }

    /**
     * Get business ID from a model.
     */
    protected function getBusinessIdFromModel(Model $model): ?int
    {
        // Direct business_id field
        if (isset($model->business_id)) {
            return $model->business_id;
        }

        // Through shift relationship
        if (method_exists($model, 'shift') && $model->shift) {
            return $model->shift->business_id;
        }

        // Through shift_assignment relationship
        if (method_exists($model, 'shiftAssignment') && $model->shiftAssignment) {
            return $model->shiftAssignment->shift?->business_id;
        }

        return null;
    }

    /**
     * Verify incoming webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
