<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessRoster;
use App\Models\RosterMember;
use App\Models\User;
use App\Services\RosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BIZ-005: Business Roster Management Controller
 *
 * Handles all roster-related operations for businesses.
 */
class RosterController extends Controller
{
    protected RosterService $rosterService;

    public function __construct(RosterService $rosterService)
    {
        $this->middleware('auth');
        $this->middleware('business');
        $this->rosterService = $rosterService;
    }

    /**
     * Display list of all rosters.
     */
    public function index()
    {
        $business = Auth::user();

        $rosters = BusinessRoster::where('business_id', $business->id)
            ->withCount(['members', 'activeMembers', 'pendingInvitations'])
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $stats = $this->rosterService->getRosterStats($business);

        return view('business.rosters.index', compact('rosters', 'stats'));
    }

    /**
     * Show form to create a new roster.
     */
    public function create()
    {
        $rosterTypes = BusinessRoster::getTypes();

        return view('business.rosters.create', compact('rosterTypes'));
    }

    /**
     * Store a new roster.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:preferred,regular,backup,blacklist',
            'is_default' => 'boolean',
        ]);

        $roster = $this->rosterService->createRoster(Auth::user(), $request->all());

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', 'Roster created successfully.');
    }

    /**
     * Display a roster with its members.
     */
    public function show(BusinessRoster $roster)
    {
        $this->authorize('view', $roster);

        $roster->load(['members.worker.workerProfile', 'members.worker.skills']);

        $members = $roster->members()
            ->with(['worker.workerProfile', 'worker.skills', 'addedByUser'])
            ->orderByDesc('priority')
            ->orderByDesc('total_shifts')
            ->paginate(20);

        $pendingInvitations = $roster->pendingInvitations()
            ->with('worker')
            ->get();

        return view('business.rosters.show', compact('roster', 'members', 'pendingInvitations'));
    }

    /**
     * Show form to edit a roster.
     */
    public function edit(BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $rosterTypes = BusinessRoster::getTypes();

        return view('business.rosters.edit', compact('roster', 'rosterTypes'));
    }

