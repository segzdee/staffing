<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WKR-014: Team Formation - Worker Team Model
 *
 * Represents a team of workers that can apply to shifts together.
 *
 * @property int $id
 * @property string $name
 * @property int $created_by
 * @property int|null $business_id
 * @property string|null $description
 * @property string|null $avatar_url
 * @property int $max_members
 * @property int $member_count
 * @property bool $is_active
 * @property bool $is_public
 * @property bool $requires_approval
 * @property int $total_shifts_completed
 * @property float|null $average_rating
 * @property int $total_earnings
 * @property array|null $specializations
 * @property array|null $preferred_industries
 * @property float|null $min_reliability_score
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\User|null $business
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\WorkerTeamMember> $memberships
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\User> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\TeamShiftRequest> $shiftRequests
 */
class WorkerTeam extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'created_by',
        'business_id',
        'description',
        'avatar_url',
        'max_members',
        'member_count',
        'is_active',
        'is_public',
        'requires_approval',
        'total_shifts_completed',
        'average_rating',
        'total_earnings',
        'specializations',
        'preferred_industries',
        'min_reliability_score',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'max_members' => 'integer',
        'member_count' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'total_shifts_completed' => 'integer',
        'average_rating' => 'decimal:2',
        'total_earnings' => 'integer',
        'specializations' => 'array',
        'preferred_industries' => 'array',
        'min_reliability_score' => 'decimal:2',
    ];

    /**
     * Get the team creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the associated business (if any).
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get all team memberships.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkerTeamMember::class, 'team_id');
    }

    /**
     * Get all team members (users) through the membership pivot.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'worker_team_members', 'team_id', 'user_id')
            ->withPivot(['role', 'status', 'joined_at', 'shifts_with_team', 'earnings_with_team'])
            ->withTimestamps();
    }

    /**
     * Get active team members.
     */
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    /**
     * Get team leaders.
     */
    public function leaders(): BelongsToMany
    {
        return $this->activeMembers()->wherePivot('role', 'leader');
    }

    /**
     * Get shift requests for this team.
     */
    public function shiftRequests(): HasMany
    {
        return $this->hasMany(TeamShiftRequest::class, 'team_id');
    }

    /**
     * Scope to get active teams.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get public teams.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get teams created by a specific user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to get teams associated with a business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Check if a user is a member of this team.
     */
    public function hasMember(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if a user is a leader of this team.
     */
    public function hasLeader(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'leader')
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if team is full.
     */
    public function isFull(): bool
    {
        return $this->member_count >= $this->max_members;
    }

    /**
     * Check if team can accept new members.
     */
    public function canAcceptMembers(): bool
    {
        return $this->is_active && ! $this->isFull();
    }

    /**
     * Get the number of available slots.
     */
    public function getAvailableSlots(): int
    {
        return max(0, $this->max_members - $this->member_count);
    }

    /**
     * Increment member count.
     */
    public function incrementMemberCount(): bool
    {
        $this->member_count++;

        return $this->save();
    }

    /**
     * Decrement member count.
     */
    public function decrementMemberCount(): bool
    {
        $this->member_count = max(0, $this->member_count - 1);

        return $this->save();
    }

    /**
     * Recalculate member count from database.
     */
    public function refreshMemberCount(): bool
    {
        $this->member_count = $this->memberships()
            ->where('status', 'active')
            ->count();

        return $this->save();
    }

    /**
     * Add shift completion to stats.
     */
    public function recordShiftCompletion(int $earnings = 0): bool
    {
        $this->total_shifts_completed++;
        $this->total_earnings += $earnings;

        return $this->save();
    }

    /**
     * Update average rating.
     */
    public function updateAverageRating(): bool
    {
        $averageRating = $this->activeMembers()
            ->join('users', 'users.id', '=', 'worker_team_members.user_id')
            ->avg('users.rating_as_worker');

        $this->average_rating = $averageRating;

        return $this->save();
    }

    /**
     * Check if a user meets the reliability requirements.
     */
    public function meetsReliabilityRequirement(User $user): bool
    {
        if (! $this->min_reliability_score) {
            return true;
        }

        $userScore = $user->reliability_score ?? 0;

        return $userScore >= $this->min_reliability_score;
    }

    /**
     * Deactivate the team.
     */
    public function deactivate(): bool
    {
        $this->is_active = false;

        return $this->save();
    }

    /**
     * Activate the team.
     */
    public function activate(): bool
    {
        $this->is_active = true;

        return $this->save();
    }

    /**
     * Get formatted total earnings.
     */
    public function getFormattedEarnings(): string
    {
        return number_format($this->total_earnings / 100, 2);
    }
}
