<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImprovementMetric;
use App\Models\ImprovementSuggestion;
use App\Models\User;
use App\Services\ContinuousImprovementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * QUA-005: Continuous Improvement System
 * Admin controller for managing suggestions and viewing improvement metrics.
 */
class ImprovementController extends Controller
{
    public function __construct(
        protected ContinuousImprovementService $improvementService
    ) {}

    /**
     * Display the improvement dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return view('admin.unauthorized');
        }

        $dashboard = $this->improvementService->getAdminDashboard();

        return view('admin.improvements.index', [
            'pendingCount' => $dashboard['pending_suggestions'],
            'inProgressCount' => $dashboard['in_progress'],
            'completedThisMonth' => $dashboard['completed_this_month'],
            'healthScore' => $dashboard['health_score'],
            'topSuggestions' => $dashboard['top_suggestions'],
            'decliningMetrics' => $dashboard['declining_metrics'],
            'recentActivity' => $dashboard['recent_activity'],
        ]);
    }

    /**
     * Display all suggestions.
     */
    public function suggestions(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return view('admin.unauthorized');
        }

        $query = ImprovementSuggestion::with(['submitter', 'assignee']);

        // Filter by status
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->withCategory($request->category);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->withPriority($request->priority);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // Sort options
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'votes':
                $query->topVoted();
                break;
            case 'priority':
                $query->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')");
                break;
            case 'recent':
            default:
                $query->recent();
                break;
        }

        $suggestions = $query->paginate(20);

        // Get admin users for assignment dropdown
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.improvements.suggestions', [
            'suggestions' => $suggestions,
            'categories' => ImprovementSuggestion::getCategories(),
            'statuses' => ImprovementSuggestion::getStatuses(),
            'priorities' => ImprovementSuggestion::getPriorities(),
            'admins' => $admins,
            'filters' => [
                'status' => $request->status,
                'category' => $request->category,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Display a specific suggestion for review.
     */
    public function showSuggestion(ImprovementSuggestion $suggestion)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return view('admin.unauthorized');
        }

        $suggestion->load(['submitter', 'assignee', 'suggestionVotes.user']);

        // Get admin users for assignment dropdown
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.improvements.suggestion-detail', [
            'suggestion' => $suggestion,
            'statuses' => ImprovementSuggestion::getStatuses(),
            'admins' => $admins,
        ]);
    }

    /**
     * Update a suggestion's status and details.
     */
    public function updateSuggestion(Request $request, ImprovementSuggestion $suggestion)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return view('admin.unauthorized');
        }

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(ImprovementSuggestion::getStatuses())),
            'admin_notes' => 'nullable|string|max:5000',
            'assigned_to' => 'nullable|exists:users,id',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:2000',
        ]);

        $this->improvementService->reviewSuggestion(
            $suggestion,
            $validated['status'],
            $validated['admin_notes'] ?? null,
            $validated['assigned_to'] ?? null,
            $validated['rejection_reason'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Suggestion updated successfully.',
                'suggestion' => $suggestion->fresh(['submitter', 'assignee']),
            ]);
        }

        return redirect()
            ->route('admin.improvements.suggestion', $suggestion)
            ->with('success', 'Suggestion has been updated.');
    }

    /**
     * Bulk update suggestions.
     */
    public function bulkUpdate(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return back()->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'suggestion_ids' => 'required|array',
            'suggestion_ids.*' => 'exists:improvement_suggestions,id',
            'action' => 'required|in:status,assign,delete',
            'status' => 'required_if:action,status|nullable|in:'.implode(',', array_keys(ImprovementSuggestion::getStatuses())),
            'assigned_to' => 'required_if:action,assign|nullable|exists:users,id',
        ]);

        $count = 0;

        foreach ($validated['suggestion_ids'] as $id) {
            $suggestion = ImprovementSuggestion::find($id);

            if (! $suggestion) {
                continue;
            }

            switch ($validated['action']) {
                case 'status':
                    $suggestion->update(['status' => $validated['status']]);
                    $count++;
                    break;
                case 'assign':
                    $suggestion->update(['assigned_to' => $validated['assigned_to']]);
                    $count++;
                    break;
                case 'delete':
                    $suggestion->delete();
                    $count++;
                    break;
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} suggestions updated.",
            ]);
        }

        return back()->with('success', "{$count} suggestions have been updated.");
    }

    /**
     * Display the metrics dashboard.
     */
    public function metrics(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return view('admin.unauthorized');
        }

        $metrics = ImprovementMetric::orderBy('name')->get();

        // Get trend data for chart
        $days = (int) $request->get('days', 30);
        $trendData = [];

        foreach ($metrics as $metric) {
            $trendData[$metric->metric_key] = $this->improvementService->getMetricTrend(
                $metric->metric_key,
                $days
            );
        }

        return view('admin.improvements.metrics', [
            'metrics' => $metrics,
            'trendData' => $trendData,
            'days' => $days,
        ]);
    }

    /**
     * Update metric target values.
     */
    public function updateMetric(Request $request, ImprovementMetric $metric)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return back()->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'target_value' => 'nullable|numeric',
            'baseline_value' => 'nullable|numeric',
            'description' => 'nullable|string|max:500',
        ]);

        $metric->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Metric updated successfully.',
                'metric' => $metric->fresh(),
            ]);
        }

        return back()->with('success', 'Metric has been updated.');
    }

    /**
     * Record a new metric value manually.
     */
    public function recordMetricValue(Request $request, ImprovementMetric $metric)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return back()->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'value' => 'required|numeric',
        ]);

        $metric->recordValue($validated['value']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Metric value recorded.',
                'metric' => $metric->fresh(),
            ]);
        }

        return back()->with('success', 'Metric value has been recorded.');
    }

    /**
     * Generate and display an improvement report.
     */
    public function report(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return view('admin.unauthorized');
        }

        $report = $this->improvementService->generateImprovementReport();

        if ($request->wantsJson()) {
            return response()->json($report);
        }

        return view('admin.improvements.report', [
            'report' => $report,
        ]);
    }

    /**
     * Export improvement report as CSV.
     */
    public function exportReport(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            return back()->with('error', 'Unauthorized');
        }

        $report = $this->improvementService->generateImprovementReport();

        $filename = 'improvement-report-'.Carbon::now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($report) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, ['OvertimeStaff Improvement Report']);
            fputcsv($file, ['Generated: '.$report['generated_at']]);
            fputcsv($file, ['Period: '.$report['period']['start'].' to '.$report['period']['end']]);
            fputcsv($file, []);

            // Platform Health
            fputcsv($file, ['Platform Health Score']);
            fputcsv($file, ['Overall Score', $report['platform_health']['overall_score']]);
            fputcsv($file, ['Grade', $report['platform_health']['grade']]);
            fputcsv($file, []);

            // Health Components
            fputcsv($file, ['Health Components', 'Score']);
            foreach ($report['platform_health']['components'] as $key => $value) {
                fputcsv($file, [ucwords(str_replace('_', ' ', $key)), round($value, 2)]);
            }
            fputcsv($file, []);

            // Suggestions Summary
            fputcsv($file, ['Suggestions Summary']);
            fputcsv($file, ['Total Suggestions', $report['suggestions']['total']]);
            fputcsv($file, ['Last 30 Days Submitted', $report['suggestions']['last_30_days']['submitted']]);
            fputcsv($file, ['Last 30 Days Completed', $report['suggestions']['last_30_days']['completed']]);
            fputcsv($file, []);

            // By Status
            fputcsv($file, ['Suggestions by Status', 'Count']);
            foreach ($report['suggestions']['by_status'] as $status => $count) {
                fputcsv($file, [ucfirst($status), $count]);
            }
            fputcsv($file, []);

            // Recent Completions
            fputcsv($file, ['Recent Completions']);
            fputcsv($file, ['Title', 'Category', 'Completed Date', 'Days to Complete']);
            foreach ($report['recent_completions'] as $completion) {
                fputcsv($file, [
                    $completion['title'],
                    $completion['category'],
                    $completion['completed_at'],
                    $completion['days_to_complete'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Trigger a refresh of all metrics.
     */
    public function refreshMetrics(Request $request)
    {
        $user = auth()->user();

        if (! $user->is_dev_account && ! $user->hasPermission('improvements')) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return back()->with('error', 'Unauthorized');
        }

        $this->improvementService->updateAllMetrics();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All metrics have been refreshed.',
            ]);
        }

        return back()->with('success', 'All metrics have been refreshed.');
    }
}
