<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueSafetyFlag;
use App\Models\VenueSafetyRating;
use App\Services\VenueSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SAF-004: Admin Venue Safety Controller
 *
 * Handles admin management of the venue safety system including:
 * - Dashboard with safety statistics
 * - Flag management and investigation
 * - Venue safety status management
 * - Safety audits
 */
class VenueSafetyController extends Controller
{
    public function __construct(
        protected VenueSafetyService $safetyService
    ) {}

    /**
     * Display safety dashboard with statistics.
     */
    public function index(Request $request): View
    {
        $stats = $this->safetyService->getAdminDashboardStats();
        $flagsRequiringAttention = $this->safetyService->getFlagsRequiringAttention();
        $unsafeVenues = $this->safetyService->getUnsafeVenues();

        // Recent activity
        $recentFlags = VenueSafetyFlag::with(['venue', 'reporter', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentRatings = VenueSafetyRating::with(['venue', 'user'])
            ->where('overall_safety', '<=', 3)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.safety.index', [
            'stats' => $stats,
            'flagsRequiringAttention' => $flagsRequiringAttention,
            'unsafeVenues' => $unsafeVenues,
            'recentFlags' => $recentFlags,
            'recentRatings' => $recentRatings,
        ]);
    }

    /**
     * List all safety flags with filtering.
     */
    public function flags(Request $request): View
    {
        $query = VenueSafetyFlag::with(['venue', 'reporter', 'assignee']);

        // Apply filters
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        if ($request->filled('severity')) {
            $query->withSeverity($request->severity);
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('venue_id')) {
            $query->forVenue($request->venue_id);
        }

        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $query->assignedTo($request->assigned_to);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('venue', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('reporter', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Priority sorting: critical first, then by created date
        if ($sortField === 'priority') {
            $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
                ->orderBy('created_at', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $flags = $query->paginate(20)->withQueryString();

        // Get filter options
        $venues = Venue::orderBy('name')->pluck('name', 'id');
        $admins = User::where('role', 'admin')->orderBy('name')->pluck('name', 'id');

        return view('admin.safety.flags', [
            'flags' => $flags,
            'venues' => $venues,
            'admins' => $admins,
            'flagTypes' => VenueSafetyFlag::getTypeOptions(),
            'severityOptions' => VenueSafetyFlag::getSeverityOptions(),
            'statusOptions' => VenueSafetyFlag::getStatusOptions(),
            'currentFilters' => $request->only(['status', 'severity', 'type', 'venue_id', 'assigned_to', 'search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show details of a specific flag.
     */
    public function showFlag(VenueSafetyFlag $flag): View
    {
        $flag->load(['venue.businessProfile.user', 'reporter', 'assignee']);

        // Get other flags for this venue
        $relatedFlags = VenueSafetyFlag::forVenue($flag->venue_id)
            ->where('id', '!=', $flag->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get venue safety summary
        $venueSummary = $this->safetyService->getVenueSafetySummary($flag->venue);

        // Get admins for assignment dropdown
        $admins = User::where('role', 'admin')->orderBy('name')->pluck('name', 'id');

        return view('admin.safety.flag-show', [
            'flag' => $flag,
            'relatedFlags' => $relatedFlags,
            'venueSummary' => $venueSummary,
            'admins' => $admins,
        ]);
    }

    /**
     * Assign a flag to an admin for investigation.
     */
    public function assignFlag(Request $request, VenueSafetyFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $admin = User::findOrFail($validated['assigned_to']);
        $this->safetyService->assignFlagToAdmin($flag, $admin);

        return redirect()->back()
            ->with('success', "Flag assigned to {$admin->name} for investigation.");
    }

    /**
     * Update flag status.
     */
    public function updateFlagStatus(Request $request, VenueSafetyFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(VenueSafetyFlag::STATUS_LABELS)),
        ]);

        $flag->update(['status' => $validated['status']]);

        // Recalculate venue safety status
        $this->safetyService->updateVenueSafetyStatus($flag->venue);

        return redirect()->back()
            ->with('success', 'Flag status updated successfully.');
    }

    /**
     * Resolve a flag.
     */
    public function resolveFlag(Request $request, VenueSafetyFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|min:10|max:5000',
        ]);

        $this->safetyService->resolveFlag($flag, $request->user(), $validated['resolution_notes']);

        return redirect()->route('admin.safety.flags')
            ->with('success', 'Safety flag has been resolved.');
    }

    /**
     * Dismiss a flag.
     */
    public function dismissFlag(Request $request, VenueSafetyFlag $flag): RedirectResponse
    {
        $validated = $request->validate([
            'dismissal_reason' => 'required|string|min:10|max:2000',
        ]);

        $this->safetyService->dismissFlag($flag, $request->user(), $validated['dismissal_reason']);

        return redirect()->route('admin.safety.flags')
            ->with('success', 'Safety flag has been dismissed.');
    }

    /**
     * List all venues with safety information.
     */
    public function venues(Request $request): View
    {
        $query = Venue::with(['businessProfile', 'safetyRatings', 'safetyFlags']);

        // Apply filters
        if ($request->filled('safety_status')) {
            $query->where('safety_status', $request->safety_status);
        }

        if ($request->filled('has_flags')) {
            if ($request->has_flags === 'yes') {
                $query->where('active_safety_flags', '>', 0);
            } else {
                $query->where('active_safety_flags', 0);
            }
        }

        if ($request->filled('score_below')) {
            $query->where('safety_score', '<', $request->score_below)
                ->whereNotNull('safety_score');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'safety_score');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'safety_score') {
            // Venues without scores at the end
            $query->orderByRaw('safety_score IS NULL')
                ->orderBy('safety_score', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $venues = $query->paginate(20)->withQueryString();

        return view('admin.safety.venues', [
            'venues' => $venues,
            'safetyStatuses' => Venue::SAFETY_STATUSES,
            'currentFilters' => $request->only(['safety_status', 'has_flags', 'score_below', 'search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show detailed safety information for a venue.
     */
    public function showVenue(Venue $venue): View
    {
        $venue->load(['businessProfile.user', 'safetyRatings', 'safetyFlags']);

        $summary = $this->safetyService->getVenueSafetySummary($venue);
        $trend = $this->safetyService->getSafetyTrend($venue, 12);

        $ratings = VenueSafetyRating::forVenue($venue->id)
            ->with(['user', 'shift'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'ratings_page');

        $flags = VenueSafetyFlag::forVenue($venue->id)
            ->with(['reporter', 'assignee'])
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'flags_page');

        return view('admin.safety.venue-show', [
            'venue' => $venue,
            'summary' => $summary,
            'trend' => $trend,
            'ratings' => $ratings,
            'flags' => $flags,
        ]);
    }

    /**
     * Update venue safety status manually.
     */
    public function updateVenueStatus(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'safety_status' => 'required|in:'.implode(',', array_keys(Venue::SAFETY_STATUSES)),
        ]);

        $venue->update(['safety_status' => $validated['safety_status']]);

        return redirect()->back()
            ->with('success', 'Venue safety status updated successfully.');
    }

    /**
     * Record a safety audit for a venue.
     */
    public function recordAudit(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'passed' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->safetyService->recordSafetyAudit(
            $venue,
            $request->user(),
            $validated['passed']
        );

        $status = $validated['passed'] ? 'passed' : 'failed';

        return redirect()->back()
            ->with('success', "Safety audit recorded as {$status}.");
    }

    /**
     * Trigger investigation for a venue.
     */
    public function triggerInvestigation(Venue $venue): RedirectResponse
    {
        $this->safetyService->triggerInvestigation($venue);

        return redirect()->back()
            ->with('success', 'Investigation has been triggered for this venue.');
    }

    /**
     * Bulk assign flags to an admin.
     */
    public function bulkAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'flag_ids' => 'required|array|min:1',
            'flag_ids.*' => 'exists:venue_safety_flags,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $admin = User::findOrFail($validated['assigned_to']);

        foreach ($validated['flag_ids'] as $flagId) {
            $flag = VenueSafetyFlag::find($flagId);
            if ($flag && $flag->is_open) {
                $this->safetyService->assignFlagToAdmin($flag, $admin);
            }
        }

        $count = count($validated['flag_ids']);

        return redirect()->back()
            ->with('success', "{$count} flag(s) assigned to {$admin->name}.");
    }

    /**
     * Export safety data.
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'flags');
        $format = $request->get('format', 'csv');

        if ($type === 'flags') {
            $data = VenueSafetyFlag::with(['venue', 'reporter', 'assignee'])->get();
        } else {
            $data = VenueSafetyRating::with(['venue', 'user', 'shift'])->get();
        }

        // For now, return JSON - CSV export can be implemented with Laravel Excel
        return response()->json([
            'data' => $data,
            'exported_at' => now()->toIso8601String(),
            'count' => $data->count(),
        ]);
    }

    /**
     * API: Get dashboard statistics.
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->safetyService->getAdminDashboardStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * API: Get flags requiring attention.
     */
    public function getFlagsRequiringAttention(): JsonResponse
    {
        $flags = $this->safetyService->getFlagsRequiringAttention();

        return response()->json([
            'success' => true,
            'data' => $flags,
            'count' => $flags->count(),
        ]);
    }

    /**
     * API: Get unsafe venues.
     */
    public function getUnsafeVenues(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', VenueSafetyService::SAFETY_SCORE_THRESHOLD);
        $venues = $this->safetyService->getUnsafeVenues($threshold);

        return response()->json([
            'success' => true,
            'data' => $venues,
            'count' => $venues->count(),
            'threshold' => $threshold,
        ]);
    }
}
