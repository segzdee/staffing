<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\TeamInvitationRequest;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\TeamActivity;
use App\Models\TeamPermission;
use App\Models\Venue;
use App\Models\User;
use App\Services\TeamManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * BIZ-REG-008: Team Management Controller
 *
 * Handles team member invitations, management, and permission updates.
 */
class TeamController extends Controller
{
    protected TeamManagementService $teamService;

    public function __construct(TeamManagementService $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Display team members list.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Only business owners and administrators can view team list
        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to manage team members.');
        }

        $filters = [
            'status' => $request->get('status'),
            'role' => $request->get('role'),
            'venue_id' => $request->get('venue_id'),
            'search' => $request->get('search'),
        ];

        $teamMembers = $this->teamService->getTeamMembers($user->id, $filters);
        $pendingInvitations = $this->teamService->getPendingInvitations($user->id);
        $statistics = $this->teamService->getTeamStatistics($user->id);

        $roles = TeamPermission::getInvitableRoles();
        $venues = Venue::forBusiness($user->businessProfile->id)->active()->get();

        return view('business.team.index', compact(
            'teamMembers',
            'pendingInvitations',
            'statistics',
            'roles',
            'venues',
            'filters'
        ));
    }

    /**
     * Show invitation form.
     */
    public function create()
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to invite team members.');
        }

        $roles = $this->teamService->getValidRolesForInviter($user, $user->id);
        $venues = Venue::forBusiness($user->businessProfile->id)->active()->get();
        $permissionMatrix = TeamPermission::getPermissionMatrix();

        return view('business.team.invite', compact('roles', 'venues', 'permissionMatrix'));
    }

    /**
     * Send team invitation.
     */
    public function invite(TeamInvitationRequest $request)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to invite team members.');
        }

        try {
            $invitation = $this->teamService->inviteTeamMember(
                $user,
                $user,
                $request->input('email'),
                $request->input('role'),
                $request->input('venue_access'),
                $request->input('message'),
                $request->input('custom_permissions')
            );

            return redirect()->route('business.team.index')
                ->with('success', 'Invitation sent successfully to ' . $request->input('email'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show team member details.
     */
    public function show($id)
    {
        $user = Auth::user();
        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        $activities = $this->teamService->getMemberActivity($teamMember, 50);
        $venues = Venue::forBusiness($user->businessProfile->id)->get();

        return view('business.team.show', compact('teamMember', 'activities', 'venues'));
    }

    /**
     * Show edit form for team member.
     */
    public function edit($id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to edit team members.');
        }

        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        // Cannot edit owner role
        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot edit business owner role.');
        }

        $roles = $this->teamService->getValidRolesForInviter($user, $user->id);
        $venues = Venue::forBusiness($user->businessProfile->id)->active()->get();
        $permissionMatrix = TeamPermission::getPermissionMatrix();

        return view('business.team.edit', compact('teamMember', 'roles', 'venues', 'permissionMatrix'));
    }

    /**
     * Update team member role and permissions.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to update team members.');
        }

        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        // Cannot edit owner role
        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot edit business owner role.');
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,manager,scheduler,viewer',
            'venue_access' => 'nullable|array',
            'venue_access.*' => 'integer|exists:venues,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Update role if changed
            if ($request->input('role') !== $teamMember->role) {
                $this->teamService->updateMemberRole($teamMember, $request->input('role'), $user);
            }

            // Update venue access
            $this->teamService->updateVenueAccess($teamMember, $request->input('venue_access'), $user);

            // Update notes
            if ($request->has('notes')) {
                $teamMember->update(['notes' => $request->input('notes')]);
            }

            return redirect()->route('business.team.show', $teamMember->id)
                ->with('success', 'Team member updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Resend invitation to pending team member.
     */
    public function resendInvitation($id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to resend invitations.');
        }

        // Check both team_members and team_invitations
        $invitation = TeamInvitation::forBusiness($user->id)->find($id);

        if (!$invitation) {
            // Fallback to team member with pending status
            $teamMember = TeamMember::forBusiness($user->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $plainToken = $teamMember->resendInvitation();

            if (!$plainToken) {
                return redirect()->back()->with('error', 'Cannot resend invitation.');
            }
        } else {
            try {
                $this->teamService->resendInvitation($invitation, $user);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Invitation resent successfully.');
    }

    /**
     * Revoke a pending invitation.
     */
    public function revokeInvitation(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to revoke invitations.');
        }

        $invitation = TeamInvitation::forBusiness($user->id)
            ->pending()
            ->findOrFail($id);

        try {
            $this->teamService->revokeInvitation($invitation, $user, $request->input('reason'));

            return redirect()->back()->with('success', 'Invitation revoked.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Suspend team member.
     */
    public function suspend(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to suspend team members.');
        }

        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        try {
            $this->teamService->suspendMember($teamMember, $user, $request->input('reason'));

            return redirect()->back()->with('success', 'Team member suspended.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reactivate suspended team member.
     */
    public function reactivate($id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to reactivate team members.');
        }

        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        try {
            $this->teamService->reactivateMember($teamMember, $user);

            return redirect()->back()->with('success', 'Team member reactivated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove team member access.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to remove team members.');
        }

        $teamMember = $this->getTeamMemberForBusiness($id, $user);

        try {
            $reason = $request->input('reason', 'Removed by administrator');
            $this->teamService->removeMember($teamMember, $user, $reason);

            return redirect()->route('business.team.index')
                ->with('success', 'Team member removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Accept team invitation (public route).
     */
    public function acceptInvitation(Request $request, $token)
    {
        $invitation = $this->teamService->validateInvitation($token);

        if (!$invitation) {
            // Try legacy team member token
            $hashedToken = hash('sha256', $token);
            $teamMember = TeamMember::where('invitation_token', $hashedToken)
                ->where('status', 'pending')
                ->first();

            if (!$teamMember) {
                return redirect()->route('login')
                    ->with('error', 'Invalid or expired invitation link.');
            }

            if (!$teamMember->isInvitationValid()) {
                return redirect()->route('login')
                    ->with('error', 'This invitation has expired.');
            }

            // Handle legacy flow
            return $this->handleLegacyInvitation($teamMember, $token);
        }

        // If user is not logged in, redirect to login/register
        if (!Auth::check()) {
            session(['team_invitation_token' => $token]);
            return redirect()->route('register')
                ->with('info', 'Please create an account or login to accept the invitation.');
        }

        $user = Auth::user();

        try {
            $teamMember = $this->teamService->acceptInvitation(
                $invitation,
                $user,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('business.team.dashboard')
                ->with('success', 'Welcome to the team! You can now access the business dashboard.');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Decline team invitation.
     */
    public function declineInvitation($token)
    {
        $invitation = $this->teamService->validateInvitation($token);

        if (!$invitation) {
            return redirect()->route('home')
                ->with('error', 'Invalid or expired invitation link.');
        }

        try {
            $this->teamService->declineInvitation($invitation);

            return redirect()->route('home')
                ->with('success', 'You have declined the team invitation.');
        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show team dashboard for team members (non-owners).
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get all businesses where user is a team member
        $teamMemberships = TeamMember::with(['business', 'business.businessProfile'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        if ($teamMemberships->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not a member of any team.');
        }

        return view('business.team.dashboard', compact('teamMemberships'));
    }

    /**
     * Get activity log for team or member.
     */
    public function getActivity(Request $request, $id = null)
    {
        $user = Auth::user();

        if (!$this->canViewActivity($user)) {
            abort(403, 'You do not have permission to view activity logs.');
        }

        $filters = [
            'team_member_id' => $id,
            'activity_type' => $request->get('type'),
            'category' => $request->get('category'),
            'venue_id' => $request->get('venue_id'),
            'days' => $request->get('days', 30),
            'limit' => $request->get('limit', 100),
        ];

        if ($request->has('start_date') && $request->has('end_date')) {
            $filters['start_date'] = $request->get('start_date');
            $filters['end_date'] = $request->get('end_date');
            unset($filters['days']);
        }

        $activities = $this->teamService->getBusinessActivity($user->id, $filters);
        $activityTypes = TeamActivity::ACTIVITY_TYPES;
        $categories = TeamActivity::CATEGORIES;

        if ($request->wantsJson()) {
            return response()->json([
                'activities' => $activities,
                'types' => $activityTypes,
                'categories' => $categories,
            ]);
        }

        return view('business.team.activity', compact('activities', 'activityTypes', 'categories', 'filters'));
    }

    /**
     * Get permission matrix.
     */
    public function getPermissions()
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to view permissions.');
        }

        $matrix = TeamPermission::getPermissionMatrix();
        $roles = TeamPermission::getRoles();
        $categories = TeamPermission::getPermissionsByCategory();

        return response()->json([
            'matrix' => $matrix,
            'roles' => $roles,
            'categories' => $categories,
        ]);
    }

    /**
     * Handle legacy invitation (TeamMember-based).
     */
    protected function handleLegacyInvitation(TeamMember $teamMember, string $token)
    {
        if (!Auth::check()) {
            session(['team_invitation_token' => $token]);
            return redirect()->route('register')
                ->with('info', 'Please create an account or login to accept the invitation.');
        }

        $user = Auth::user();

        // Update team member user_id if not set
        if (!$teamMember->user_id) {
            $teamMember->user_id = $user->id;
        }

        // Verify it's the correct user
        if ($teamMember->user_id !== $user->id) {
            return redirect()->route('dashboard')
                ->with('error', 'This invitation is for a different user.');
        }

        // Accept invitation
        if ($teamMember->acceptInvitation()) {
            return redirect()->route('business.team.dashboard')
                ->with('success', 'Welcome to the team!');
        }

        return redirect()->route('dashboard')
            ->with('error', 'Failed to accept invitation.');
    }

    /**
     * Get team member for the business.
     */
    protected function getTeamMemberForBusiness($id, $user): TeamMember
    {
        return TeamMember::with(['user', 'invitedBy', 'managedVenues'])
            ->where('business_id', $user->id)
            ->findOrFail($id);
    }

    /**
     * Check if user can manage team (owner or administrator).
     */
    protected function canManageTeam($user): bool
    {
        // Business owner can always manage team
        if ($user->user_type === 'business') {
            return true;
        }

        // Check if user is an administrator of any business
        $teamMember = TeamMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('can_manage_team', true)
            ->first();

        return !is_null($teamMember);
    }

    /**
     * Check if user can view activity logs.
     */
    protected function canViewActivity($user): bool
    {
        // Business owner can always view activity
        if ($user->user_type === 'business') {
            return true;
        }

        // Check if user has view_activity permission
        $teamMember = TeamMember::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('can_view_activity', true)
            ->first();

        return !is_null($teamMember);
    }
}
