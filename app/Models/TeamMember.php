<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * BIZ-REG-008: Team Member Model
 *
 * Represents a team member within a business organization.
 * Handles role-based permissions, venue access control, and activity tracking.
 *
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property int|null $invited_by
 * @property string $role
 * @property array|null $venue_access
 * @property bool $can_post_shifts
 * @property bool $can_edit_shifts
 * @property bool $can_cancel_shifts
 * @property bool $can_approve_applications
 * @property bool $can_manage_workers
 * @property bool $can_view_payments
 * @property bool $can_process_payments
 * @property bool $can_manage_venues
 * @property bool $can_manage_team
 * @property bool $can_view_analytics
 * @property bool $can_manage_settings
 * @property bool $can_manage_billing
 * @property bool $can_view_activity
 * @property bool $can_manage_favorites
 * @property bool $can_manage_integrations
 * @property bool $can_view_reports
 * @property string $status
 * @property string|null $invitation_token
 * @property int|null $invitation_id
 * @property \Illuminate\Support\Carbon|null $invited_at
 * @property \Illuminate\Support\Carbon|null $invitation_expires_at
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property int $shifts_posted
 * @property int $shifts_edited
 * @property int $applications_processed
 * @property int $workers_approved
 * @property int $shifts_cancelled
 * @property int $venues_managed
 * @property int $login_count
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $notes
 * @property string|null $revocation_reason
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property int|null $suspended_by
 * @property \Illuminate\Support\Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property bool $requires_2fa
 */
class TeamMember extends Model
{
    use HasFactory;

    /**
     * Available roles.
     */
    public const ROLES = [
        'owner' => 'Owner',
        'admin' => 'Administrator',
        'administrator' => 'Administrator',
        'manager' => 'Manager',
        'location_manager' => 'Location Manager',
        'scheduler' => 'Scheduler',
        'viewer' => 'Viewer',
    ];

    /**
     * Role hierarchy (higher index = lower privileges).
     */
    public const ROLE_HIERARCHY = [
        'owner' => 0,
        'admin' => 1,
        'administrator' => 1,
        'manager' => 2,
        'location_manager' => 2,
        'scheduler' => 3,
        'viewer' => 4,
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'invited_by',
        'role',
        'venue_access',
        'can_post_shifts',
        'can_edit_shifts',
        'can_cancel_shifts',
        'can_approve_applications',
        'can_manage_workers',
        'can_view_payments',
        'can_process_payments',
        'can_manage_venues',
        'can_manage_team',
        'can_view_analytics',
        'can_manage_settings',
        'can_manage_billing',
        'can_view_activity',
        'can_manage_favorites',
        'can_manage_integrations',
        'can_view_reports',
        'status',
        'invitation_token',
        'invitation_id',
        'invited_at',
        'invitation_expires_at',
        'joined_at',
        'last_active_at',
        'shifts_posted',
        'shifts_edited',
        'applications_processed',
        'workers_approved',
        'shifts_cancelled',
        'venues_managed',
        'login_count',
        'last_login_at',
        'last_login_ip',
        'notes',
        'revocation_reason',
        'revoked_at',
        'suspended_by',
        'suspended_at',
        'suspension_reason',
        'requires_2fa',
    ];

    protected $casts = [
        'venue_access' => 'array',
        'can_post_shifts' => 'boolean',
        'can_edit_shifts' => 'boolean',
        'can_cancel_shifts' => 'boolean',
        'can_approve_applications' => 'boolean',
        'can_manage_workers' => 'boolean',
        'can_view_payments' => 'boolean',
        'can_process_payments' => 'boolean',
        'can_manage_venues' => 'boolean',
        'can_manage_team' => 'boolean',
        'can_view_analytics' => 'boolean',
        'can_manage_settings' => 'boolean',
        'can_manage_billing' => 'boolean',
        'can_view_activity' => 'boolean',
        'can_manage_favorites' => 'boolean',
        'can_manage_integrations' => 'boolean',
        'can_view_reports' => 'boolean',
        'requires_2fa' => 'boolean',
        'invited_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'last_login_at' => 'datetime',
        'revoked_at' => 'datetime',
        'suspended_at' => 'datetime',
        'shifts_posted' => 'integer',
        'shifts_edited' => 'integer',
        'applications_processed' => 'integer',
        'workers_approved' => 'integer',
        'shifts_cancelled' => 'integer',
        'venues_managed' => 'integer',
        'login_count' => 'integer',
    ];

