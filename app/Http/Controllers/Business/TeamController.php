<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Carbon\Carbon;

/**
 * BIZ-003: Team Management Controller
 *
 * Handles team member invitations, management, and permission updates.
 */
class TeamController extends Controller
{
    /**
     * Display team members list.
     */
    public function index()
    {
        $user = Auth::user();

        // Only business owners and administrators can view team list
        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to manage team members.');
        }

        $teamMembers = TeamMember::with(['user', 'invitedBy'])
            ->where('business_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $activeCount = $teamMembers->where('status', 'active')->count();
        $pendingCount = $teamMembers->where('status', 'pending')->count();

        return view('business.team.index', compact('teamMembers', 'activeCount', 'pendingCount'));
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

        $roles = [
            'administrator' => 'Administrator - Full access except billing',
            'location_manager' => 'Location Manager - Manage specific venues',
            'scheduler' => 'Scheduler - Create and manage shifts only',
            'viewer' => 'Viewer - Read-only access',
        ];

        // Get available venues for location manager assignment
        $venues = []; // TODO: Fetch from venues table when implemented

        return view('business.team.invite', compact('roles', 'venues'));
    }

    /**
     * Send team invitation.
     */
    public function invite(Request $request)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to invite team members.');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'role' => 'required|in:administrator,location_manager,scheduler,viewer',
            'venue_access' => 'nullable|array',
            'venue_access.*' => 'integer|exists:business_venues,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $email = $request->input('email');

        // Check if user exists
        $invitedUser = User::where('email', $email)->first();

        // Check if already a team member
        $existingMember = TeamMember::where('business_id', $user->id)
            ->where('user_id', $invitedUser?->id)
            ->first();

        if ($existingMember) {
            if ($existingMember->status === 'active') {
                return redirect()->back()->with('error', 'This user is already a team member.');
            } elseif ($existingMember->status === 'pending') {
                return redirect()->back()->with('error', 'An invitation has already been sent to this email.');
            } else {
                return redirect()->back()->with('error', 'This user was previously removed from your team.');
            }
        }

        // Create team member record
        $teamMember = TeamMember::create([
            'business_id' => $user->id,
            'user_id' => $invitedUser?->id,
            'invited_by' => $user->id,
            'role' => $request->input('role'),
            'venue_access' => $request->input('venue_access'),
            'status' => 'pending',
            'notes' => $request->input('notes'),
        ]);

        // Apply role permissions
        $teamMember->applyRolePermissions();

        // Generate invitation token
        $plainToken = $teamMember->generateInvitationToken();

        // Send invitation notification
        try {
            if ($invitedUser) {
                // User exists, send notification
                $invitedUser->notify(new TeamInvitationNotification($teamMember, $plainToken));
            } else {
                // User doesn't exist, send email invitation
                Mail::send('emails.team-invitation', [
                    'businessName' => $user->businessProfile->business_name ?? $user->name,
                    'role' => $teamMember->role_name,
                    'invitationUrl' => route('team.invitation.accept', ['token' => $plainToken]),
                    'expiresAt' => $teamMember->invitation_expires_at->format('M j, Y'),
                ], function ($message) use ($email, $user) {
                    $message->to($email)
                        ->subject("You've been invited to join " . ($user->businessProfile->business_name ?? $user->name));
                });
            }
        } catch (\Exception $e) {
            // Log error but don't fail the invitation
            \Log::error('Failed to send team invitation: ' . $e->getMessage());
        }

