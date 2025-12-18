<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\RosterInvitation;
use App\Models\RosterMember;
use App\Services\RosterService;
use Illuminate\Support\Facades\Auth;

/**
 * BIZ-005: Worker Roster Invitation Controller
 *
 * Handles roster invitation and membership operations for workers.
 */
class RosterInvitationController extends Controller
{
    protected RosterService $rosterService;

    public function __construct(RosterService $rosterService)
    {
        $this->middleware('auth');
        $this->middleware('worker');
        $this->rosterService = $rosterService;
    }

    /**
     * Display all roster invitations for the worker.
     */
    public function index()
    {
        $worker = Auth::user();

        // Pending invitations
        $pendingInvitations = RosterInvitation::where('worker_id', $worker->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with(['roster.business.businessProfile', 'invitedByUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Historical invitations
        $historicalInvitations = RosterInvitation::where('worker_id', $worker->id)
            ->where(function ($q) {
                $q->where('status', '!=', 'pending')
                    ->orWhere('expires_at', '<=', now());
            })
            ->with(['roster.business.businessProfile'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('worker.roster-invitations.index', compact(
            'pendingInvitations',
            'historicalInvitations'
        ));
    }

    /**
     * Display a specific invitation.
     */
    public function show(RosterInvitation $invitation)
    {
        if ($invitation->worker_id !== Auth::id()) {
            abort(403);
        }

        $invitation->load([
            'roster.business.businessProfile',
            'roster.members' => function ($q) {
                $q->where('status', 'active')->limit(5);
            },
            'invitedByUser',
        ]);

        // Get business stats to show worker
        $business = $invitation->roster->business;
        $businessStats = [
            'total_shifts_posted' => $business->total_shifts_posted ?? 0,
            'rating' => $business->rating_as_business ?? 0,
            'active_roster_members' => $invitation->roster->activeMembers()->count(),
        ];

        return view('worker.roster-invitations.show', compact('invitation', 'businessStats'));
    }

    /**
     * Accept a roster invitation.
     */
    public function accept(RosterInvitation $invitation)
    {
        if ($invitation->worker_id !== Auth::id()) {
            abort(403);
        }

        if (! $invitation->canRespond()) {
            return redirect()->route('worker.roster-invitations.index')
                ->with('error', 'This invitation has expired or already been responded to.');
        }

        try {
            $this->rosterService->acceptInvitation($invitation);

            return redirect()->route('worker.roster-memberships.index')
                ->with('success', "You have joined {$invitation->roster->business->name}'s {$invitation->roster->name} roster.");
        } catch (\Exception $e) {
            return redirect()->route('worker.roster-invitations.index')
                ->with('error', 'Failed to accept invitation: '.$e->getMessage());
        }
    }

    /**
     * Decline a roster invitation.
     */
    public function decline(RosterInvitation $invitation)
    {
        if ($invitation->worker_id !== Auth::id()) {
            abort(403);
        }

        if (! $invitation->canRespond()) {
            return redirect()->route('worker.roster-invitations.index')
                ->with('error', 'This invitation has expired or already been responded to.');
        }

        $this->rosterService->declineInvitation($invitation);

        return redirect()->route('worker.roster-invitations.index')
            ->with('success', 'Invitation declined.');
    }

    /**
     * Display all roster memberships for the worker.
     */
    public function memberships()
    {
        $worker = Auth::user();

        $memberships = RosterMember::where('worker_id', $worker->id)
            ->with(['roster.business.businessProfile'])
            ->orderByDesc('last_worked_at')
            ->orderByDesc('total_shifts')
            ->get();

        // Group by status
        $activeMemberships = $memberships->where('status', 'active');
        $inactiveMemberships = $memberships->where('status', 'inactive');
        $pendingMemberships = $memberships->where('status', 'pending');

        return view('worker.roster-memberships.index', compact(
            'activeMemberships',
            'inactiveMemberships',
            'pendingMemberships'
        ));
    }

    /**
     * View a specific roster membership.
     */
    public function showMembership(RosterMember $member)
    {
        if ($member->worker_id !== Auth::id()) {
            abort(403);
        }

        $member->load(['roster.business.businessProfile', 'addedByUser']);

        // Get recent shifts with this business
        $recentShifts = Auth::user()->assignedShifts()
            ->whereHas('business', function ($q) use ($member) {
                $q->where('id', $member->roster->business_id);
            })
            ->orderByDesc('shift_date')
            ->limit(5)
            ->get();

        return view('worker.roster-memberships.show', compact('member', 'recentShifts'));
    }

    /**
     * Leave a roster voluntarily.
     */
    public function leave(RosterMember $member)
    {
        if ($member->worker_id !== Auth::id()) {
            abort(403);
        }

        $rosterName = $member->roster->name;
        $businessName = $member->roster->business->name;

        $this->rosterService->leaveRoster($member);

        return redirect()->route('worker.roster-memberships.index')
            ->with('success', "You have left {$businessName}'s {$rosterName} roster.");
    }

    /**
     * Get pending invitations count (for notifications badge).
     */
    public function pendingCount()
    {
        $count = RosterInvitation::where('worker_id', Auth::id())
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->count();

        return response()->json(['count' => $count]);
    }
}
