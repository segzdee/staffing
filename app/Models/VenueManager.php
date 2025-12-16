<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BIZ-REG-006: Venue Manager Pivot Model
 *
 * Manages the relationship between venues and team members.
 * Includes venue-specific permission overrides.
 *
 * @property int $id
 * @property int $venue_id
 * @property int $team_member_id
 * @property bool $is_primary
 * @property bool $can_post_shifts
 * @property bool $can_edit_shifts
 * @property bool $can_cancel_shifts
 * @property bool $can_approve_workers
 * @property bool $can_manage_venue_settings
 * @property bool $notify_new_applications
 * @property bool $notify_shift_changes
 * @property bool $notify_worker_checkins
 */
class VenueManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'venue_id',
        'team_member_id',
        'is_primary',
        'can_post_shifts',
        'can_edit_shifts',
        'can_cancel_shifts',
        'can_approve_workers',
        'can_manage_venue_settings',
        'notify_new_applications',
        'notify_shift_changes',
        'notify_worker_checkins',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_post_shifts' => 'boolean',
        'can_edit_shifts' => 'boolean',
        'can_cancel_shifts' => 'boolean',
        'can_approve_workers' => 'boolean',
        'can_manage_venue_settings' => 'boolean',
        'notify_new_applications' => 'boolean',
        'notify_shift_changes' => 'boolean',
        'notify_worker_checkins' => 'boolean',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the venue.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the team member.
     */
    public function teamMember()
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * Get the user through team member.
     */
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            TeamMember::class,
            'id', // Foreign key on team_members
            'id', // Foreign key on users
            'team_member_id', // Local key on venue_managers
            'user_id' // Local key on team_members
        );
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Check if manager has a specific permission for this venue.
     */
    public function hasPermission(string $permission): bool
    {
        $permissionMap = [
            'post_shifts' => 'can_post_shifts',
            'edit_shifts' => 'can_edit_shifts',
            'cancel_shifts' => 'can_cancel_shifts',
            'approve_workers' => 'can_approve_workers',
            'manage_venue_settings' => 'can_manage_venue_settings',
        ];

        $field = $permissionMap[$permission] ?? $permission;

        return $this->{$field} ?? false;
    }

    /**
     * Get notification preferences.
     */
    public function getNotificationPreferences(): array
    {
        return [
            'new_applications' => $this->notify_new_applications,
            'shift_changes' => $this->notify_shift_changes,
            'worker_checkins' => $this->notify_worker_checkins,
        ];
    }

    /**
     * Should receive notification for type.
     */
    public function shouldNotify(string $type): bool
    {
        $notificationMap = [
            'new_application' => 'notify_new_applications',
            'shift_change' => 'notify_shift_changes',
            'worker_checkin' => 'notify_worker_checkins',
        ];

        $field = $notificationMap[$type] ?? null;

        return $field ? $this->{$field} : false;
    }

    // =========================================
    // Query Scopes
    // =========================================

    /**
     * Scope for primary managers.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for managers who can post shifts.
     */
    public function scopeCanPostShifts($query)
    {
        return $query->where('can_post_shifts', true);
    }

    /**
     * Scope for managers who should be notified of applications.
     */
    public function scopeNotifyApplications($query)
    {
        return $query->where('notify_new_applications', true);
    }
}
