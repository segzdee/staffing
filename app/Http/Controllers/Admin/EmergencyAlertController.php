<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Services\EmergencyAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin Emergency Alert Controller
 * SAF-001: Emergency Contact System - Admin Management
 *
 * Handles admin-side emergency alert management including
 * viewing, acknowledging, resolving, and monitoring alerts.
 */
class EmergencyAlertController extends Controller
{
    protected EmergencyAlertService $alertService;

    public function __construct(EmergencyAlertService $alertService)
    {
        $this->middleware(['auth', 'admin']);
        $this->alertService = $alertService;
    }

    /**
     * Display emergency alerts dashboard.
     *
     * GET /admin/emergency-alerts
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $activeAlerts = $this->alertService->getAlertsNeedingResponse();
        $statistics = $this->alertService->getAlertStatistics(30);

        return view('admin.emergency-alerts.index', [
            'activeAlerts' => $activeAlerts,
            'statistics' => $statistics,
            'types' => EmergencyAlert::TYPES,
            'statuses' => EmergencyAlert::STATUSES,
        ]);
    }

    /**
     * Get active alerts for live dashboard (API).
     *
     * GET /api/admin/emergency-alerts/active
     */
    public function getActiveAlerts(): JsonResponse
    {
        $alerts = $this->alertService->getAlertsNeedingResponse();

        return response()->json([
            'success' => true,
            'data' => $alerts->map(fn ($alert) => $this->formatAlertForAdmin($alert)),
        ]);
    }

    /**
     * Get all alerts with filtering.
     *
     * GET /api/admin/emergency-alerts
     */
    public function list(Request $request): JsonResponse
    {
        $query = EmergencyAlert::with(['user', 'shift', 'venue', 'acknowledgedByUser', 'resolvedByUser']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date.' 23:59:59');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by shift
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter by venue
        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->venue_id);
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $alerts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $alerts->through(fn ($alert) => $this->formatAlertForAdmin($alert)),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    /**
     * Get single alert details.
     *
     * GET /api/admin/emergency-alerts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $alert = EmergencyAlert::with([
            'user.workerProfile',
            'shift',
            'venue',
            'acknowledgedByUser',
            'resolvedByUser',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatAlertForAdmin($alert, true),
        ]);
    }

    /**
     * Acknowledge an alert.
     *
     * POST /api/admin/emergency-alerts/{id}/acknowledge
     */
    public function acknowledge(int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);
        $admin = Auth::user();

        if ($alert->isAcknowledged()) {
            return response()->json([
                'success' => false,
                'message' => 'Alert has already been acknowledged.',
            ], 422);
        }

        $this->alertService->acknowledgeAlert($alert, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully.',
            'data' => $this->formatAlertForAdmin($alert->fresh()),
        ]);
    }

    /**
     * Resolve an alert.
     *
     * POST /api/admin/emergency-alerts/{id}/resolve
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);
        $admin = Auth::user();

        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:2000',
        ]);

        if ($alert->isResolved()) {
            return response()->json([
                'success' => false,
                'message' => 'Alert has already been resolved.',
            ], 422);
        }

        $this->alertService->resolveAlert($alert, $admin, $validated['resolution_notes']);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully.',
            'data' => $this->formatAlertForAdmin($alert->fresh()),
        ]);
    }

    /**
     * Mark alert as false alarm.
     *
     * POST /api/admin/emergency-alerts/{id}/false-alarm
     */
    public function markFalseAlarm(Request $request, int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);
        $admin = Auth::user();

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($alert->isResolved() || $alert->isFalseAlarm()) {
            return response()->json([
                'success' => false,
                'message' => 'Alert has already been closed.',
            ], 422);
        }

        $this->alertService->markAsFalseAlarm($alert, $admin, $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as false alarm.',
            'data' => $this->formatAlertForAdmin($alert->fresh()),
        ]);
    }

    /**
     * Notify emergency contacts for an alert.
     *
     * POST /api/admin/emergency-alerts/{id}/notify-contacts
     */
    public function notifyContacts(int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);

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
     * Mark emergency services as called.
     *
     * POST /api/admin/emergency-alerts/{id}/emergency-services-called
     */
    public function markEmergencyServicesCalled(int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);

        $this->alertService->markEmergencyServicesCalled($alert);

        return response()->json([
            'success' => true,
            'message' => 'Marked emergency services as called.',
            'data' => $this->formatAlertForAdmin($alert->fresh()),
        ]);
    }

    /**
     * Get alert statistics.
     *
     * GET /api/admin/emergency-alerts/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $days = min(max($days, 1), 365); // Between 1 and 365 days

        $statistics = $this->alertService->getAlertStatistics($days);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get location history for an alert.
     *
     * GET /api/admin/emergency-alerts/{id}/location-history
     */
    public function locationHistory(int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'alert_number' => $alert->alert_number,
                'current_location' => $alert->coordinates,
                'location_history' => $alert->location_history ?? [],
            ],
        ]);
    }

    /**
     * Format alert data for admin view.
     */
    protected function formatAlertForAdmin(EmergencyAlert $alert, bool $detailed = false): array
    {
        $data = [
            'id' => $alert->id,
            'alert_number' => $alert->alert_number,
            'type' => $alert->type,
            'type_label' => $alert->type_label,
            'status' => $alert->status,
            'status_label' => $alert->status_label,
            'is_high_priority' => $alert->isHighPriority(),
            'user' => [
                'id' => $alert->user->id,
                'name' => $alert->user->name,
                'email' => $alert->user->email,
                'phone' => $alert->user->workerProfile?->phone ?? null,
            ],
            'location' => $alert->coordinates,
            'location_address' => $alert->location_address,
            'message' => $alert->message,
            'shift' => $alert->shift ? [
                'id' => $alert->shift->id,
                'title' => $alert->shift->title,
            ] : null,
            'venue' => $alert->venue ? [
                'id' => $alert->venue->id,
                'name' => $alert->venue->name,
                'address' => $alert->venue->full_address,
            ] : null,
            'is_acknowledged' => $alert->isAcknowledged(),
            'acknowledged_at' => $alert->acknowledged_at?->toISOString(),
            'acknowledged_by' => $alert->acknowledgedByUser ? [
                'id' => $alert->acknowledgedByUser->id,
                'name' => $alert->acknowledgedByUser->name,
            ] : null,
            'is_resolved' => $alert->isResolved(),
            'resolved_at' => $alert->resolved_at?->toISOString(),
            'resolved_by' => $alert->resolvedByUser ? [
                'id' => $alert->resolvedByUser->id,
                'name' => $alert->resolvedByUser->name,
            ] : null,
            'resolution_notes' => $alert->resolution_notes,
            'emergency_services_called' => $alert->emergency_services_called,
            'emergency_contacts_notified' => $alert->emergency_contacts_notified,
            'response_time_minutes' => $alert->getResponseTimeMinutes(),
            'resolution_time_minutes' => $alert->getResolutionTimeMinutes(),
            'duration_minutes' => $alert->duration_minutes,
            'created_at' => $alert->created_at->toISOString(),
            'updated_at' => $alert->updated_at->toISOString(),
        ];

        if ($detailed) {
            $data['location_history'] = $alert->location_history;
        }

        return $data;
    }
}
