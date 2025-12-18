<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BIZ-005: Business Roster Management
 *
 * Represents a roster (list) of workers maintained by a business.
 * Rosters can be of type: preferred, regular, backup, or blacklist.
 *
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $business
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RosterMember> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RosterInvitation> $invitations
 * @property-read int|null $invitations_count
 */
class BusinessRoster extends Model
{
    use HasFactory;

    /**
     * Roster types
     */
    public const TYPE_PREFERRED = 'preferred';

    public const TYPE_REGULAR = 'regular';

    public const TYPE_BACKUP = 'backup';

    public const TYPE_BLACKLIST = 'blacklist';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'description',
        'type',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the business that owns this roster.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    /**
     * Get all members of this roster.
     */
    public function members(): HasMany
    {
        return $this->hasMany(RosterMember::class, 'roster_id');
    }

    /**
     * Get active members of this roster.
     */
    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    /**
     * Get pending members of this roster.
     */
    public function pendingMembers(): HasMany
    {
        return $this->members()->where('status', 'pending');
    }

    /**
     * Get all invitations for this roster.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(RosterInvitation::class, 'roster_id');
    }

    /**
     * Get pending invitations for this roster.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->where('status', 'pending');
    }

    /**
     * Check if a worker is in this roster.
     */
    public function hasWorker(User|int $worker): bool
    {
        $workerId = $worker instanceof User ? $worker->id : $worker;

        return $this->members()->where('worker_id', $workerId)->exists();
    }

    /**
     * Check if a worker has a pending invitation.
     */
    public function hasPendingInvitation(User|int $worker): bool
    {
        $workerId = $worker instanceof User ? $worker->id : $worker;

        return $this->invitations()
            ->where('worker_id', $workerId)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the member record for a specific worker.
     */
    public function getMember(User|int $worker): ?RosterMember
    {
        $workerId = $worker instanceof User ? $worker->id : $worker;

        return $this->members()->where('worker_id', $workerId)->first();
    }

    /**
     * Scope for preferred rosters.
     */
    public function scopePreferred($query)
    {
        return $query->where('type', self::TYPE_PREFERRED);
    }

    /**
     * Scope for regular rosters.
     */
    public function scopeRegular($query)
    {
        return $query->where('type', self::TYPE_REGULAR);
    }

    /**
     * Scope for backup rosters.
     */
    public function scopeBackup($query)
    {
        return $query->where('type', self::TYPE_BACKUP);
    }

    /**
     * Scope for blacklist rosters.
     */
    public function scopeBlacklist($query)
    {
        return $query->where('type', self::TYPE_BLACKLIST);
    }

    /**
     * Scope for default rosters.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get display name for roster type.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PREFERRED => 'Preferred Workers',
            self::TYPE_REGULAR => 'Regular Workers',
            self::TYPE_BACKUP => 'Backup Workers',
            self::TYPE_BLACKLIST => 'Blacklisted Workers',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get badge color for roster type.
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PREFERRED => 'success',
            self::TYPE_REGULAR => 'primary',
            self::TYPE_BACKUP => 'warning',
            self::TYPE_BLACKLIST => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get all available roster types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PREFERRED => 'Preferred Workers',
            self::TYPE_REGULAR => 'Regular Workers',
            self::TYPE_BACKUP => 'Backup Workers',
            self::TYPE_BLACKLIST => 'Blacklisted Workers',
        ];
    }

    /**
     * Check if this roster type should exclude workers from shift matching.
     */
    public function isExclusionRoster(): bool
    {
        return $this->type === self::TYPE_BLACKLIST;
    }

    /**
     * Get members ordered by priority for shift matching.
     */
    public function getMembersForShiftMatching(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeMembers()
            ->orderByDesc('priority')
            ->orderByDesc('total_shifts')
            ->get();
    }
}
