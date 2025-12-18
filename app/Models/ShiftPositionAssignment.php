<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SL-012: Multi-Position Shifts
 *
 * Represents the assignment of a worker to a specific position within a multi-position shift.
 * This links ShiftPosition with ShiftAssignment and User models.
 *
 * @property int $id
 * @property int $shift_position_id
 * @property int $shift_assignment_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ShiftPosition $shiftPosition
 * @property-read \App\Models\ShiftAssignment $shiftAssignment
 * @property-read \App\Models\User $user
 */
class ShiftPositionAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_position_id',
        'shift_assignment_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shift_position_id' => 'integer',
        'shift_assignment_id' => 'integer',
        'user_id' => 'integer',
    ];

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the shift position this assignment is for.
     */
    public function shiftPosition(): BelongsTo
    {
        return $this->belongsTo(ShiftPosition::class);
    }

    /**
     * Get the shift assignment this position assignment is linked to.
     */
    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    /**
     * Get the user (worker) assigned to this position.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - for semantic clarity when referring to workers.
     */
    public function worker(): BelongsTo
    {
        return $this->user();
    }

    // =========================================
    // Convenience Methods
    // =========================================

    /**
     * Get the shift through the position relationship.
     */
    public function getShiftAttribute()
    {
        return $this->shiftPosition?->shift;
    }

    /**
     * Get the position title.
     */
    public function getPositionTitleAttribute(): ?string
    {
        return $this->shiftPosition?->title;
    }

    /**
     * Get the hourly rate for this position.
     */
    public function getHourlyRateAttribute(): ?float
    {
        return $this->shiftPosition?->hourly_rate;
    }

    /**
     * Check if this assignment is for a specific position.
     */
    public function isForPosition(ShiftPosition $position): bool
    {
        return $this->shift_position_id === $position->id;
    }

    /**
     * Check if this assignment is for a specific user.
     */
    public function isForUser(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
