<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\CalendarExportService;
use App\Services\IntegrationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * BIZ-012: Integration APIs - Integration Management Controller
 *
 * Handles external integration management for businesses:
 * - Connect/disconnect integrations
 * - Sync data with external systems
 * - View sync history
 * - Manage calendar feeds
 */
class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationService $integrationService,
        protected CalendarExportService $calendarExportService
    ) {}

    /**
     * Display the integrations management page.
     */
    public function index()
    {
        $user = Auth::user();

        $integrations = $this->integrationService->getBusinessIntegrations($user);
        $availableProviders = $this->integrationService->getAvailableProviders();

        // Get providers that are not yet connected
        $connectedProviders = $integrations->pluck('provider')->toArray();
        $unconnectedProviders = array_diff_key($availableProviders, array_flip($connectedProviders));

        // Get calendar URL
        $calendarUrl = $this->calendarExportService->getCalendarUrl($user);

        return view('business.integrations.index', compact(
            'integrations',
            'availableProviders',
            'unconnectedProviders',
            'calendarUrl'
        ));
    }

    /**
     * Get available integration providers (API).
     */
    public function getProviders()
    {
        $providers = $this->integrationService->getAvailableProviders();

        return response()->json([
            'success' => true,
            'data' => $providers,
        ]);
    }

    /**
     * Get integrations for the current business (API).
     */
    public function getIntegrations()
    {
        $user = Auth::user();
        $integrations = $this->integrationService->getBusinessIntegrations($user);

        return response()->json([
            'success' => true,
            'data' => $integrations->map(function ($integration) {
                return [
                    'id' => $integration->id,
                    'provider' => $integration->provider,
                    'name' => $integration->name,
                    'type' => $integration->type,
                    'is_active' => $integration->is_active,
                    'connected_at' => $integration->connected_at?->toIso8601String(),
                    'last_sync_at' => $integration->last_sync_at?->toIso8601String(),
                    'sync_errors' => $integration->sync_errors,
                    'needs_reauth' => $integration->needsReauth(),
                    'display_name' => $integration->display_name,
                ];
            }),
        ]);
    }

    /**
     * Connect a new integration.
     */
    public function connect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string',
            'credentials' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            $integration = $this->integrationService->connect(
                $user,
                $request->input('provider'),
                $request->input('credentials')
            );

            return response()->json([
                'success' => true,
                'message' => 'Integration connected successfully',
                'data' => [
                    'id' => $integration->id,
                    'provider' => $integration->provider,
                    'name' => $integration->name,
                    'connected_at' => $integration->connected_at?->toIso8601String(),
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
                'message' => 'Failed to connect integration',
            ], 500);
        }
    }

    /**
     * Disconnect an integration.
     */
    public function disconnect(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->integrationService->disconnect($integration);

            return response()->json([
                'success' => true,
                'message' => 'Integration disconnected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect integration',
            ], 500);
        }
    }

    /**
     * Test integration connection.
     */
    public function testConnection(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $isConnected = $this->integrationService->testConnection($integration);

        return response()->json([
            'success' => true,
            'data' => [
                'is_connected' => $isConnected,
                'message' => $isConnected ? 'Connection successful' : 'Connection failed',
            ],
        ]);
    }

    /**
     * Sync shifts from/to integration.
     */
    public function syncShifts(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active',
            ], 400);
        }

        try {
            $sync = $this->integrationService->syncShifts($integration);

            return response()->json([
                'success' => true,
                'message' => 'Shift sync completed',
                'data' => [
                    'sync_id' => $sync->id,
                    'status' => $sync->status,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_updated' => $sync->records_updated,
                    'records_failed' => $sync->records_failed,
                    'duration' => $sync->duration,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync timesheets.
     */
    public function syncTimesheets(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active',
            ], 400);
        }

        try {
            $sync = $this->integrationService->syncTimesheets($integration);

            return response()->json([
                'success' => true,
                'message' => 'Timesheet sync completed',
                'data' => [
                    'sync_id' => $sync->id,
                    'status' => $sync->status,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_failed' => $sync->records_failed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import workers from integration.
     */
    public function importWorkers(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active',
            ], 400);
        }

        try {
            $sync = $this->integrationService->importWorkers($integration);

            return response()->json([
                'success' => true,
                'message' => 'Worker import completed',
                'data' => [
                    'sync_id' => $sync->id,
                    'status' => $sync->status,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_updated' => $sync->records_updated,
                    'records_failed' => $sync->records_failed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export payroll data.
     */
    public function exportPayroll(Request $request, Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $integration->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Integration is not active',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'period' => 'required|date_format:Y-m',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $period = Carbon::createFromFormat('Y-m', $request->input('period'));
            $sync = $this->integrationService->exportPayroll($integration, $period);

            return response()->json([
                'success' => true,
                'message' => 'Payroll export completed',
                'data' => [
                    'sync_id' => $sync->id,
                    'status' => $sync->status,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_failed' => $sync->records_failed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync history for an integration.
     */
    public function syncHistory(Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $history = $this->integrationService->getSyncHistory($integration, 20);

        return response()->json([
            'success' => true,
            'data' => $history->map(function ($sync) {
                return [
                    'id' => $sync->id,
                    'direction' => $sync->direction,
                    'entity_type' => $sync->entity_type,
                    'status' => $sync->status,
                    'status_label' => $sync->status_label,
                    'records_processed' => $sync->records_processed,
                    'records_created' => $sync->records_created,
                    'records_updated' => $sync->records_updated,
                    'records_failed' => $sync->records_failed,
                    'success_rate' => $sync->success_rate,
                    'duration' => $sync->duration,
                    'errors' => $sync->errors,
                    'started_at' => $sync->started_at?->toIso8601String(),
                    'completed_at' => $sync->completed_at?->toIso8601String(),
                    'created_at' => $sync->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Update integration settings.
     */
    public function updateSettings(Request $request, Integration $integration)
    {
        $user = Auth::user();

        // Verify ownership
        if ($integration->business_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.auto_sync' => 'sometimes|boolean',
            'settings.sync_interval' => 'sometimes|integer|min:15|max:1440',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentSettings = $integration->settings ?? [];
        $newSettings = array_merge($currentSettings, $request->input('settings'));

        $integration->update(['settings' => $newSettings]);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => [
                'settings' => $integration->fresh()->settings,
            ],
        ]);
    }

    /**
     * Get calendar subscription URL.
     */
    public function getCalendarUrl()
    {
        $user = Auth::user();
        $calendarUrl = $this->calendarExportService->getCalendarUrl($user);

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $calendarUrl,
                'instructions' => [
                    'google' => 'Add this URL in Google Calendar via "Other calendars" > "From URL"',
                    'outlook' => 'In Outlook, use "Add calendar" > "Subscribe from web"',
                    'apple' => 'In Calendar app, use File > New Calendar Subscription',
                ],
            ],
        ]);
    }

    /**
     * Regenerate calendar URL (for security).
     */
    public function regenerateCalendarUrl()
    {
        $user = Auth::user();
        $token = $this->calendarExportService->regenerateCalendarToken($user);
        $calendarUrl = $this->calendarExportService->getCalendarUrl($user);

        return response()->json([
            'success' => true,
            'message' => 'Calendar URL regenerated. You will need to update your calendar subscriptions.',
            'data' => [
                'url' => $calendarUrl,
            ],
        ]);
    }
}
