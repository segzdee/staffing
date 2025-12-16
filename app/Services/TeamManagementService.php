<?php

namespace App\Services;

use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\TeamActivity;
use App\Models\TeamPermission;
use App\Models\Venue;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Notifications\InvitationAcceptedNotification;
use App\Notifications\TeamMemberSuspendedNotification;
use App\Notifications\RoleChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * BIZ-REG-008: Team Management Service
 *
 * Handles team member operations including:
 * - Team member invitations
 * - Role and permission management
 * - Venue access control
 * - Activity tracking
 */
class TeamManagementService
{
    /**
     * Invite a new team member.
     */
    public function inviteTeamMember(
        User $business,
        User $invitedBy,
        string $email,
        string $role,
        ?array $venueAccess = null,
        ?string $message = null,
        ?array $customPermissions = null
    ): TeamInvitation {
        return DB::transaction(function () use ($business, $invitedBy, $email, $role, $venueAccess, $message, $customPermissions) {
            // Validate role hierarchy
            $inviterMember = $this->getTeamMember($invitedBy, $business->id);
            if ($inviterMember && !$inviterMember->canInviteRole($role)) {
                throw new \Exception('You cannot invite a team member with a higher role than your own.');
            }

            // Check for existing invitation
            $existingInvitation = TeamInvitation::forBusiness($business->id)
                ->forEmail($email)
                ->pending()
                ->first();

            if ($existingInvitation) {
                throw new \Exception('An invitation has already been sent to this email address.');
            }

            // Check if user is already a team member
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $existingMember = TeamMember::where('business_id', $business->id)
                    ->where('user_id', $existingUser->id)
                    ->whereIn('status', ['active', 'pending'])
                    ->first();

                if ($existingMember) {
                    throw new \Exception('This user is already a team member or has a pending invitation.');
                }
            }

            // Validate venue access
            if ($venueAccess) {
                $validVenues = Venue::forBusiness($business->businessProfile->id)
                    ->whereIn('id', $venueAccess)
                    ->pluck('id')
                    ->toArray();

                if (count($validVenues) !== count($venueAccess)) {
                    throw new \Exception('One or more selected venues are invalid.');
                }
            }

            // Create invitation
            $invitation = TeamInvitation::createInvitation(
                $business->id,
                $invitedBy->id,
                $email,
                $role,
                $venueAccess,
                $message,
                $customPermissions
            );

            // Log activity
            TeamActivity::logInvitationSent($invitation, $invitedBy);

            // Send notification
            $this->sendInvitationEmail($invitation);

            return $invitation;
        });
    }

    /**
     * Validate an invitation token.
     */
    public function validateInvitation(string $token): ?TeamInvitation
    {
        $invitation = TeamInvitation::findByToken($token);

        if (!$invitation) {
            return null;
        }

        if (!$invitation->canBeAccepted()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Accept a team invitation.
     */
    public function acceptInvitation(
        TeamInvitation $invitation,
        User $user,
        ?string $ip = null,
        ?string $userAgent = null
    ): TeamMember {
        return DB::transaction(function () use ($invitation, $user, $ip, $userAgent) {
            // Accept invitation and create team member
            $teamMember = $invitation->accept($user, $ip, $userAgent);

            // Log activity
            TeamActivity::log(
                $invitation->business_id,
                $user->id,
                'invitation_accepted',
                "{$user->name} accepted invitation and joined as {$teamMember->role_name}",
                [
                    'role' => $teamMember->role,
                    'invitation_id' => $invitation->id,
                ],
                $teamMember->id,
                null,
                TeamMember::class,
                $teamMember->id
            );

            // Notify business owner
            $business = User::find($invitation->business_id);
            if ($business) {
                try {
                    $business->notify(new InvitationAcceptedNotification($teamMember));
                } catch (\Exception $e) {
                    Log::warning('Failed to send invitation accepted notification: ' . $e->getMessage());
                }
            }

            return $teamMember;
        });
    }

    /**
     * Decline a team invitation.
     */
    public function declineInvitation(TeamInvitation $invitation): void
    {
        $invitation->decline();

        TeamActivity::log(
            $invitation->business_id,
            $invitation->user_id ?? 0,
            'invitation_declined',
            "Invitation to {$invitation->email} was declined",
            ['invitation_id' => $invitation->id],
            null,
            null,
            TeamInvitation::class,
            $invitation->id
        );
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(TeamInvitation $invitation, User $resentBy): string
    {
        if (!$invitation->can_resend) {
            throw new \Exception('Maximum resend attempts reached.');
        }

        $token = $invitation->regenerateToken();

        // Log activity
        TeamActivity::log(
            $invitation->business_id,
            $resentBy->id,
            'invitation_resent',
            "Resent invitation to {$invitation->email}",
            ['invitation_id' => $invitation->id, 'resend_count' => $invitation->resend_count],
            null,
            null,
            TeamInvitation::class,
            $invitation->id
        );

        // Send notification
        $this->sendInvitationEmail($invitation);

        return $token;
    }

    /**
     * Revoke an invitation.
     */
    public function revokeInvitation(TeamInvitation $invitation, User $revokedBy, ?string $reason = null): void
    {
        $invitation->revoke($reason);

        TeamActivity::log(
            $invitation->business_id,
            $revokedBy->id,
            'invitation_revoked',
            "Revoked invitation to {$invitation->email}",
            ['invitation_id' => $invitation->id, 'reason' => $reason],
            null,
            null,
            TeamInvitation::class,
            $invitation->id
        );
    }

    /**
     * Update team member role.
     */
    public function updateMemberRole(
        TeamMember $member,
        string $newRole,
        User $changedBy
    ): TeamMember {
        return DB::transaction(function () use ($member, $newRole, $changedBy) {
            // Validate role change
            if ($member->role === 'owner') {
                throw new \Exception('Cannot change the owner role.');
            }

            // Check if changer has permission
            $changerMember = $this->getTeamMember($changedBy, $member->business_id);
            if (!$changedBy->user_type === 'business' && !$changerMember?->canInviteRole($newRole)) {
                throw new \Exception('You cannot assign a role higher than your own.');
            }

            $oldRole = $member->role;

            // Update role
            $member->role = $newRole;
            $member->save();

            // Apply new role permissions
            $member->applyRolePermissions();

            // Log activity
            TeamActivity::logRoleChange($member, $oldRole, $newRole, $changedBy);

            // Notify member
            try {
                $member->user->notify(new RoleChangedNotification($member, $oldRole, $newRole));
            } catch (\Exception $e) {
                Log::warning('Failed to send role changed notification: ' . $e->getMessage());
            }

            return $member->fresh();
        });
    }

    /**
     * Update team member venue access.
     */
    public function updateVenueAccess(
        TeamMember $member,
        ?array $venueIds,
        User $changedBy
    ): TeamMember {
        // Validate venues
        if ($venueIds !== null) {
            $businessProfile = $member->business->businessProfile;
            $validVenues = Venue::forBusiness($businessProfile->id)
                ->whereIn('id', $venueIds)
                ->pluck('id')
                ->toArray();

            if (count($validVenues) !== count($venueIds)) {
                throw new \Exception('One or more selected venues are invalid.');
            }
        }

        $oldAccess = $member->venue_access;
        $member->venue_access = $venueIds;
        $member->save();

        // Log activity
        TeamActivity::log(
            $member->business_id,
            $changedBy->id,
            'venue_access_updated',
            "Updated venue access for {$member->user->name}",
            [
                'old_access' => $oldAccess,
                'new_access' => $venueIds,
            ],
            $member->id,
            null,
            TeamMember::class,
            $member->id
        );

        return $member->fresh();
    }

    /**
     * Suspend a team member.
     */
    public function suspendMember(
        TeamMember $member,
        User $suspendedBy,
        ?string $reason = null
    ): TeamMember {
        if ($member->role === 'owner') {
            throw new \Exception('Cannot suspend the business owner.');
        }

        $member->suspend($reason, $suspendedBy->id);

        // Log activity
        TeamActivity::log(
            $member->business_id,
            $suspendedBy->id,
            'member_suspended',
            "Suspended {$member->user->name}",
            ['reason' => $reason],
            $member->id,
            null,
            TeamMember::class,
            $member->id
        );

        // Notify member
        try {
            $member->user->notify(new TeamMemberSuspendedNotification($member, $reason));
        } catch (\Exception $e) {
            Log::warning('Failed to send suspension notification: ' . $e->getMessage());
        }

        return $member->fresh();
    }

    /**
     * Reactivate a suspended team member.
     */
    public function reactivateMember(TeamMember $member, User $reactivatedBy): TeamMember
    {
        $member->reactivate();

        // Log activity
        TeamActivity::log(
            $member->business_id,
            $reactivatedBy->id,
            'member_reactivated',
            "Reactivated {$member->user->name}",
            null,
            $member->id,
            null,
            TeamMember::class,
            $member->id
        );

        return $member->fresh();
    }

    /**
     * Remove a team member.
     */
    public function removeMember(
        TeamMember $member,
        User $removedBy,
        string $reason = 'Removed by administrator'
    ): void {
        if ($member->role === 'owner') {
            throw new \Exception('Cannot remove the business owner.');
        }

        // Check if remover outranks the member
        $removerMember = $this->getTeamMember($removedBy, $member->business_id);
        if ($removerMember && !$removerMember->outranks($member) && $removedBy->id !== $member->business_id) {
            throw new \Exception('You cannot remove a team member with an equal or higher role.');
        }

        $member->revoke($reason);

        // Log activity
        TeamActivity::log(
            $member->business_id,
            $removedBy->id,
            'member_removed',
            "Removed {$member->user->name} from team",
            ['reason' => $reason],
            $member->id,
            null,
            TeamMember::class,
            $member->id
        );
    }

    /**
     * Track an activity for a team member.
     */
    public function trackActivity(
        TeamMember $member,
        string $activityType,
        string $description,
        ?array $metadata = null,
        ?int $venueId = null,
        ?string $subjectType = null,
        ?int $subjectId = null
    ): TeamActivity {
        return TeamActivity::log(
            $member->business_id,
            $member->user_id,
            $activityType,
            $description,
            $metadata,
            $member->id,
            $venueId,
            $subjectType,
            $subjectId
        );
    }

    /**
     * Get activity log for a team member.
     */
    public function getMemberActivity(TeamMember $member, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return TeamActivity::forTeamMember($member->id)
            ->with(['user', 'venue'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity log for a business.
     */
    public function getBusinessActivity(int $businessId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = TeamActivity::forBusiness($businessId)
            ->with(['user', 'teamMember.user', 'venue']);

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['team_member_id'])) {
            $query->forTeamMember($filters['team_member_id']);
        }

        if (isset($filters['activity_type'])) {
            $query->ofType($filters['activity_type']);
        }

        if (isset($filters['category'])) {
            $query->inCategory($filters['category']);
        }

        if (isset($filters['venue_id'])) {
            $query->forVenue($filters['venue_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        } elseif (isset($filters['days'])) {
            $query->recent($filters['days']);
        }

        $limit = $filters['limit'] ?? 100;

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get valid roles that a user can invite.
     */
    public function getValidRolesForInviter(User $inviter, int $businessId): array
    {
        // Business owner can invite all roles
        if ($inviter->id === $businessId || $inviter->user_type === 'business') {
            return TeamPermission::getInvitableRoles();
        }

        // Get inviter's team member record
        $member = $this->getTeamMember($inviter, $businessId);

        if (!$member) {
            return [];
        }

        return TeamPermission::getInvitableRolesFor($member->role);
    }

    /**
     * Check if a user has a specific permission.
     */
    public function checkPermission(User $user, int $businessId, string $permission): bool
    {
        // Business owner always has all permissions
        if ($user->id === $businessId) {
            return true;
        }

        $member = $this->getTeamMember($user, $businessId);

        if (!$member || !$member->isActive()) {
            return false;
        }

        return $member->hasPermission($permission);
    }

    /**
     * Get team member record for a user.
     */
    public function getTeamMember(User $user, int $businessId): ?TeamMember
    {
        // If user is the business owner, return a virtual owner member
        if ($user->id === $businessId) {
            $member = new TeamMember();
            $member->business_id = $businessId;
            $member->user_id = $user->id;
            $member->role = 'owner';
            $member->status = 'active';
            return $member;
        }

        return TeamMember::where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get all team members for a business.
     */
    public function getTeamMembers(int $businessId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = TeamMember::forBusiness($businessId)
            ->with(['user', 'invitedBy', 'managedVenues']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['role'])) {
            $query->withRole($filters['role']);
        }

        if (isset($filters['venue_id'])) {
            $query->withVenueAccess($filters['venue_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get pending invitations for a business.
     */
    public function getPendingInvitations(int $businessId): \Illuminate\Database\Eloquent\Collection
    {
        return TeamInvitation::forBusiness($businessId)
            ->pending()
            ->with(['inviter', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get team statistics for a business.
     */
    public function getTeamStatistics(int $businessId): array
    {
        $members = TeamMember::forBusiness($businessId)->get();
        $invitations = TeamInvitation::forBusiness($businessId)->get();

        return [
            'total_members' => $members->count(),
            'active_members' => $members->where('status', 'active')->count(),
            'pending_invitations' => $invitations->where('status', 'pending')->count(),
            'suspended_members' => $members->where('status', 'suspended')->count(),
            'by_role' => $members->groupBy('role')->map->count(),
            'recently_active' => $members->where('last_active_at', '>=', now()->subDays(7))->count(),
            'total_shifts_posted' => $members->sum('shifts_posted'),
            'total_applications_processed' => $members->sum('applications_processed'),
        ];
    }

    /**
     * Send invitation email.
     */
    protected function sendInvitationEmail(TeamInvitation $invitation): void
    {
        try {
            if ($invitation->user) {
                // User exists, send notification
                $invitation->user->notify(new TeamInvitationNotification($invitation));
            } else {
                // User doesn't exist, send email directly
                $business = $invitation->business;
                $businessName = $business->businessProfile->business_name ?? $business->name;

                Mail::send('emails.team-invitation', [
                    'businessName' => $businessName,
                    'role' => $invitation->role_label,
                    'invitationUrl' => $invitation->getInvitationUrl(),
                    'expiresAt' => $invitation->expires_at->format('M j, Y'),
                    'message' => $invitation->message,
                ], function ($mail) use ($invitation, $businessName) {
                    $mail->to($invitation->email)
                        ->subject("You've been invited to join {$businessName}");
                });
            }
        } catch (\Exception $e) {
            Log::error('Failed to send team invitation email: ' . $e->getMessage());
        }
    }
}
