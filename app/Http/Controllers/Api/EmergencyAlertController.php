<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Services\EmergencyAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Emergency Alert Controller
 * SAF-001: Emergency Contact System - SOS Functionality
 *
 * Handles SOS alert triggering, location updates, and user alert management.
 * This is the API controller for the SOS feature.
 */
class EmergencyAlertController extends Controller
{
    protected EmergencyAlertService $alertService;

    public function __construct(EmergencyAlertService $alertService)
    {
        $this->middleware('auth:sanctum');
        $this->alertService = $alertService;
    }

    /**
     * Trigger an SOS alert - ONE TAP EMERGENCY.
     *
     * POST /api/sos/trigger
     *
     * This endpoint is designed to work with minimal data for true emergencies.
     * Location data is optional but highly recommended.
     */
    public function trigger(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check if user already has an active alert
        if ($this->alertService->userHasActiveAlert($user)) {
            $activeAlert = $this->alertService->getUserActiveAlert($user);

            return response()->json([
                'success' => false,
                'message' => 'You already have an active emergency alert.',
                'data' => [
                    'alert_number' => $activeAlert->alert_number,
                    'status' => $activeAlert->status,
                    'created_at' => $activeAlert->created_at->toISOString(),
                ],
            ], 409);
        }

        // Validate optional data
        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:500',
            'accuracy' => 'nullable|integer|min:0',
            'message' => 'nullable|string|max:1000',
            'type' => ['nullable', Rule::in(array_keys(EmergencyAlert::TYPES))],
            'shift_id' => 'nullable|integer|exists:shifts,id',
            'venue_id' => 'nullable|integer|exists:venues,id',
        ]);

        // Trigger the SOS alert
        $alert = $this->alertService->triggerSOS($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Emergency SOS alert triggered. Help is on the way.',
            'data' => [
                'alert_number' => $alert->alert_number,
                'status' => $alert->status,
                'type' => $alert->type,
                'type_label' => $alert->type_label,
                'created_at' => $alert->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Update location for an active alert.
     *
     * POST /api/sos/location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|integer|min:0',
        ]);

        $alert = $this->alertService->getUserActiveAlert($user);

        if (! $alert) {
            return response()->json([
                'success' => false,
                'message' => 'No active emergency alert found.',
            ], 404);
        }

        $this->alertService->updateLocation(
            $alert,
            $validated['latitude'],
            $validated['longitude'],
            $validated['accuracy'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
        ]);
    }

    /**
     * Get status of active alert.
     *
     * GET /api/sos/status
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();
        $alert = $this->alertService->getUserActiveAlert($user);

        if (! $alert) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_active_alert' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_active_alert' => true,
                'alert' => [
                    'alert_number' => $alert->alert_number,
                    'status' => $alert->status,
                    'status_label' => $alert->status_label,
                    'type' => $alert->type,
                    'type_label' => $alert->type_label,
                    'is_acknowledged' => $alert->isAcknowledged(),
                    'acknowledged_at' => $alert->acknowledged_at?->toISOString(),
                    'duration_minutes' => $alert->duration_minutes,
                    'created_at' => $alert->created_at->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Cancel an active alert (user-initiated).
     *
     * POST /api/sos/cancel
     */
    public function cancel(): JsonResponse
    {
        $user = Auth::user();
        $alert = $this->alertService->getUserActiveAlert($user);

        if (! $alert) {
            return response()->json([
                'success' => false,
                'message' => 'No active emergency alert found.',
            ], 404);
        }

        $cancelled = $this->alertService->cancelAlert($alert, $user);

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to cancel this alert.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emergency alert cancelled.',
        ]);
    }

    /**
     * Request notification to emergency contacts.
     *
     * POST /api/sos/notify-contacts
     */
    public function notifyContacts(): JsonResponse
    {
        $user = Auth::user();
        $alert = $this->alertService->getUserActiveAlert($user);

        if (! $alert) {
            return response()->json([
                'success' => false,
                'message' => 'No active emergency alert found.',
            ], 404);
        }

        if ($alert->emergency_contacts_notified) {
            return response()->json([
                'success' => false,
                'message' => 'Emergency contacts have already been notified.',
            ], 422);
        }

        $this->alertService->notifyEmergencyContacts($alert);

        return response()->json([
            'success' => true,
            'message' => 'Emergency contacts have been notified.',
        ]);
    }

    /**
     * Get user's alert history.
     *
     * GET /api/sos/history
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = min($request->input('limit', 10), 50);

        $alerts = $this->alertService->getUserAlertHistory($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $alerts->map(fn ($alert) => [
                'alert_number' => $alert->alert_number,
                'type' => $alert->type,
                'type_label' => $alert->type_label,
                'status' => $alert->status,
                'status_label' => $alert->status_label,
                'shift' => $alert->shift ? [
                    'id' => $alert->shift->id,
                    'title' => $alert->shift->title,
                ] : null,
                'venue' => $alert->venue ? [
                    'id' => $alert->venue->id,
                    'name' => $alert->venue->name,
                ] : null,
                'created_at' => $alert->created_at->toISOString(),
                'resolved_at' => $alert->resolved_at?->toISOString(),
                'duration_minutes' => $alert->duration_minutes,
            ]),
        ]);
    }
}
