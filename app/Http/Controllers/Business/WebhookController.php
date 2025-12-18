<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * BIZ-012: Integration APIs - Webhook Management Controller
 *
 * Handles outbound webhook management for businesses:
 * - Register/update/delete webhooks
 * - View webhook events
 * - Test webhook endpoints
 * - Regenerate secrets
 */
class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService
    ) {}

    /**
     * Display the webhooks management page.
     */
    public function index()
    {
        $user = Auth::user();

        $webhooks = $this->webhookService->getBusinessWebhooks($user);
        $availableEvents = Webhook::getEventsGroupedByCategory();

        return view('business.webhooks.index', compact('webhooks', 'availableEvents'));
    }

    /**
     * Get webhooks for the current business (API).
     */
    public function getWebhooks()
    {
        $user = Auth::user();
        $webhooks = $this->webhookService->getBusinessWebhooks($user);

        return response()->json([
            'success' => true,
            'data' => $webhooks->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                    'failure_count' => $webhook->failure_count,
                    'status_label' => $webhook->status_label,
                    'status_color' => $webhook->status_color,
                    'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                    'last_success_at' => $webhook->last_success_at?->toIso8601String(),
                    'last_failure_at' => $webhook->last_failure_at?->toIso8601String(),
                    'last_error' => $webhook->last_error,
                    'created_at' => $webhook->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Get available webhook events.
     */
    public function getEvents()
    {
        $events = Webhook::getAvailableEvents();
        $grouped = Webhook::getEventsGroupedByCategory();

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events,
                'grouped' => $grouped,
            ],
        ]);
    }

    /**
     * Register a new webhook.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            $webhook = $this->webhookService->registerWebhook(
                $user,
                $request->input('url'),
                $request->input('events')
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook registered successfully',
                'data' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'secret' => $webhook->secret, // Return secret once on creation
                    'is_active' => $webhook->is_active,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register webhook',
            ], 500);
        }
    }

    /**
     * Get a specific webhook.
     */
    public function show(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $webhook->id,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
                'status_label' => $webhook->status_label,
                'status_color' => $webhook->status_color,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'last_success_at' => $webhook->last_success_at?->toIso8601String(),
                'last_failure_at' => $webhook->last_failure_at?->toIso8601String(),
                'last_error' => $webhook->last_error,
                'created_at' => $webhook->created_at?->toIso8601String(),
                'updated_at' => $webhook->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update a webhook.
     */
    public function update(Request $request, Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'url' => 'sometimes|url|max:500',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $webhook = $this->webhookService->updateWebhook($webhook, $request->only(['url', 'events', 'is_active']));

            return response()->json([
                'success' => true,
                'message' => 'Webhook updated successfully',
                'data' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook',
            ], 500);
        }
    }

    /**
     * Delete a webhook.
     */
    public function destroy(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->webhookService->deleteWebhook($webhook);

            return response()->json([
                'success' => true,
                'message' => 'Webhook deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete webhook',
            ], 500);
        }
    }

    /**
     * Test a webhook endpoint.
     */
    public function test(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $isSuccessful = $this->webhookService->testWebhook($webhook);

        return response()->json([
            'success' => true,
            'data' => [
                'test_passed' => $isSuccessful,
                'message' => $isSuccessful
                    ? 'Webhook endpoint is responding correctly'
                    : 'Webhook endpoint failed to respond',
            ],
        ]);
    }

    /**
     * Regenerate webhook secret.
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $newSecret = $this->webhookService->regenerateSecret($webhook);

            return response()->json([
                'success' => true,
                'message' => 'Webhook secret regenerated. Please update your endpoint.',
                'data' => [
                    'secret' => $newSecret,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate secret',
            ], 500);
        }
    }

    /**
     * Reactivate a disabled webhook.
     */
    public function reactivate(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $webhook->reactivate();

        return response()->json([
            'success' => true,
            'message' => 'Webhook reactivated successfully',
            'data' => [
                'is_active' => $webhook->is_active,
                'failure_count' => $webhook->failure_count,
            ],
        ]);
    }

    /**
     * Toggle webhook active status.
     */
    public function toggle(Webhook $webhook)
    {
        $user = Auth::user();

        // Verify ownership
        if ($webhook->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json([
            'success' => true,
            'message' => $webhook->is_active ? 'Webhook enabled' : 'Webhook disabled',
            'data' => [
                'is_active' => $webhook->is_active,
            ],
        ]);
    }
}
