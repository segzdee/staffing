<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\Team\CreateTeamRequest;
use App\Http\Requests\Worker\Team\TeamShiftApplicationRequest;
use App\Models\Shift;
use App\Models\TeamShiftRequest;
use App\Models\User;
use App\Models\WorkerTeam;
use App\Models\WorkerTeamMember;
use App\Services\TeamFormationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * WKR-014: Team Formation - Worker Team Controller
 *
 * Manages worker teams and team shift applications.
 */
class WorkerTeamController extends Controller
{
    public function __construct(
        protected TeamFormationService $teamFormationService
    ) {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Display teams dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $myTeams = $this->teamFormationService->getWorkerTeams($user);
        $teamInvitations = $this->teamFormationService->getTeamInvitations($user);

        // Get public teams for discovery
        $publicTeams = WorkerTeam::active()
            ->public()
            ->where('created_by', '!=', $user->id)
            ->whereDoesntHave('memberships', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('creator')
            ->limit(10)
            ->get();

        return view('worker.teams.index', [
            'myTeams' => $myTeams,
            'teamInvitations' => $teamInvitations,
            'publicTeams' => $publicTeams,
        ]);
    }

    /**
     * Show team creation form.
     */
    public function create(): View
    {
        return view('worker.teams.create');
    }

    /**
     * Create a new team.
     */
    public function store(CreateTeamRequest $request): JsonResponse
    {
        try {
            $team = $this->teamFormationService->createTeam(
                $request->user(),
                $request->name,
                $request->member_ids ?? [],
                [
                    'description' => $request->description,
                    'max_members' => $request->max_members ?? 10,
                    'is_public' => $request->is_public ?? false,
                    'requires_approval' => $request->requires_approval ?? true,
                    'specializations' => $request->specializations,
                    'preferred_industries' => $request->preferred_industries,
                    'min_reliability_score' => $request->min_reliability_score,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully!',
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'member_count' => $team->member_count,
                ],
                'redirect' => route('worker.teams.show', $team),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show team details.
     */
    public function show(Request $request, WorkerTeam $team): View
    {
        $user = $request->user();

        // Check access - must be member or public team
        if (! $team->is_public && ! $team->hasMember($user)) {
            abort(403, 'You do not have access to this team.');
        }

        $team->load(['creator', 'activeMembers', 'shiftRequests' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $isLeader = $team->hasLeader($user);
        $isMember = $team->hasMember($user);

        // Get pending invitations if leader
        $pendingInvitations = $isLeader
            ? WorkerTeamMember::where('team_id', $team->id)
                ->where('status', WorkerTeamMember::STATUS_PENDING)
                ->with('user')
                ->get()
            : collect();

        return view('worker.teams.show', [
            'team' => $team,
            'isLeader' => $isLeader,
            'isMember' => $isMember,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    /**
     * Update team settings.
     */
    public function update(Request $request, WorkerTeam $team): JsonResponse
    {
        // Verify leader
        if (! $team->hasLeader($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only team leaders can update team settings.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|min:3|max:100',
            'description' => 'nullable|string|max:1000',
            'max_members' => 'sometimes|integer|min:'.$team->member_count.'|max:50',
            'is_public' => 'sometimes|boolean',
            'requires_approval' => 'sometimes|boolean',
            'specializations' => 'nullable|array',
            'preferred_industries' => 'nullable|array',
            'min_reliability_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $team->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Team settings updated successfully.',
            'team' => $team->fresh(),
        ]);
    }

    /**
     * Invite a worker to the team.
     */
    public function invite(Request $request, WorkerTeam $team): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);

            $membership = $this->teamFormationService->inviteToTeam(
                $team,
                $user,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent to '.$user->name.'.',
                'invitation' => [
                    'id' => $membership->id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ],
                    'status' => $membership->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept a team invitation.
     */
    public function acceptInvitation(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = WorkerTeamMember::where('id', $membershipId)
                ->where('user_id', $request->user()->id)
                ->where('status', WorkerTeamMember::STATUS_PENDING)
                ->firstOrFail();

            $this->teamFormationService->acceptTeamInvitation($membership);

            return response()->json([
                'success' => true,
                'message' => 'You have joined '.$membership->team->name.'!',
                'redirect' => route('worker.teams.show', $membership->team),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Decline a team invitation.
     */
    public function declineInvitation(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = WorkerTeamMember::where('id', $membershipId)
                ->where('user_id', $request->user()->id)
                ->where('status', WorkerTeamMember::STATUS_PENDING)
                ->firstOrFail();

            $membership->decline();

            return response()->json([
                'success' => true,
                'message' => 'Invitation declined.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Leave a team.
     */
    public function leave(Request $request, WorkerTeam $team): JsonResponse
    {
        try {
            $this->teamFormationService->leaveTeam($team, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'You have left '.$team->name.'.',
                'redirect' => route('worker.teams.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove a member from team (leader action).
     */
    public function removeMember(Request $request, WorkerTeam $team, int $memberId): JsonResponse
    {
        try {
            $member = User::findOrFail($memberId);

            $this->teamFormationService->removeFromTeam(
                $team,
                $member,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => $member->name.' has been removed from the team.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Promote a member to leader.
     */
    public function promoteMember(Request $request, WorkerTeam $team, int $memberId): JsonResponse
    {
        if (! $team->hasLeader($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only team leaders can promote members.',
            ], 403);
        }

        $membership = WorkerTeamMember::where('team_id', $team->id)
            ->where('user_id', $memberId)
            ->where('status', WorkerTeamMember::STATUS_ACTIVE)
            ->first();

        if (! $membership) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found.',
            ], 404);
        }

        $membership->promoteToLeader();

        return response()->json([
            'success' => true,
            'message' => $membership->user->name.' is now a team leader.',
        ]);
    }

    /**
     * Apply to a shift as a team.
     */
    public function applyToShift(TeamShiftApplicationRequest $request): JsonResponse
    {
        try {
            $team = WorkerTeam::findOrFail($request->team_id);
            $shift = Shift::findOrFail($request->shift_id);

            $teamRequest = $this->teamFormationService->applyAsTeam(
                $team,
                $shift,
                $request->user(),
                $request->members_needed ?? 0
            );

            return response()->json([
                'success' => true,
                'message' => 'Team application submitted successfully.',
                'request' => [
                    'id' => $teamRequest->id,
                    'status' => $teamRequest->status,
                    'members_needed' => $teamRequest->members_needed,
                    'members_confirmed' => $teamRequest->members_confirmed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm participation in a team shift application.
     */
    public function confirmShiftParticipation(Request $request, int $requestId): JsonResponse
    {
        try {
            $teamRequest = TeamShiftRequest::findOrFail($requestId);

            $this->teamFormationService->confirmTeamShiftParticipation(
                $teamRequest,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Your participation has been confirmed.',
                'progress' => $teamRequest->fresh()->getProgressPercentage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a team shift application.
     */
    public function cancelShiftApplication(Request $request, int $requestId): JsonResponse
    {
        try {
            $teamRequest = TeamShiftRequest::findOrFail($requestId);

            // Verify user is team leader
            if (! $teamRequest->team->hasLeader($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only team leaders can cancel shift applications.',
                ], 403);
            }

            $teamRequest->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Team shift application cancelled.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get team shift applications.
     */
    public function shiftApplications(Request $request, WorkerTeam $team): JsonResponse
    {
        if (! $team->hasMember($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this team.',
            ], 403);
        }

        $applications = TeamShiftRequest::where('team_id', $team->id)
            ->with(['shift.business', 'requester'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'applications' => $applications,
        ]);
    }

    /**
     * Delete (deactivate) a team.
     */
    public function destroy(Request $request, WorkerTeam $team): JsonResponse
    {
        // Only creator can delete
        if ($team->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the team creator can delete the team.',
            ], 403);
        }

        $team->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'Team has been deactivated.',
            'redirect' => route('worker.teams.index'),
        ]);
    }

    /**
     * Search workers to invite to team.
     */
    public function searchMembersToInvite(Request $request, WorkerTeam $team): JsonResponse
    {
        if (! $team->hasLeader($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Only team leaders can invite members.',
            ], 403);
        }

        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'workers' => [],
            ]);
        }

        // Get existing member IDs
        $existingMemberIds = $team->memberships()
            ->whereIn('status', [WorkerTeamMember::STATUS_ACTIVE, WorkerTeamMember::STATUS_PENDING])
            ->pluck('user_id')
            ->toArray();

        $workers = User::where('user_type', 'worker')
            ->where('status', 'active')
            ->whereNotIn('id', $existingMemberIds)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'avatar', 'rating_as_worker', 'reliability_score']);

        // Filter by reliability requirement
        if ($team->min_reliability_score) {
            $workers = $workers->filter(function ($worker) use ($team) {
                return ($worker->reliability_score ?? 0) >= $team->min_reliability_score;
            });
        }

        return response()->json([
            'success' => true,
            'workers' => $workers->values()->map(function ($worker) {
                return [
                    'id' => $worker->id,
                    'name' => $worker->name,
                    'avatar' => $worker->avatar,
                    'rating' => $worker->rating_as_worker,
                    'reliability_score' => $worker->reliability_score,
                ];
            }),
        ]);
    }
}
