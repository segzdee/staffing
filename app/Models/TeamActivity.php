<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BIZ-REG-008: Team Activity Model
 *
 * Comprehensive activity log for team member actions.
 * Supports audit trails and activity reporting.
 *
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property int|null $team_member_id
 * @property string $activity_type
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $description
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $venue_id
 */
class TeamActivity extends Model
{
    use HasFactory;

    /**
     * Activity types with descriptions.
     */
    public const ACTIVITY_TYPES = [
        // Team Management
        'invitation_sent' => 'Invitation sent',
        'invitation_accepted' => 'Invitation accepted',
        'invitation_declined' => 'Invitation declined',
        'invitation_revoked' => 'Invitation revoked',
        'invitation_resent' => 'Invitation resent',
        'invitation_expired' => 'Invitation expired',
        'member_joined' => 'Team member joined',
        'member_suspended' => 'Team member suspended',
        'member_reactivated' => 'Team member reactivated',
        'member_removed' => 'Team member removed',
        'role_changed' => 'Role changed',
        'permissions_updated' => 'Permissions updated',
        'venue_access_granted' => 'Venue access granted',
        'venue_access_revoked' => 'Venue access revoked',

        // Shift Management
        'shift_posted' => 'Shift posted',
        'shift_edited' => 'Shift edited',
        'shift_cancelled' => 'Shift cancelled',
        'shift_duplicated' => 'Shift duplicated',
        'bulk_shifts_created' => 'Bulk shifts created',

        // Worker Management
        'worker_approved' => 'Worker approved',
        'worker_rejected' => 'Worker rejected',
        'worker_assigned' => 'Worker assigned',
        'worker_unassigned' => 'Worker unassigned',
        'worker_favorited' => 'Worker favorited',
        'worker_blocked' => 'Worker blocked',

        // Venue Management
        'venue_created' => 'Venue created',
        'venue_updated' => 'Venue updated',
        'venue_deactivated' => 'Venue deactivated',
        'venue_reactivated' => 'Venue reactivated',
        'venue_deleted' => 'Venue deleted',
        'venue_manager_assigned' => 'Venue manager assigned',
        'venue_manager_removed' => 'Venue manager removed',

        // Authentication
        'login' => 'Logged in',
        'logout' => 'Logged out',
        'password_changed' => 'Password changed',
        'two_factor_enabled' => 'Two-factor enabled',
        'two_factor_disabled' => 'Two-factor disabled',

        // Settings
        'settings_updated' => 'Settings updated',
        'billing_updated' => 'Billing updated',
        'integration_connected' => 'Integration connected',
        'integration_disconnected' => 'Integration disconnected',
    ];