    protected $appends = [
        'role_name',
        'status_color',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business (user) this team member belongs to.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the business profile.
     */
    public function businessProfile()
    {
        return $this->hasOneThrough(
            BusinessProfile::class,
            User::class,
            'id',
            'user_id',
            'business_id',
            'id'
        );
    }

    /**
     * Get the user who is the team member.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who invited this team member.
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who suspended this member.
     */
    public function suspendedBy()
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Get the invitation this member came from.
     */
    public function invitation()
    {
        return $this->belongsTo(TeamInvitation::class, 'invitation_id');
    }

    /**
     * Get venues this member manages.
     */
    public function managedVenues()
    {
        return $this->belongsToMany(Venue::class, 'venue_managers')
            ->withPivot([
                'is_primary',
                'can_post_shifts',
                'can_edit_shifts',
                'can_cancel_shifts',
                'can_approve_workers',
                'can_manage_venue_settings',
                'notify_new_applications',
                'notify_shift_changes',
                'notify_worker_checkins',
            ])
            ->withTimestamps();
    }

    /**
     * Get venue manager records.
     */
    public function venueManagers()
    {
        return $this->hasMany(VenueManager::class);
    }

    /**
     * Get activities by this member.
     */
    public function activities()
    {
        return $this->hasMany(TeamActivity::class);
    }

    // =========================================
    // Role-Based Permissions
    // =========================================

    /**
     * Define permission sets for each role.
     */
    public static function getRolePermissions(string $role): array
    {
        // Normalize role name
        $role = str_replace('administrator', 'admin', $role);
        $role = str_replace('location_manager', 'manager', $role);

        return TeamPermission::ROLE_PERMISSIONS[$role] ?? TeamPermission::ROLE_PERMISSIONS['viewer'];
    }

    /**
     * Apply role permissions to team member.
     */
    public function applyRolePermissions(): void
    {
        $permissions = self::getRolePermissions($this->role);

        // Map permission slugs to model attributes
        $attributeMap = [
            'manage_team' => 'can_manage_team',
            'manage_venues' => 'can_manage_venues',
            'manage_billing' => 'can_manage_billing',
            'post_shifts' => 'can_post_shifts',
            'edit_shifts' => 'can_edit_shifts',
            'cancel_shifts' => 'can_cancel_shifts',
            'approve_workers' => 'can_approve_applications',
            'view_reports' => 'can_view_reports',
            'manage_favorites' => 'can_manage_favorites',
            'manage_integrations' => 'can_manage_integrations',
            'view_activity' => 'can_view_activity',
        ];

        $updates = [];
        foreach ($permissions as $slug => $granted) {
            if (isset($attributeMap[$slug])) {
                $updates[$attributeMap[$slug]] = $granted;
            }
        }

        // Set additional permissions based on role
        $updates['can_manage_workers'] = $permissions['approve_workers'] ?? false;
        $updates['can_view_payments'] = in_array($this->role, ['owner', 'admin', 'administrator', 'manager', 'location_manager']);
        $updates['can_process_payments'] = $this->role === 'owner';
        $updates['can_view_analytics'] = $permissions['view_reports'] ?? false;
        $updates['can_manage_settings'] = $this->role === 'owner';

        $this->fill($updates);
        $this->save();
    }

    /**
     * Check if team member has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Owner always has all permissions
        if ($this->role === 'owner') {
            return true;
        }

        // Check if member is active
        if ($this->status !== 'active') {
            return false;
        }

        // Map permission names to attributes
        $attributeMap = [
            'manage_team' => 'can_manage_team',
            'manage_venues' => 'can_manage_venues',
            'manage_billing' => 'can_manage_billing',
            'post_shifts' => 'can_post_shifts',
            'edit_shifts' => 'can_edit_shifts',
            'cancel_shifts' => 'can_cancel_shifts',
            'approve_workers' => 'can_approve_applications',
            'view_reports' => 'can_view_reports',
            'manage_favorites' => 'can_manage_favorites',
            'manage_integrations' => 'can_manage_integrations',
            'view_activity' => 'can_view_activity',
        ];

        $attribute = $attributeMap[$permission] ?? $permission;

        return $this->{$attribute} ?? false;
    }

    /**
     * Check if team member can access a specific venue.
     */
    public function canAccessVenue(?int $venueId): bool
    {
        // Owners and admins can access all venues
        if (in_array($this->role, ['owner', 'admin', 'administrator'])) {
            return true;
        }

        // If no venue_access set, member can access all venues
        if (is_null($this->venue_access)) {
            return true;
        }

        // If no venue ID provided, check if member has any venue access
        if (is_null($venueId)) {
            return !empty($this->venue_access);
        }

        // Check if venue ID is in the access list
        return in_array($venueId, $this->venue_access);
    }

    /**
     * Get accessible venue IDs.
     */
    public function getAccessibleVenueIds(): ?array
    {
        if (in_array($this->role, ['owner', 'admin', 'administrator'])) {
            return null; // All venues
        }

        return $this->venue_access;
    }

    /**
     * Grant access to a venue.
     */
    public function grantVenueAccess(int $venueId): void
    {
        $venues = $this->venue_access ?? [];
        if (!in_array($venueId, $venues)) {
            $venues[] = $venueId;
            $this->venue_access = $venues;
            $this->save();
        }
    }

    /**
     * Revoke access to a venue.
     */
    public function revokeVenueAccess(int $venueId): void
    {
        $venues = $this->venue_access ?? [];
        $venues = array_values(array_filter($venues, fn($id) => $id != $venueId));
        $this->venue_access = $venues;
        $this->save();
    }

    /**
     * Check if this member can invite a specific role.
     */
    public function canInviteRole(string $role): bool
    {
        $myLevel = self::ROLE_HIERARCHY[$this->role] ?? 999;
        $targetLevel = self::ROLE_HIERARCHY[$role] ?? 999;

        // Can only invite roles at same level or below
        return $targetLevel >= $myLevel;
    }

    /**
     * Check if this member outranks another.
     */
    public function outranks(TeamMember $other): bool
    {
        $myLevel = self::ROLE_HIERARCHY[$this->role] ?? 999;
        $otherLevel = self::ROLE_HIERARCHY[$other->role] ?? 999;

        return $myLevel < $otherLevel;
    }

    // =========================================
    // Invitation Management
    // =========================================

    /**
     * Generate invitation token.
     */
    public function generateInvitationToken(): string
    {
        $token = Str::random(64);
        $this->invitation_token = hash('sha256', $token);
        $this->invited_at = now();
        $this->invitation_expires_at = now()->addDays(7);
        $this->save();

        return $token;
    }

    /**
     * Check if invitation is still valid.
     */
    public function isInvitationValid(): bool
    {
        return $this->status === 'pending'
            && !is_null($this->invitation_token)
            && !is_null($this->invitation_expires_at)
            && $this->invitation_expires_at->isFuture();
    }

    /**
     * Accept invitation and activate team member.
     */
    public function acceptInvitation(): bool
    {
        if (!$this->isInvitationValid()) {
            return false;
        }

        $this->status = 'active';
        $this->joined_at = now();
        $this->invitation_token = null;
        $this->invitation_expires_at = null;
        $this->save();

        return true;
    }

    /**
     * Resend invitation with new token.
     */
    public function resendInvitation(): string
    {
        if ($this->status !== 'pending') {
            return '';
        }

        return $this->generateInvitationToken();
    }

    // =========================================
    // Status Management
    // =========================================

    /**
     * Check if team member is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Suspend team member.
     */
    public function suspend(string $reason = null, ?int $suspendedById = null): void
    {
        $this->status = 'suspended';
        $this->suspended_at = now();
        $this->suspended_by = $suspendedById;
        $this->suspension_reason = $reason;
        $this->save();
    }

    /**
     * Reactivate suspended team member.
     */
    public function reactivate(): void
    {
        if ($this->status === 'suspended') {
            $this->status = 'active';
            $this->suspended_at = null;
            $this->suspended_by = null;
            $this->suspension_reason = null;
            $this->save();
        }
    }

    /**
     * Revoke team member access.
     */
    public function revoke(string $reason): void
    {
        $this->status = 'revoked';
        $this->revocation_reason = $reason;
        $this->revoked_at = now();
        $this->save();
    }

    // =========================================
    // Activity Tracking
    // =========================================

    /**
     * Update last active timestamp.
     */
    public function updateLastActive(): void
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Record login.
     */
    public function recordLogin(?string $ip = null): void
    {
        $this->increment('login_count');
        $this->last_login_at = now();
        $this->last_login_ip = $ip;
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Increment shifts posted counter.
     */
    public function incrementShiftsPosted(): void
    {
        $this->increment('shifts_posted');
        $this->updateLastActive();
    }

    /**
     * Increment shifts edited counter.
     */
    public function incrementShiftsEdited(): void
    {
        $this->increment('shifts_edited');
        $this->updateLastActive();
    }

    /**
     * Increment applications processed counter.
     */
    public function incrementApplicationsProcessed(): void
    {
        $this->increment('applications_processed');
        $this->updateLastActive();
    }

    /**
     * Increment workers approved counter.
     */
    public function incrementWorkersApproved(): void
    {
        $this->increment('workers_approved');
        $this->updateLastActive();
    }

    /**
     * Increment shifts cancelled counter.
     */
    public function incrementShiftsCancelled(): void
    {
        $this->increment('shifts_cancelled');
        $this->updateLastActive();
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for active team members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for team members of a specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for members with venue access.
     */
    public function scopeWithVenueAccess($query, int $venueId)
    {
        return $query->where(function ($q) use ($venueId) {
            $q->whereNull('venue_access')
              ->orWhereJsonContains('venue_access', $venueId);
        });
    }

    /**
     * Scope for members who can manage team.
     */
    public function scopeCanManageTeam($query)
    {
        return $query->where('can_manage_team', true);
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get formatted role name.
     */
    public function getRoleNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucwords(str_replace('_', ' ', $this->role));
    }

    /**
     * Get status badge color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'pending' => 'yellow',
            'suspended' => 'orange',
            'revoked' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get list of all permissions this member has.
     */
    public function getPermissionsList(): array
    {
        $permissions = [];
        $permissionFields = [
            'can_post_shifts',
            'can_edit_shifts',
            'can_cancel_shifts',
            'can_approve_applications',
            'can_manage_workers',
            'can_view_payments',
            'can_process_payments',
            'can_manage_venues',
            'can_manage_team',
            'can_view_analytics',
            'can_manage_settings',
            'can_manage_billing',
            'can_view_activity',
            'can_manage_favorites',
            'can_manage_integrations',
            'can_view_reports',
        ];

        foreach ($permissionFields as $field) {
            if ($this->{$field}) {
                $permissions[] = str_replace('can_', '', $field);
            }
        }

        return $permissions;
    }

    /**
     * Get activity stats for this member.
     */
    public function getActivityStats(): array
    {
        return [
            'shifts_posted' => $this->shifts_posted,
            'shifts_edited' => $this->shifts_edited,
            'shifts_cancelled' => $this->shifts_cancelled,
            'applications_processed' => $this->applications_processed,
            'workers_approved' => $this->workers_approved,
            'venues_managed' => $this->venues_managed,
            'login_count' => $this->login_count,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
        ];
    }
}
