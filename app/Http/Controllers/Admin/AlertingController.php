<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertConfiguration;
use App\Models\AlertHistory;
use App\Models\AlertIntegration;
use App\Services\AlertingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * ADM-004: Alerting Configuration Controller
 *
 * Manages external alerting integrations (Slack, PagerDuty) and alert configurations.
 */
class AlertingController extends Controller
{
    protected AlertingService $alertingService;

    public function __construct(AlertingService $alertingService)
    {
        $this->alertingService = $alertingService;
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display the alerting configuration page.
     *
     * GET /admin/alerting
     */
    public function index()
    {
        // Get all alert configurations
        $configurations = AlertConfiguration::orderBy('metric_name')->get();

        // Get all integrations
        $integrations = AlertIntegration::all()->keyBy('type');

        // Ensure default integrations exist
        $this->ensureDefaultIntegrations($integrations);
        $integrations = AlertIntegration::all()->keyBy('type');

        // Get alert statistics
        $statistics = $this->alertingService->getAlertStatistics(30);

        // Check if alerting is enabled globally
        $alertingEnabled = config('alerting.enabled', env('ALERTS_ENABLED', true));

        return view('admin.alerting.index', compact(
            'configurations',
            'integrations',
            'statistics',
            'alertingEnabled'
        ));
    }

    /**
     * Update Slack integration settings.
     *
     * PUT /admin/alerting/slack
     */
    public function updateSlack(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'webhook_url_default' => 'nullable|url',
            'webhook_url_critical' => 'nullable|url',
            'webhook_url_warnings' => 'nullable|url',
            'default_channel' => 'nullable|string|max:100',
            'mention_on_critical' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $integration = AlertIntegration::firstOrCreate(
            ['type' => 'slack'],
            ['display_name' => 'Slack']
        );

        $config = [
            'webhooks' => [
                'default' => $request->input('webhook_url_default', ''),
                'critical' => $request->input('webhook_url_critical', ''),
                'warnings' => $request->input('webhook_url_warnings', ''),
            ],
            'default_channel' => $request->input('default_channel', '#monitoring'),
            'mention_on_critical' => $request->input('mention_on_critical', '@channel'),
        ];

        $integration->update([
            'enabled' => $request->boolean('enabled'),
            'config' => $config,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Slack integration settings updated successfully',
            'integration' => $integration->fresh(),
        ]);
    }

    /**
     * Update PagerDuty integration settings.
     *
     * PUT /admin/alerting/pagerduty
     */
    public function updatePagerDuty(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'integration_key' => 'nullable|string|max:100',
            'routing_key_default' => 'nullable|string|max:100',
            'routing_key_critical' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $integration = AlertIntegration::firstOrCreate(
            ['type' => 'pagerduty'],
            ['display_name' => 'PagerDuty']
        );

        $config = [
            'integration_key' => $request->input('integration_key', ''),
            'routing_keys' => [
                'default' => $request->input('routing_key_default', ''),
                'critical' => $request->input('routing_key_critical', ''),
            ],
            'api_url' => 'https://events.pagerduty.com/v2/enqueue',
        ];

        $integration->update([
            'enabled' => $request->boolean('enabled'),
            'config' => $config,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PagerDuty integration settings updated successfully',
            'integration' => $integration->fresh(),
        ]);
    }

    /**
     * Update email integration settings.
     *
     * PUT /admin/alerting/email
     */
    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'critical_recipients' => 'nullable|array',
            'critical_recipients.*' => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $integration = AlertIntegration::firstOrCreate(
            ['type' => 'email'],
            ['display_name' => 'Email']
        );

        $config = [
            'recipients' => $request->input('recipients', []),
            'critical_recipients' => $request->input('critical_recipients', []),
            'from_address' => config('mail.from.address'),
            'from_name' => 'OvertimeStaff Alerts',
        ];

        $integration->update([
            'enabled' => $request->boolean('enabled'),
            'config' => $config,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email integration settings updated successfully',
            'integration' => $integration->fresh(),
        ]);
    }

    /**
     * Test Slack integration.
     *
     * POST /admin/alerting/test-slack
     */
    public function testSlack()
    {
        $result = $this->alertingService->testSlackIntegration();

        return response()->json($result);
    }

    /**
     * Test PagerDuty integration.
     *
     * POST /admin/alerting/test-pagerduty
     */
    public function testPagerDuty()
    {
        $result = $this->alertingService->testPagerDutyIntegration();

        return response()->json($result);
    }

    /**
     * View alert history.
     *
     * GET /admin/alerting/history
     */
    public function history(Request $request)
    {
        $query = AlertHistory::with(['incident', 'alertConfiguration', 'acknowledgedBy'])
            ->orderByDesc('created_at');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('alert_type', $request->input('type'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by metric
        if ($request->filled('metric')) {
            $query->where('metric_name', $request->input('metric'));
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date') . ' 23:59:59');
        }

        $alerts = $query->paginate(50);

        // Get filter options
        $alertTypes = AlertHistory::distinct('alert_type')->pluck('alert_type');
        $metrics = AlertHistory::distinct('metric_name')->pluck('metric_name');

        if ($request->wantsJson()) {
            return response()->json([
                'alerts' => $alerts,
                'filters' => [
                    'types' => $alertTypes,
                    'metrics' => $metrics,
                ],
            ]);
        }

        return view('admin.alerting.history', compact('alerts', 'alertTypes', 'metrics'));
    }

    /**
     * Update alert configuration.
     *
     * PUT /admin/alerting/configurations/{id}
     */
    public function updateConfiguration(Request $request, $id)
    {
        $configuration = AlertConfiguration::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'warning_threshold' => 'nullable|numeric',
            'critical_threshold' => 'nullable|numeric',
            'comparison' => 'required|in:greater_than,less_than,equals',
            'severity' => 'required|in:info,warning,critical',
            'slack_channel' => 'nullable|string|max:100',
            'pagerduty_routing_key' => 'nullable|string|max:100',
            'slack_enabled' => 'required|boolean',
            'pagerduty_enabled' => 'required|boolean',
            'email_enabled' => 'required|boolean',
            'cooldown_minutes' => 'required|integer|min:1|max:1440',
            'quiet_hours_enabled' => 'required|boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration->update($request->only([
            'enabled',
            'warning_threshold',
            'critical_threshold',
            'comparison',
            'severity',
            'slack_channel',
            'pagerduty_routing_key',
            'slack_enabled',
            'pagerduty_enabled',
            'email_enabled',
            'cooldown_minutes',
            'quiet_hours_enabled',
            'quiet_hours_start',
            'quiet_hours_end',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Alert configuration updated successfully',
            'configuration' => $configuration->fresh(),
        ]);
    }

