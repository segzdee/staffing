<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BIZ-005: Roster Member
 *
 * Represents a worker's membership in a business roster.
 *
 * @property int $id
 * @property int $roster_id
 * @property int $worker_id
 * @property string $status
 * @property string|null $notes
 * @property float|null $custom_rate
 * @property int $priority
 * @property array|null $preferred_positions
 * @property array|null $availability_preferences
 * @property int $added_by
 * @property \Illuminate\Support\Carbon|null $last_worked_at
 * @property int $total_shifts
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\BusinessRoster $roster
 * @property-read \App\Models\User $worker
 * @property-read \App\Models\User $addedByUser
 */
class RosterMember extends Model
{
    use HasFactory;

    /**
     * Member statuses
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'roster_id',
        'worker_id',
        'status',
        'notes',
        'custom_rate',
        'priority',
        'preferred_positions',
        'availability_preferences',
        'added_by',
        'last_worked_at',
        'total_shifts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_rate' => 'decimal:2',
        'priority' => 'integer',
        'preferred_positions' => 'array',
        'availability_preferences' => 'array',
        'last_worked_at' => 'datetime',
        'total_shifts' => 'integer',
    ];

    /**
     * Get the roster this member belongs to.
     */
    public function roster(): BelongsTo
    {
        return $this->belongsTo(BusinessRoster::class, 'roster_id');
    }

    /**
     * Get the worker user.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Get the user who added this member.
     */
    public function addedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scope for active members.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for inactive members.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope for pending members.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope ordered by priority (highest first).
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * Scope to filter by preferred position.
     */
    public function scopeWithPosition($query, string $position)
    {
        return $query->whereJsonContains('preferred_positions', $position);
    }

    /**
     * Check if member is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if member is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if member is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Activate this member.
     */
    public function activate(): self
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        return $this;
    }

    /**
     * Deactivate this member.
     */
    public function deactivate(): self
    {
        $this->update(['status' => self::STATUS_INACTIVE]);

        return $this;
    }

    /**
     * Update work statistics after a shift is completed.
     */
    public function recordShiftWorked(Shift $shift): self
    {
        $this->update([
            'last_worked_at' => now(),
            'total_shifts' => $this->total_shifts + 1,
        ]);

        return $this;
    }

    /**
     * Check if member has a custom rate set.
     */
    public function hasCustomRate(): bool
    {
        return ! is_null($this->custom_rate) && $this->custom_rate > 0;
    }

    /**
     * Get the effective rate for this member (custom or default).
     */
    public function getEffectiveRate(?float $defaultRate = null): ?float
    {
        return $this->custom_rate ?? $defaultRate;
    }

    /**
     * Get the display status.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING => 'Pending',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_PENDING => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING => 'Pending',
        ];
    }

    /**
     * Check if worker can work at specified position.
     */
    public function canWorkPosition(string $position): bool
    {
        if (empty($this->preferred_positions)) {
            return true; // No preference means all positions OK
        }

        return in_array($position, $this->preferred_positions);
    }

    /**
     * Check if worker is available based on preferences.
     */
    public function isAvailableFor(Shift $shift): bool
    {
        if (empty($this->availability_preferences)) {
            return true; // No preference means always available
        }

        // Check day of week preference
        $dayOfWeek = $shift->shift_date->format('l'); // Monday, Tuesday, etc.
        $preferences = $this->availability_preferences;

        if (isset($preferences['days']) && ! in_array($dayOfWeek, $preferences['days'])) {
            return false;
        }

        // Check time preference
        if (isset($preferences['start_time']) && isset($preferences['end_time'])) {
            $shiftStart = $shift->start_time->format('H:i');
            $shiftEnd = $shift->end_time->format('H:i');

            if ($shiftStart < $preferences['start_time'] || $shiftEnd > $preferences['end_time']) {
                return false;
            }
        }

        return true;
    }
}
