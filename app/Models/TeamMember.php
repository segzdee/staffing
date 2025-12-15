<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * BIZ-003: Team Member Model
 *
 * Represents a team member within a business organization.
 * Handles role-based permissions and venue access control.
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
 * @property string $status
 * @property string|null $invitation_token
 * @property \Illuminate\Support\Carbon|null $invited_at
 * @property \Illuminate\Support\Carbon|null $invitation_expires_at
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $last_active_at
 * @property int $shifts_posted
 * @property int $shifts_edited
 * @property int $applications_processed
 * @property string|null $notes
 * @property string|null $revocation_reason
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class TeamMember extends Model
{
    use HasFactory;

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
        'status',
        'invitation_token',
        'invited_at',
        'invitation_expires_at',
        'joined_at',
        'last_active_at',
        'shifts_posted',
        'shifts_edited',
        'applications_processed',
        'notes',
        'revocation_reason',
        'revoked_at',
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
        'invited_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'revoked_at' => 'datetime',
        'shifts_posted' => 'integer',
        'shifts_edited' => 'integer',
        'applications_processed' => 'integer',
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

    // =========================================
    // Role-Based Permissions
    // =========================================

    /**
     * Define permission sets for each role.
     */
    public static function getRolePermissions(string $role): array
    {
        $permissions = [
            'owner' => [
                'can_post_shifts' => true,
                'can_edit_shifts' => true,
                'can_cancel_shifts' => true,
                'can_approve_applications' => true,
                'can_manage_workers' => true,
                'can_view_payments' => true,
                'can_process_payments' => true,
                'can_manage_venues' => true,
                'can_manage_team' => true,
                'can_view_analytics' => true,
                'can_manage_settings' => true,
            ],
            'administrator' => [
                'can_post_shifts' => true,
                'can_edit_shifts' => true,
                'can_cancel_shifts' => true,
                'can_approve_applications' => true,
                'can_manage_workers' => true,
                'can_view_payments' => true,
                'can_process_payments' => false,
                'can_manage_venues' => true,
                'can_manage_team' => true,
                'can_view_analytics' => true,
                'can_manage_settings' => false,
            ],
            'location_manager' => [
                'can_post_shifts' => true,
                'can_edit_shifts' => true,
                'can_cancel_shifts' => true,
                'can_approve_applications' => true,
                'can_manage_workers' => false,
                'can_view_payments' => true,
                'can_process_payments' => false,
                'can_manage_venues' => false,
                'can_manage_team' => false,
                'can_view_analytics' => true,
                'can_manage_settings' => false,
            ],
            'scheduler' => [
                'can_post_shifts' => true,
                'can_edit_shifts' => true,
                'can_cancel_shifts' => false,
                'can_approve_applications' => true,
                'can_manage_workers' => false,
                'can_view_payments' => false,
                'can_process_payments' => false,
                'can_manage_venues' => false,
                'can_manage_team' => false,
                'can_view_analytics' => false,
                'can_manage_settings' => false,
            ],
            'viewer' => [
                'can_post_shifts' => false,
                'can_edit_shifts' => false,
                'can_cancel_shifts' => false,
                'can_approve_applications' => false,
                'can_manage_workers' => false,
                'can_view_payments' => false,
                'can_process_payments' => false,
                'can_manage_venues' => false,
                'can_manage_team' => false,
                'can_view_analytics' => false,
                'can_manage_settings' => false,
            ],
        ];

        return $permissions[$role] ?? $permissions['viewer'];
    }

    /**
     * Apply role permissions to team member.
     */
    public function applyRolePermissions()
    {
        $permissions = self::getRolePermissions($this->role);
        $this->fill($permissions);
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

        // Check specific permission
        return $this->{$permission} ?? false;
    }

    /**
     * Check if team member can access a specific venue.
     */
    public function canAccessVenue(?int $venueId): bool
    {
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
     * Grant access to a venue.
     */
    public function grantVenueAccess(int $venueId)
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
    public function revokeVenueAccess(int $venueId)
    {
        $venues = $this->venue_access ?? [];
        $venues = array_values(array_filter($venues, fn($id) => $id != $venueId));
        $this->venue_access = $venues;
        $this->save();
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
        $this->invitation_expires_at = now()->addDays(7); // 7 days to accept
        $this->save();

        return $token; // Return unhashed token to send in email
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
    public function acceptInvitation()
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
    public function suspend(string $reason = null)
    {
        $this->status = 'suspended';
        $this->notes = $reason;
        $this->save();
    }

    /**
     * Reactivate suspended team member.
     */
    public function reactivate()
    {
        if ($this->status === 'suspended') {
            $this->status = 'active';
            $this->save();
        }
    }

    /**
     * Revoke team member access.
     */
    public function revoke(string $reason)
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
    public function updateLastActive()
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * Increment shifts posted counter.
     */
    public function incrementShiftsPosted()
    {
        $this->increment('shifts_posted');
        $this->updateLastActive();
    }

    /**
     * Increment shifts edited counter.
     */
    public function incrementShiftsEdited()
    {
        $this->increment('shifts_edited');
        $this->updateLastActive();
    }

    /**
     * Increment applications processed counter.
     */
    public function incrementApplicationsProcessed()
    {
        $this->increment('applications_processed');
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

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get formatted role name.
     */
    public function getRoleNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->role));
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
        ];

        foreach ($permissionFields as $field) {
            if ($this->{$field}) {
                $permissions[] = str_replace('can_', '', $field);
            }
        }

        return $permissions;
    }
}
