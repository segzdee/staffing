<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\User;
use App\Models\Venue;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * SAF-002: Admin Incident Management Controller
 *
 * Allows administrators to view, manage, investigate, and resolve
 * incidents reported by workers and businesses.
 */
class IncidentController extends Controller
{
    public function __construct(protected IncidentService $incidentService)
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display a listing of all incidents.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'status',
            'type',
            'severity',
            'venue_id',
            'assigned_to',
            'start_date',
            'end_date',
            'per_page',
        ]);

        $search = $request->get('search');
        $incidents = $this->incidentService->searchIncidents($search ?? '', $filters);

        // Get statistics for the dashboard
        $statistics = $this->incidentService->getStatistics($filters);

        // Get admin users for assignment filter
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        // Get venues for filter
        $venues = Venue::where('is_active', true)->orderBy('name')->get();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'statistics' => $statistics,
            'types' => Incident::TYPE_LABELS,
            'statuses' => Incident::STATUS_LABELS,
            'severities' => Incident::SEVERITIES,
            'admins' => $admins,
            'venues' => $venues,
            'filters' => $filters,
            'search' => $search,
        ]);
    }

    /**
     * Display the specified incident.
     */
    public function show(Incident $incident)
    {
        // Load all relationships
        $incident->load([
            'shift.business',
            'venue',
            'reporter',
            'involvedUser',
            'assignee',
            'updates.user', // Admins see all updates
        ]);

        // Get admin users for assignment
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        return view('admin.incidents.show', [
            'incident' => $incident,
            'updates' => $incident->updates,
            'admins' => $admins,
            'statuses' => Incident::STATUS_LABELS,
        ]);
    }

    /**
     * Assign an investigator to the incident.
     */
    public function assign(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $admin = User::findOrFail($validated['assigned_to']);

        // Verify the assignee is an admin
        if ($admin->role !== 'admin') {
            return back()->with('error', 'Incidents can only be assigned to administrators.');
        }

        $this->incidentService->assignInvestigator($incident, $admin);

        return back()->with('success', "Incident assigned to {$admin->name}.");
    }

    /**
     * Update the incident status.
     */
    public function updateStatus(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Incident::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // Special handling for resolution
        if ($validated['status'] === Incident::STATUS_RESOLVED) {
            if (empty($validated['notes'])) {
                return back()->with('error', 'Resolution notes are required when resolving an incident.');
            }
            $this->incidentService->resolveIncident($incident, $validated['notes']);
        } else {
            $this->incidentService->updateStatus($incident, $validated['status'], $validated['notes']);
        }

        return back()->with('success', 'Incident status updated successfully.');
    }

    /**
     * Escalate the incident.
     */
    public function escalate(Incident $incident)
    {
        if ($incident->status === Incident::STATUS_ESCALATED) {
            return back()->with('error', 'Incident is already escalated.');
        }

        if ($incident->isClosed()) {
            return back()->with('error', 'Cannot escalate a closed incident.');
        }

        $this->incidentService->escalateIncident($incident);

        return back()->with('success', 'Incident escalated to senior management.');
    }

    /**
     * Resolve the incident.
     */
    public function resolve(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $this->incidentService->resolveIncident($incident, $validated['resolution_notes']);

        return back()->with('success', 'Incident resolved successfully.');
    }

    /**
     * Close the incident.
     */
    public function close(Incident $incident)
    {
        if ($incident->status !== Incident::STATUS_RESOLVED) {
            return back()->with('error', 'Only resolved incidents can be closed. Please resolve the incident first.');
        }

        $this->incidentService->closeIncident($incident);

        return back()->with('success', 'Incident closed successfully.');
    }

    /**
     * Reopen a closed incident.
     */
    public function reopen(Request $request, Incident $incident)
    {
        if (! $incident->isClosed()) {
            return back()->with('error', 'Only closed incidents can be reopened.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $this->incidentService->updateStatus($incident, Incident::STATUS_INVESTIGATING, "Incident reopened. Reason: {$validated['reason']}");

        // Clear resolution data
        $incident->update([
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);

        return back()->with('success', 'Incident reopened successfully.');
    }

    /**
     * Add an internal note to the incident.
     */
    public function addInternalNote(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx', 'max:10240'],
        ]);

        // Process attachment uploads
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('incidents/admin-notes', 'public');
                $attachmentUrls[] = [
                    'url' => Storage::url($path),
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        // Add internal note (only visible to admins)
        $this->incidentService->addUpdate(
            $incident,
            Auth::user(),
            $validated['content'],
            $attachmentUrls,
            true // internal note
        );

        return back()->with('success', 'Internal note added.');
    }

    /**
     * Add a public update to the incident (visible to reporter).
     */
    public function addPublicUpdate(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx', 'max:10240'],
        ]);

        // Process attachment uploads
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('incidents/updates', 'public');
                $attachmentUrls[] = [
                    'url' => Storage::url($path),
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        // Add public update (visible to reporter)
        $this->incidentService->addUpdate(
            $incident,
            Auth::user(),
            $validated['content'],
            $attachmentUrls,
            false // public update
        );

        return back()->with('success', 'Update added and reporter notified.');
    }

    /**
     * Mark insurance claim as required.
     */
    public function flagInsurance(Incident $incident)
    {
        if ($incident->requires_insurance_claim) {
            return back()->with('error', 'Insurance claim is already flagged for this incident.');
        }

        $this->incidentService->triggerInsuranceClaim($incident);

        return back()->with('success', 'Insurance claim flagged as required.');
    }

    /**
     * Record insurance claim number.
     */
    public function recordClaimNumber(Request $request, Incident $incident)
    {
        $validated = $request->validate([
            'insurance_claim_number' => ['required', 'string', 'max:100'],
        ]);

        $this->incidentService->recordInsuranceClaimNumber($incident, $validated['insurance_claim_number']);

        return back()->with('success', 'Insurance claim number recorded.');
    }

    /**
     * Mark authorities as notified.
     */
    public function notifyAuthorities(Incident $incident)
    {
        if ($incident->authorities_notified) {
            return back()->with('error', 'Authorities have already been marked as notified.');
        }

        $this->incidentService->markAuthoritiesNotified($incident);

        return back()->with('success', 'Authorities marked as notified.');
    }

    /**
     * Export incidents to CSV.
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'status',
            'type',
            'severity',
            'venue_id',
            'assigned_to',
            'start_date',
            'end_date',
        ]);

        $search = $request->get('search');

        // Get incidents
        $query = Incident::query()
            ->with(['shift', 'venue', 'reporter', 'assignee', 'involvedUser']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('incident_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        foreach ($filters as $key => $value) {
            if ($value) {
                $query->where($key, $value);
            }
        }

        $incidents = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $csv = "Incident Number,Type,Severity,Status,Description,Location,Reported By,Reported At,Assigned To,Resolved At\n";

        foreach ($incidents as $incident) {
            $csv .= implode(',', [
                $incident->incident_number,
                $incident->getTypeLabel(),
                ucfirst($incident->severity),
                $incident->getStatusLabel(),
                '"'.str_replace('"', '""', substr($incident->description, 0, 200)).'"',
                '"'.str_replace('"', '""', $incident->location_description ?? '').'"',
                '"'.($incident->reporter->name ?? 'Unknown').'"',
                $incident->created_at->format('Y-m-d H:i:s'),
                '"'.($incident->assignee->name ?? 'Unassigned').'"',
                $incident->resolved_at ? $incident->resolved_at->format('Y-m-d H:i:s') : '',
            ])."\n";
        }

        $filename = 'incidents_export_'.now()->format('Y-m-d_His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Display incident analytics/dashboard.
     */
    public function analytics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $statistics = $this->incidentService->getStatistics([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        // Get trend data (incidents per day)
        $trendData = Incident::whereBetween('incident_time', [$startDate, $endDate])
            ->selectRaw('DATE(incident_time) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get top venues by incident count
        $topVenues = Incident::whereBetween('incident_time', [$startDate, $endDate])
            ->whereNotNull('venue_id')
            ->selectRaw('venue_id, COUNT(*) as count')
            ->groupBy('venue_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('venue')
            ->get();

        // Get resolution time distribution
        $resolutionTimes = Incident::whereBetween('incident_time', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->selectRaw('TIMESTAMPDIFF(HOUR, created_at, resolved_at) as hours')
            ->pluck('hours');

        $resolutionDistribution = [
            'under_24h' => $resolutionTimes->filter(fn ($h) => $h < 24)->count(),
            '24_48h' => $resolutionTimes->filter(fn ($h) => $h >= 24 && $h < 48)->count(),
            '48_72h' => $resolutionTimes->filter(fn ($h) => $h >= 48 && $h < 72)->count(),
            'over_72h' => $resolutionTimes->filter(fn ($h) => $h >= 72)->count(),
        ];

        return view('admin.incidents.analytics', [
            'statistics' => $statistics,
            'trendData' => $trendData,
            'topVenues' => $topVenues,
            'resolutionDistribution' => $resolutionDistribution,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Bulk assign incidents.
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'incident_ids' => ['required', 'array', 'min:1'],
            'incident_ids.*' => ['exists:incidents,id'],
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $admin = User::findOrFail($validated['assigned_to']);

        if ($admin->role !== 'admin') {
            return back()->with('error', 'Incidents can only be assigned to administrators.');
        }

        $count = 0;
        foreach ($validated['incident_ids'] as $incidentId) {
            $incident = Incident::find($incidentId);
            if ($incident && $incident->isOpen()) {
                $this->incidentService->assignInvestigator($incident, $admin);
                $count++;
            }
        }

        return back()->with('success', "{$count} incidents assigned to {$admin->name}.");
    }

    /**
     * Get incidents assigned to current admin.
     */
    public function myIncidents(Request $request)
    {
        $admin = Auth::user();

        $filters = $request->only(['status', 'type', 'severity']);
        $filters['assigned_to'] = $admin->id;

        $search = $request->get('search');
        $incidents = $this->incidentService->searchIncidents($search ?? '', $filters);

        // Get my statistics
        $myStatistics = [
            'assigned' => Incident::where('assigned_to', $admin->id)->open()->count(),
            'resolved_this_week' => Incident::where('assigned_to', $admin->id)
                ->where('status', Incident::STATUS_RESOLVED)
                ->where('resolved_at', '>=', now()->startOfWeek())
                ->count(),
            'avg_resolution_hours' => Incident::where('assigned_to', $admin->id)
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        return view('admin.incidents.my-incidents', [
            'incidents' => $incidents,
            'myStatistics' => $myStatistics,
            'types' => Incident::TYPE_LABELS,
            'statuses' => Incident::STATUS_LABELS,
            'severities' => Incident::SEVERITIES,
            'filters' => $filters,
            'search' => $search,
        ]);
    }
}