    /**
     * Activity categories.
     */
    public const CATEGORIES = [
        'team' => ['invitation_sent', 'invitation_accepted', 'invitation_declined', 'invitation_revoked', 'invitation_resent', 'invitation_expired', 'member_joined', 'member_suspended', 'member_reactivated', 'member_removed', 'role_changed', 'permissions_updated', 'venue_access_granted', 'venue_access_revoked'],
        'shifts' => ['shift_posted', 'shift_edited', 'shift_cancelled', 'shift_duplicated', 'bulk_shifts_created'],
        'workers' => ['worker_approved', 'worker_rejected', 'worker_assigned', 'worker_unassigned', 'worker_favorited', 'worker_blocked'],
        'venues' => ['venue_created', 'venue_updated', 'venue_deactivated', 'venue_reactivated', 'venue_deleted', 'venue_manager_assigned', 'venue_manager_removed'],
        'auth' => ['login', 'logout', 'password_changed', 'two_factor_enabled', 'two_factor_disabled'],
        'settings' => ['settings_updated', 'billing_updated', 'integration_connected', 'integration_disconnected'],
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'team_member_id',
        'activity_type',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'venue_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = [
        'activity_label',
        'category',
        'formatted_time',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the business.
     */
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the team member (if applicable).
     */
    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * Get the venue (if applicable).
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the subject of the activity (polymorphic).
     */
    public function subject()
    {
        return $this->morphTo();
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get activity label.
     */
    public function getActivityLabelAttribute(): string
    {
        return self::ACTIVITY_TYPES[$this->activity_type] ?? ucfirst(str_replace('_', ' ', $this->activity_type));
    }

    /**
     * Get activity category.
     */
    public function getCategoryAttribute(): string
    {
        foreach (self::CATEGORIES as $category => $types) {
            if (in_array($this->activity_type, $types)) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * Get formatted time.
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific team member.
     */
    public function scopeForTeamMember($query, int $teamMemberId)
    {
        return $query->where('team_member_id', $teamMemberId);
    }

    /**
     * Scope for specific activity type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope for activity types.
     */
    public function scopeOfTypes($query, array $types)
    {
        return $query->whereIn('activity_type', $types);
    }

    /**
     * Scope for category.
     */
    public function scopeInCategory($query, string $category)
    {
        $types = self::CATEGORIES[$category] ?? [];
        return $query->whereIn('activity_type', $types);
    }

    /**
     * Scope for specific venue.
     */
    public function scopeForVenue($query, int $venueId)
    {
        return $query->where('venue_id', $venueId);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope for recent activities.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================
    // Factory Methods
    // =========================================

    /**
     * Log an activity.
     */
    public static function log(
        int $businessId,
        int $userId,
        string $activityType,
        string $description,
        ?array $metadata = null,
        ?int $teamMemberId = null,
        ?int $venueId = null,
        ?string $subjectType = null,
        ?int $subjectId = null
    ): self {
        return static::create([
            'business_id' => $businessId,
            'user_id' => $userId,
            'team_member_id' => $teamMemberId,
            'activity_type' => $activityType,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'venue_id' => $venueId,
        ]);
    }

    /**
     * Log team invitation sent.
     */
    public static function logInvitationSent(TeamInvitation $invitation, User $inviter): self
    {
        return self::log(
            $invitation->business_id,
            $inviter->id,
            'invitation_sent',
            "Invited {$invitation->email} as {$invitation->role_label}",
            [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'venue_access' => $invitation->venue_access,
            ],
            null,
            null,
            TeamInvitation::class,
            $invitation->id
        );
    }

    /**
     * Log team member role change.
     */
    public static function logRoleChange(TeamMember $member, string $oldRole, string $newRole, User $changedBy): self
    {
        return self::log(
            $member->business_id,
            $changedBy->id,
            'role_changed',
            "Changed {$member->user->name}'s role from {$oldRole} to {$newRole}",
            [
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ],
            $member->id,
            null,
            TeamMember::class,
            $member->id
        );
    }

    /**
     * Log venue creation.
     */
    public static function logVenueCreated(Venue $venue, User $createdBy): self
    {
        return self::log(
            $venue->businessProfile->user_id,
            $createdBy->id,
            'venue_created',
            "Created venue: {$venue->name}",
            [
                'venue_name' => $venue->name,
                'venue_type' => $venue->type,
                'address' => $venue->full_address,
            ],
            null,
            $venue->id,
            Venue::class,
            $venue->id
        );
    }

    /**
     * Log shift posted.
     */
    public static function logShiftPosted(Shift $shift, User $postedBy, ?int $teamMemberId = null): self
    {
        return self::log(
            $shift->business_id,
            $postedBy->id,
            'shift_posted',
            "Posted shift: {$shift->title}",
            [
                'shift_title' => $shift->title,
                'date' => $shift->start_time->toDateString(),
                'hourly_rate' => $shift->hourly_rate,
            ],
            $teamMemberId,
            $shift->venue_id,
            Shift::class,
            $shift->id
        );
    }

    // =========================================
    // Statistics Methods
    // =========================================

    /**
     * Get activity count by type for a business.
     */
    public static function getActivityCounts(int $businessId, int $days = 30): array
    {
        return static::forBusiness($businessId)
            ->recent($days)
            ->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->pluck('count', 'activity_type')
            ->toArray();
    }

    /**
     * Get most active team members.
     */
    public static function getMostActiveMembers(int $businessId, int $limit = 5): array
    {
        return static::forBusiness($businessId)
            ->recent(30)
            ->whereNotNull('team_member_id')
            ->selectRaw('team_member_id, COUNT(*) as activity_count')
            ->groupBy('team_member_id')
            ->orderByDesc('activity_count')
            ->limit($limit)
            ->with('teamMember.user')
            ->get()
            ->toArray();
    }
}