    /**
     * Update a roster.
     */
    public function update(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:preferred,regular,backup,blacklist',
            'is_default' => 'boolean',
        ]);

        $this->rosterService->updateRoster($roster, $request->all());

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', 'Roster updated successfully.');
    }

    /**
     * Delete a roster.
     */
    public function destroy(BusinessRoster $roster)
    {
        $this->authorize('delete', $roster);

        $this->rosterService->deleteRoster($roster);

        return redirect()->route('business.rosters.index')
            ->with('success', 'Roster deleted successfully.');
    }

    /**
     * Show add member form.
     */
    public function addMemberForm(BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        return view('business.rosters.add-member', compact('roster'));
    }

    /**
     * Search workers to add to roster.
     */
    public function searchWorkers(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'search' => 'required|string|min:2|max:100',
        ]);

        $workers = $this->rosterService->searchWorkersForRoster(
            Auth::user(),
            $request->search,
            $roster->type
        );

        return response()->json([
            'workers' => $workers->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'email' => $worker->email,
                    'rating' => $worker->rating_as_worker,
                    'total_shifts' => $worker->total_shifts_completed,
                    'avatar' => $worker->avatar ?? null,
                ];
            }),
        ]);
    }

    /**
     * Add a worker to a roster.
     */
    public function addMember(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'custom_rate' => 'nullable|numeric|min:0|max:9999.99',
            'priority' => 'nullable|integer|min:0|max:100',
            'preferred_positions' => 'nullable|array',
            'status' => 'nullable|in:active,inactive,pending',
        ]);

        $worker = User::findOrFail($request->worker_id);

        if ($worker->user_type !== 'worker') {
            return back()->with('error', 'Only workers can be added to rosters.');
        }

        if ($roster->hasWorker($worker)) {
            return back()->with('error', 'This worker is already in this roster.');
        }

        $this->rosterService->addWorkerToRoster($roster, $worker, array_merge(
            $request->only(['notes', 'custom_rate', 'priority', 'preferred_positions', 'status']),
            ['added_by' => Auth::id()]
        ));

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', "Worker {$worker->name} added to roster.");
    }

    /**
     * Update a roster member.
     */
    public function updateMember(Request $request, BusinessRoster $roster, RosterMember $member)
    {
        $this->authorize('update', $roster);

        if ($member->roster_id !== $roster->id) {
            abort(404);
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
            'custom_rate' => 'nullable|numeric|min:0|max:9999.99',
            'priority' => 'nullable|integer|min:0|max:100',
            'preferred_positions' => 'nullable|array',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $this->rosterService->updateRosterMember($member, $request->all());

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', 'Member updated successfully.');
    }

    /**
     * Remove a worker from a roster.
     */
    public function removeMember(Request $request, BusinessRoster $roster, RosterMember $member)
    {
        $this->authorize('update', $roster);

        if ($member->roster_id !== $roster->id) {
            abort(404);
        }

        $reason = $request->input('reason');
        $workerName = $member->worker->name;

        $this->rosterService->removeWorkerFromRoster($member, $reason);

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', "Worker {$workerName} removed from roster.");
    }

    /**
     * Invite a worker to join a roster.
     */
    public function inviteWorker(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:1000',
        ]);

        $worker = User::findOrFail($request->worker_id);

        if ($worker->user_type !== 'worker') {
            return back()->with('error', 'Only workers can be invited to rosters.');
        }

        if ($roster->hasWorker($worker)) {
            return back()->with('error', 'This worker is already in this roster.');
        }

        if ($roster->hasPendingInvitation($worker)) {
            return back()->with('error', 'This worker already has a pending invitation.');
        }

        $this->rosterService->inviteWorkerToRoster($roster, $worker, $request->message);

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', "Invitation sent to {$worker->name}.");
    }

    /**
     * Bulk invite multiple workers.
     */
    public function bulkInvite(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:users,id',
            'message' => 'nullable|string|max:1000',
        ]);

        $result = $this->rosterService->bulkInviteWorkers(
            $roster,
            $request->worker_ids,
            $request->message
        );

        $message = "Invited {$result['invited']} worker(s) successfully.";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']} (already invited or in roster).";
        }

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', $message);
    }

    /**
     * Bulk add workers directly to roster.
     */
    public function bulkAdd(Request $request, BusinessRoster $roster)
    {
        $this->authorize('update', $roster);

        $request->validate([
            'worker_ids' => 'required|array|min:1',
            'worker_ids.*' => 'exists:users,id',
            'status' => 'nullable|in:active,inactive,pending',
        ]);

        $result = $this->rosterService->bulkAddWorkers(
            $roster,
            $request->worker_ids,
            ['status' => $request->status ?? 'active', 'added_by' => Auth::id()]
        );

        $message = "Added {$result['added']} worker(s) successfully.";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']} (already in roster or invalid).";
        }

        return redirect()->route('business.rosters.show', $roster)
            ->with('success', $message);
    }

    /**
     * Move a member to another roster.
     */
    public function moveMember(Request $request, BusinessRoster $roster, RosterMember $member)
    {
        $this->authorize('update', $roster);

        if ($member->roster_id !== $roster->id) {
            abort(404);
        }

        $request->validate([
            'target_roster_id' => 'required|exists:business_rosters,id',
        ]);

        $targetRoster = BusinessRoster::findOrFail($request->target_roster_id);
        $this->authorize('update', $targetRoster);

        $workerName = $member->worker->name;
        $this->rosterService->moveWorkerToRoster($member, $targetRoster);

        return redirect()->route('business.rosters.show', $targetRoster)
            ->with('success', "Worker {$workerName} moved to {$targetRoster->name}.");
    }

    /**
     * Blacklist a worker (add to blacklist roster).
     */
    public function blacklistWorker(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        $worker = User::findOrFail($request->worker_id);

        if ($worker->user_type !== 'worker') {
            return back()->with('error', 'Only workers can be blacklisted.');
        }

        $this->rosterService->blacklistWorker(Auth::user(), $worker, $request->reason);

        return back()->with('success', "Worker {$worker->name} has been blacklisted.");
    }

    /**
     * Remove a worker from blacklist.
     */
    public function unblacklistWorker(Request $request)
    {
        $request->validate([
            'worker_id' => 'required|exists:users,id',
        ]);

        $worker = User::findOrFail($request->worker_id);

        $this->rosterService->unblacklistWorker(Auth::user(), $worker);

        return back()->with('success', "Worker {$worker->name} has been removed from blacklist.");
    }

    /**
     * Get available roster workers for a specific shift.
     */
    public function forShift(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $shift = \App\Models\Shift::findOrFail($request->shift_id);

        if ($shift->business_id !== Auth::id()) {
            abort(403);
        }

        $prioritizedWorkers = $this->rosterService->prioritizeRosterForShift($shift);

        return response()->json([
            'workers' => $prioritizedWorkers,
        ]);
    }
}
