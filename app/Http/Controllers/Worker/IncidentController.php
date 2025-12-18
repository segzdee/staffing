<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Shift;
use App\Models\Venue;
use App\Services\IncidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * SAF-002: Worker Incident Reporting Controller
 *
 * Allows workers to report incidents, view their reported incidents,
 * and add updates to existing incident reports.
 */
class IncidentController extends Controller
{
    public function __construct(protected IncidentService $incidentService)
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of worker's incidents.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $filters = $request->only(['status', 'type']);
        $incidents = $this->incidentService->getIncidentsForUser($user, $filters);

        return view('worker.incidents.index', [
            'incidents' => $incidents,
            'types' => Incident::TYPE_LABELS,
            'statuses' => Incident::STATUS_LABELS,
            'severities' => Incident::SEVERITIES,
        ]);
    }

    /**
     * Show the form for creating a new incident report.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // Get worker's recent shifts for context
        $recentShifts = Shift::whereHas('assignments', function ($q) use ($user) {
            $q->where('worker_id', $user->id);
        })
            ->where('shift_date', '>=', now()->subDays(7))
            ->orderBy('shift_date', 'desc')
            ->get();

        // Pre-select shift if provided in query string
        $selectedShiftId = $request->get('shift_id');
        $selectedShift = $selectedShiftId ? Shift::find($selectedShiftId) : null;

        // Get venues for the selected shift's business (if applicable)
        $venues = collect();
        if ($selectedShift) {
            $venues = Venue::where('business_profile_id', $selectedShift->business_id)
                ->where('is_active', true)
                ->get();
        }

        return view('worker.incidents.create', [
            'recentShifts' => $recentShifts,
            'selectedShift' => $selectedShift,
            'venues' => $venues,
            'types' => Incident::TYPE_LABELS,
            'severities' => Incident::SEVERITIES,
        ]);
    }

    /**
     * Store a newly created incident report.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'involves_user_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', Rule::in(Incident::TYPES)],
            'severity' => ['required', Rule::in(Incident::SEVERITIES)],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'location_description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'incident_time' => ['required', 'date', 'before_or_equal:now'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,mov,pdf', 'max:20480'], // 20MB max
            'witnesses' => ['nullable', 'array', 'max:5'],
            'witnesses.*.name' => ['required_with:witnesses', 'string', 'max:255'],
            'witnesses.*.phone' => ['nullable', 'string', 'max:50'],
            'witnesses.*.email' => ['nullable', 'email', 'max:255'],
            'witnesses.*.statement' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        // Process evidence uploads
        $evidenceUrls = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $path = $file->store('incidents/evidence', 'public');
                $evidenceUrls[] = [
                    'url' => Storage::url($path),
                    'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image',
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // Prepare incident data
        $incidentData = [
            'shift_id' => $validated['shift_id'],
            'venue_id' => $validated['venue_id'],
            'involves_user_id' => $validated['involves_user_id'],
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'description' => $validated['description'],
            'location_description' => $validated['location_description'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'incident_time' => $validated['incident_time'],
            'evidence_urls' => $evidenceUrls,
            'witness_info' => $validated['witnesses'] ?? [],
        ];

        // Create the incident
        $incident = $this->incidentService->reportIncident($user, $incidentData);

        return redirect()
            ->route('worker.incidents.show', $incident)
            ->with('success', 'Incident reported successfully. Your incident number is: '.$incident->incident_number);
    }

    /**
     * Display the specified incident.
     */
    public function show(Incident $incident)
    {
        $user = Auth::user();

        // Authorization check
        if (! $incident->canBeViewedBy($user)) {
            abort(403, 'You do not have permission to view this incident.');
        }

        // Load relationships
        $incident->load([
            'shift',
            'venue',
            'reporter',
            'involvedUser',
            'assignee',
            'publicUpdates.user', // Workers only see public updates
        ]);

        return view('worker.incidents.show', [
            'incident' => $incident,
            'updates' => $incident->publicUpdates,
        ]);
    }

    /**
     * Add an update/comment to an incident.
     */
    public function addUpdate(Request $request, Incident $incident)
    {
        $user = Auth::user();

        // Authorization check - only reporter can add updates
        if ($incident->reported_by !== $user->id) {
            abort(403, 'You can only add updates to incidents you reported.');
        }

        // Cannot add updates to closed incidents
        if ($incident->isClosed()) {
            return back()->with('error', 'Cannot add updates to resolved or closed incidents.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:5', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,mov,pdf', 'max:10240'], // 10MB max
        ]);

        // Process attachment uploads
        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('incidents/updates', 'public');
                $attachmentUrls[] = [
                    'url' => Storage::url($path),
                    'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image',
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        // Add the update (public, not internal)
        $this->incidentService->addUpdate(
            $incident,
            $user,
            $validated['content'],
            $attachmentUrls,
            false
        );

        return back()->with('success', 'Update added to incident report.');
    }

    /**
     * Add additional evidence to an incident.
     */
    public function addEvidence(Request $request, Incident $incident)
    {
        $user = Auth::user();

        // Authorization check - only reporter can add evidence
        if ($incident->reported_by !== $user->id) {
            abort(403, 'You can only add evidence to incidents you reported.');
        }

        // Cannot add evidence to closed incidents
        if ($incident->isClosed()) {
            return back()->with('error', 'Cannot add evidence to resolved or closed incidents.');
        }

        $validated = $request->validate([
            'evidence' => ['required', 'array', 'min:1', 'max:5'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,gif,mp4,mov,pdf', 'max:20480'],
        ]);

        $files = [];
        foreach ($request->file('evidence') as $file) {
            $path = $file->store('incidents/evidence', 'public');
            $files[] = [
                'url' => Storage::url($path),
                'type' => str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image',
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        $this->incidentService->addEvidence($incident, $files);

        return back()->with('success', 'Evidence added to incident report.');
    }

    /**
     * Add witness information to an incident.
     */
    public function addWitness(Request $request, Incident $incident)
    {
        $user = Auth::user();

        // Authorization check - only reporter can add witnesses
        if ($incident->reported_by !== $user->id) {
            abort(403, 'You can only add witnesses to incidents you reported.');
        }

        // Cannot add witnesses to closed incidents
        if ($incident->isClosed()) {
            return back()->with('error', 'Cannot add witnesses to resolved or closed incidents.');
        }

        // Check witness limit
        if ($incident->getWitnessCount() >= 10) {
            return back()->with('error', 'Maximum witness limit (10) reached.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'statement' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->incidentService->addWitness($incident, $validated);

        return back()->with('success', 'Witness information added to incident report.');
    }

    /**
     * Get venues for a specific shift (AJAX endpoint).
     */
    public function getVenuesForShift(Request $request)
    {
        $shiftId = $request->get('shift_id');

        if (! $shiftId) {
            return response()->json(['venues' => []]);
        }

        $shift = Shift::find($shiftId);

        if (! $shift) {
            return response()->json(['venues' => []]);
        }

        $venues = Venue::where('business_profile_id', $shift->business_id)
            ->where('is_active', true)
            ->select('id', 'name', 'address', 'city')
            ->get();

        return response()->json(['venues' => $venues]);
    }

    /**
     * Get a specific user for the "involves" field (AJAX endpoint).
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');

        if (! $query || strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = \App\Models\User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->where('id', '!=', Auth::id())
            ->limit(10)
            ->select('id', 'name', 'email', 'user_type')
            ->get();

        return response()->json(['users' => $users]);
    }
}