        return redirect()->route('business.team.index')
            ->with('success', 'Invitation sent successfully to ' . $email);
    }

    /**
     * Show team member details.
     */
    public function show($id)
    {
        $user = Auth::user();
        $teamMember = TeamMember::with(['user', 'invitedBy'])
            ->where('business_id', $user->id)
            ->findOrFail($id);

        return view('business.team.show', compact('teamMember'));
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

        $teamMember = TeamMember::with('user')
            ->where('business_id', $user->id)
            ->findOrFail($id);

        // Cannot edit owner role
        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot edit business owner role.');
        }

        $roles = [
            'administrator' => 'Administrator - Full access except billing',
            'location_manager' => 'Location Manager - Manage specific venues',
            'scheduler' => 'Scheduler - Create and manage shifts only',
            'viewer' => 'Viewer - Read-only access',
        ];

        $venues = []; // TODO: Fetch from venues table

        return view('business.team.edit', compact('teamMember', 'roles', 'venues'));
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

        $teamMember = TeamMember::where('business_id', $user->id)->findOrFail($id);

        // Cannot edit owner role
        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot edit business owner role.');
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:administrator,location_manager,scheduler,viewer',
            'venue_access' => 'nullable|array',
            'venue_access.*' => 'integer|exists:business_venues,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update role and venue access
        $teamMember->role = $request->input('role');
        $teamMember->venue_access = $request->input('venue_access');
        $teamMember->notes = $request->input('notes');
        $teamMember->save();

        // Reapply role permissions
        $teamMember->applyRolePermissions();

        return redirect()->route('business.team.show', $teamMember->id)
            ->with('success', 'Team member updated successfully.');
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

        $teamMember = TeamMember::with('user')
            ->where('business_id', $user->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        // Generate new token
        $plainToken = $teamMember->resendInvitation();

        if (!$plainToken) {
            return redirect()->back()->with('error', 'Cannot resend invitation. Member status is not pending.');
        }

        // Send notification
        try {
            if ($teamMember->user) {
                $teamMember->user->notify(new TeamInvitationNotification($teamMember, $plainToken));
            } else {
                // Email-only invitation (user doesn't have account yet)
                Mail::send('emails.team-invitation', [
                    'businessName' => $user->businessProfile->business_name ?? $user->name,
                    'role' => $teamMember->role_name,
                    'invitationUrl' => route('team.invitation.accept', ['token' => $plainToken]),
                    'expiresAt' => $teamMember->invitation_expires_at->format('M j, Y'),
                ], function ($message) use ($teamMember, $user) {
                    $message->to($teamMember->user->email)
                        ->subject("Reminder: You've been invited to join " . ($user->businessProfile->business_name ?? $user->name));
                });
            }
        } catch (\Exception $e) {
            \Log::error('Failed to resend team invitation: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Invitation resent successfully.');
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

        $teamMember = TeamMember::where('business_id', $user->id)->findOrFail($id);

        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot suspend business owner.');
        }

        $teamMember->suspend($request->input('reason'));

        return redirect()->back()->with('success', 'Team member suspended.');
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

        $teamMember = TeamMember::where('business_id', $user->id)->findOrFail($id);
        $teamMember->reactivate();

        return redirect()->back()->with('success', 'Team member reactivated.');
    }

    /**
     * Revoke team member access.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();

        if (!$this->canManageTeam($user)) {
            abort(403, 'You do not have permission to remove team members.');
        }

        $teamMember = TeamMember::where('business_id', $user->id)->findOrFail($id);

        if ($teamMember->role === 'owner') {
            abort(403, 'Cannot remove business owner.');
        }

        $reason = $request->input('reason', 'Removed by administrator');
        $teamMember->revoke($reason);

        return redirect()->route('business.team.index')
            ->with('success', 'Team member removed successfully.');
    }

    /**
     * Accept team invitation (public route).
     */
    public function acceptInvitation(Request $request, $token)
    {
        $hashedToken = hash('sha256', $token);

        $teamMember = TeamMember::where('invitation_token', $hashedToken)
            ->where('status', 'pending')
            ->first();

        if (!$teamMember) {
            return redirect()->route('login')->with('error', 'Invalid invitation link.');
        }

        if (!$teamMember->isInvitationValid()) {
            return redirect()->route('login')->with('error', 'This invitation has expired.');
        }

        // If user is not logged in, redirect to login/register
        if (!Auth::check()) {
            session(['team_invitation_token' => $token]);
            return redirect()->route('register')->with('info', 'Please create an account or login to accept the invitation.');
        }

        $user = Auth::user();

        // Update team member user_id if not set
        if (!$teamMember->user_id) {
            $teamMember->user_id = $user->id;
        }

        // Verify it's the correct user
        if ($teamMember->user_id !== $user->id) {
            return redirect()->route('dashboard')->with('error', 'This invitation is for a different user.');
        }

        // Accept invitation
        if ($teamMember->acceptInvitation()) {
            return redirect()->route('business.team.dashboard')
                ->with('success', 'Welcome to the team! You can now access the business dashboard.');
        }

        return redirect()->route('dashboard')->with('error', 'Failed to accept invitation.');
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
            return redirect()->route('dashboard')->with('error', 'You are not a member of any team.');
        }

        return view('business.team.dashboard', compact('teamMemberships'));
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
            ->whereIn('role', ['owner', 'administrator'])
            ->first();

        return !is_null($teamMember);
    }
}