    /**
     * Create alert configuration.
     *
     * POST /admin/alerting/configurations
     */
    public function storeConfiguration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'metric_name' => 'required|string|max:100|unique:alert_configurations,metric_name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'enabled' => 'required|boolean',
            'warning_threshold' => 'nullable|numeric',
            'critical_threshold' => 'nullable|numeric',
            'comparison' => 'required|in:greater_than,less_than,equals',
            'severity' => 'required|in:info,warning,critical',
            'slack_channel' => 'nullable|string|max:100',
            'pagerduty_routing_key' => 'nullable|string|max:100',
            'slack_enabled' => 'required|boolean',
            'pagerduty_enabled' => 'required|boolean',
            'email_enabled' => 'required|boolean',
            'cooldown_minutes' => 'required|integer|min:1|max:1440',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $configuration = AlertConfiguration::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Alert configuration created successfully',
            'configuration' => $configuration,
        ], 201);
    }

    /**
     * Delete alert configuration.
     *
     * DELETE /admin/alerting/configurations/{id}
     */
    public function destroyConfiguration($id)
    {
        $configuration = AlertConfiguration::findOrFail($id);
        $configuration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert configuration deleted successfully',
        ]);
    }

    /**
     * Toggle mute status for an alert configuration.
     *
     * POST /admin/alerting/configurations/{id}/toggle-mute
     */
    public function toggleMute($id)
    {
        $configuration = AlertConfiguration::findOrFail($id);

        $configuration->update([
            'enabled' => !$configuration->enabled,
        ]);

        $status = $configuration->enabled ? 'unmuted' : 'muted';

        return response()->json([
            'success' => true,
            'message' => "Alert configuration {$status} successfully",
            'configuration' => $configuration->fresh(),
        ]);
    }

    /**
     * Acknowledge an alert.
     *
     * POST /admin/alerting/history/{id}/acknowledge
     */
    public function acknowledgeAlert(Request $request, $id)
    {
        $alert = AlertHistory::findOrFail($id);

        $alert->acknowledge($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully',
            'alert' => $alert->fresh(['acknowledgedBy']),
        ]);
    }

    /**
     * Get alert statistics.
     *
     * GET /admin/alerting/statistics
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 30);
        $statistics = $this->alertingService->getAlertStatistics($days);

        return response()->json($statistics);
    }

    /**
     * Seed default alert configurations.
     *
     * POST /admin/alerting/seed-defaults
     */
    public function seedDefaults()
    {
        $defaults = AlertConfiguration::getDefaultConfigurations();
        $created = 0;

        foreach ($defaults as $default) {
            $existing = AlertConfiguration::where('metric_name', $default['metric_name'])->first();

            if (!$existing) {
                AlertConfiguration::create($default);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Created {$created} default alert configurations",
        ]);
    }

    /**
     * Ensure default integrations exist.
     */
    protected function ensureDefaultIntegrations($integrations): void
    {
        $defaults = AlertIntegration::getDefaultIntegrations();

        foreach ($defaults as $default) {
            if (!$integrations->has($default['type'])) {
                AlertIntegration::create($default);
            }
        }
    }

    /**
     * Retry a failed alert.
     *
     * POST /admin/alerting/history/{id}/retry
     */
    public function retryAlert($id)
    {
        $alert = AlertHistory::findOrFail($id);

        if (!$alert->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'Alert cannot be retried (max attempts reached or not in failed status)',
            ], 400);
        }

        // Reset status and trigger resend
        $alert->update(['status' => 'pending']);

        // Dispatch job to resend
        // This would typically dispatch a job to resend the alert
        // For now, we'll just update the status

        return response()->json([
            'success' => true,
            'message' => 'Alert queued for retry',
            'alert' => $alert->fresh(),
        ]);
    }

    /**
     * Get integrations status summary.
     *
     * GET /admin/alerting/integrations/status
     */
    public function integrationsStatus()
    {
        $integrations = AlertIntegration::all();

        $status = $integrations->map(function ($integration) {
            return [
                'type' => $integration->type,
                'display_name' => $integration->display_name,
                'enabled' => $integration->enabled,
                'verified' => $integration->verified,
                'last_verified_at' => $integration->last_verified_at?->toIso8601String(),
                'last_used_at' => $integration->last_used_at?->toIso8601String(),
                'total_alerts_sent' => $integration->total_alerts_sent,
                'failed_alerts' => $integration->failed_alerts,
                'success_rate' => $integration->getSuccessRate(),
            ];
        });

        return response()->json([
            'integrations' => $status,
        ]);
    }
}
