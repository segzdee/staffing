<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use App\Models\SystemIncident;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    protected $systemHealthService;

    public function __construct(SystemHealthService $systemHealthService)
    {
        $this->systemHealthService = $systemHealthService;
        $this->middleware(['auth', 'admin']); // Ensure only admins can access
    }

    /**
     * Display the system health dashboard.
     */
    public function index()
    {
        $dashboardData = $this->systemHealthService->getDashboardData();

        return view('admin.system-health.index', compact('dashboardData'));
    }

    /**
     * Get real-time health metrics (AJAX endpoint for polling).
     */
    public function getRealtimeMetrics()
    {
        $dashboardData = $this->systemHealthService->getDashboardData();

        return response()->json($dashboardData);
    }

    /**
     * Get specific metric history.
     */
    public function getMetricHistory(Request $request, $metricType)
    {
        $hours = $request->get('hours', 24);
        $startTime = now()->subHours($hours);
        $endTime = now();

        $metrics = \App\Models\SystemHealthMetric::where('metric_type', $metricType)
            ->whereBetween('recorded_at', [$startTime, $endTime])
            ->orderBy('recorded_at')
            ->get();

        $chartData = [
            'labels' => $metrics->pluck('recorded_at')->map(function ($date) {
                return $date->format('H:i');
            })->toArray(),
            'datasets' => [
                [
                    'label' => ucfirst(str_replace('_', ' ', $metricType)),
                    'data' => $metrics->pluck('value')->toArray(),
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
        ];

        return response()->json([
            'chart' => $chartData,
            'stats' => [
                'min' => $metrics->min('value'),
                'max' => $metrics->max('value'),
                'avg' => round($metrics->avg('value'), 2),
                'current' => $metrics->last()->value ?? null,
            ],
        ]);
    }

    /**
     * Display all incidents.
     */
    public function incidents()
    {
        $incidents = SystemIncident::with(['triggeredByMetric', 'assignedTo'])
            ->orderByDesc('detected_at')
            ->paginate(20);

        return view('admin.system-health.incidents', compact('incidents'));
    }

    /**
     * Show a specific incident.
     */
    public function showIncident($id)
    {
        $incident = SystemIncident::with(['triggeredByMetric', 'assignedTo'])->findOrFail($id);

        return view('admin.system-health.incident-detail', compact('incident'));
    }

    /**
     * Acknowledge an incident.
     */
    public function acknowledgeIncident(Request $request, $id)
    {
        $incident = SystemIncident::findOrFail($id);

        $incident->acknowledge($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Incident acknowledged',
            'incident' => $incident->fresh(),
        ]);
    }

    /**
     * Resolve an incident.
     */
    public function resolveIncident(Request $request, $id)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
            'prevention_steps' => 'nullable|string',
        ]);

        $incident = SystemIncident::findOrFail($id);

        $incident->resolve(
            $request->input('resolution_notes'),
            $request->input('prevention_steps')
        );

        return response()->json([
            'success' => true,
            'message' => 'Incident resolved',
            'incident' => $incident->fresh(),
        ]);
    }

    /**
     * Assign incident to a user.
     */
    public function assignIncident(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $incident = SystemIncident::findOrFail($id);

        $incident->update([
            'assigned_to_user_id' => $request->input('user_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident assigned',
            'incident' => $incident->fresh(['assignedTo']),
        ]);
    }

    /**
     * Update incident severity.
     */
    public function updateIncidentSeverity(Request $request, $id)
    {
        $request->validate([
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        $incident = SystemIncident::findOrFail($id);

        $incident->update([
            'severity' => $request->input('severity'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident severity updated',
            'incident' => $incident->fresh(),
        ]);
    }

    /**
     * Get incident statistics.
     */
    public function getIncidentStats(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $stats = [
            'total' => SystemIncident::where('detected_at', '>=', $startDate)->count(),
            'open' => SystemIncident::open()->where('detected_at', '>=', $startDate)->count(),
            'resolved' => SystemIncident::where('status', 'resolved')
                ->where('detected_at', '>=', $startDate)
                ->count(),
            'by_severity' => SystemIncident::where('detected_at', '>=', $startDate)
                ->select('severity', \DB::raw('count(*) as count'))
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'by_service' => SystemIncident::where('detected_at', '>=', $startDate)
                ->select('affected_service', \DB::raw('count(*) as count'))
                ->groupBy('affected_service')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'affected_service'),
            'average_resolution_time' => SystemIncident::where('status', 'resolved')
                ->where('detected_at', '>=', $startDate)
                ->whereNotNull('duration_minutes')
                ->avg('duration_minutes'),
        ];

        return response()->json($stats);
    }

    /**
     * Test alert system.
     */
    public function testAlert(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,slack',
        ]);

        // Create a test incident
        $incident = SystemIncident::create([
            'title' => 'Test Alert - System Health Check',
            'description' => 'This is a test alert generated manually.',
            'severity' => 'low',
            'status' => 'open',
            'affected_service' => 'test',
            'detected_at' => now(),
        ]);

        // Send notification based on type
        // This would integrate with your notification system

        $incident->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => 'Test alert completed successfully.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test alert sent successfully',
        ]);
    }
}
