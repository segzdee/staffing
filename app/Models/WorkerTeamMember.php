<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-014: Team Formation - Worker Team Member Model
 *
 * Represents membership in a worker team.
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $role
 * @property string $status
 * @property int|null $invited_by
 * @property \Illuminate\Support\Carbon|null $invited_at
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property int $shifts_with_team
 * @property int $earnings_with_team
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WorkerTeam $team
 * @property-read \App\Models\User $user
 * @property-read \App\Models\User|null $inviter
 */
class WorkerTeamMember extends Model
{
    use HasFactory;

    /**
     * Role constants.
     */
    public const ROLE_LEADER = 'leader';

    public const ROLE_MEMBER = 'member';

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_REMOVED = 'removed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'role',
        'status',
        'invited_by',
        'invited_at',
        'joined_at',
        'left_at',
        'shifts_with_team',
        'earnings_with_team',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'shifts_with_team' => 'integer',
        'earnings_with_team' => 'integer',
    ];

    /**
     * Get the team.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(WorkerTeam::class, 'team_id');
    }

    /**
     * Get the member (user).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this member.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope to get memberships for a specific team.
     */
    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to get memberships for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get pending memberships (invitations).
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get leaders.
     */
    public function scopeLeaders($query)
    {
        return $query->where('role', self::ROLE_LEADER);
    }

    /**
     * Check if this is a leader role.
     */
    public function isLeader(): bool
    {
        return $this->role === self::ROLE_LEADER;
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if membership is pending (invitation).
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Accept the team invitation.
     */
    public function accept(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        $this->joined_at = now();

        if ($this->save()) {
            $this->team->incrementMemberCount();

            return true;
        }

        return false;
    }

    /**
     * Decline the team invitation.
     */
    public function decline(): bool
    {
        $this->status = self::STATUS_DECLINED;

        return $this->save();
    }

    /**
     * Remove from team.
     */
    public function remove(?string $reason = null): bool
    {
        $wasActive = $this->isActive();

        $this->status = self::STATUS_REMOVED;
        $this->left_at = now();
        $this->notes = $reason;

        if ($this->save() && $wasActive) {
            $this->team->decrementMemberCount();

            return true;
        }

        return $this->save();
    }

    /**
     * Promote to leader.
     */
    public function promoteToLeader(): bool
    {
        $this->role = self::ROLE_LEADER;

        return $this->save();
    }

    /**
     * Demote to member.
     */
    public function demoteToMember(): bool
    {
        $this->role = self::ROLE_MEMBER;

        return $this->save();
    }

    /**
     * Record a shift completed with this team.
     */
    public function recordShift(int $earnings = 0): bool
    {
        $this->shifts_with_team++;
        $this->earnings_with_team += $earnings;

        return $this->save();
    }

    /**
     * Get role label.
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_LEADER => 'Leader',
            self::ROLE_MEMBER => 'Member',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Invitation',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_REMOVED => 'Removed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted earnings.
     */
    public function getFormattedEarnings(): string
    {
        return number_format($this->earnings_with_team / 100, 2);
    }
}
