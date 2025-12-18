<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SL-012: Multi-Position Shifts
 *
 * Represents a specific position/role within a shift event.
 * Each position can have different requirements, rates, and worker allocations.
 *
 * @property int $id
 * @property int $shift_id
 * @property string $title
 * @property string|null $description
 * @property float $hourly_rate
 * @property int $required_workers
 * @property int $filled_workers
 * @property array|null $required_skills
 * @property array|null $required_certifications
 * @property int $minimum_experience_hours
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Shift $shift
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShiftPositionAssignment> $positionAssignments
 * @property-read int|null $position_assignments_count
 * @property-read int $remaining_slots
 * @property-read bool $is_fully_filled
 */
class ShiftPosition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shift_id',
        'title',
        'description',
        'hourly_rate',
        'required_workers',
        'filled_workers',
        'required_skills',
        'required_certifications',
        'minimum_experience_hours',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'required_workers' => 'integer',
        'filled_workers' => 'integer',
        'required_skills' => 'array',
        'required_certifications' => 'array',
        'minimum_experience_hours' => 'integer',
    ];

    /**
     * Valid status values for positions.
     */
    public const STATUS_OPEN = 'open';

    public const STATUS_PARTIALLY_FILLED = 'partially_filled';

    public const STATUS_FILLED = 'filled';

    public const STATUS_CANCELLED = 'cancelled';

    // =========================================
    // Relationships
    // =========================================

    /**
     * Get the shift this position belongs to.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get all position assignments for this position.
     */
    public function positionAssignments(): HasMany
    {
        return $this->hasMany(ShiftPositionAssignment::class);
    }

    /**
     * Get all assigned workers for this position through position assignments.
     */
    public function assignedWorkers()
    {
        return $this->hasManyThrough(
            User::class,
            ShiftPositionAssignment::class,
            'shift_position_id', // Foreign key on shift_position_assignments
            'id',                // Foreign key on users
            'id',                // Local key on shift_positions
            'user_id'            // Local key on shift_position_assignments
        );
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope a query to only include open positions.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope a query to only include filled positions.
     */
    public function scopeFilled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FILLED);
    }

    /**
     * Scope a query to only include partially filled positions.
     */
    public function scopePartiallyFilled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PARTIALLY_FILLED);
    }

    /**
     * Scope a query to only include cancelled positions.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope a query to only include positions that have slots available.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_PARTIALLY_FILLED])
            ->whereColumn('filled_workers', '<', 'required_workers');
    }

    /**
     * Scope a query to only include positions requiring a specific skill.
     */
    public function scopeForSkill(Builder $query, int $skillId): Builder
    {
        return $query->whereJsonContains('required_skills', $skillId);
    }

    /**
     * Scope a query to only include positions requiring a specific certification.
     */
    public function scopeForCertification(Builder $query, int $certificationId): Builder
    {
        return $query->whereJsonContains('required_certifications', $certificationId);
    }

    /**
     * Scope a query to positions with minimum experience requirement at or below a threshold.
     */
    public function scopeMaxExperienceRequired(Builder $query, int $hours): Builder
    {
        return $query->where('minimum_experience_hours', '<=', $hours);
    }

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the number of remaining slots available for this position.
     */
    public function getRemainingSlotsAttribute(): int
    {
        return max(0, $this->required_workers - $this->filled_workers);
    }

    /**
     * Check if the position is fully filled.
     */
    public function getIsFullyFilledAttribute(): bool
    {
        return $this->filled_workers >= $this->required_workers;
    }

    /**
     * Get the fill percentage for this position.
     */
    public function getFillPercentageAttribute(): float
    {
        if ($this->required_workers <= 0) {
            return 0;
        }

        return round(($this->filled_workers / $this->required_workers) * 100, 1);
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Check if the position is fully filled.
     */
    public function isFullyFilled(): bool
    {
        return $this->is_fully_filled;
    }

    /**
     * Get the number of remaining slots.
     */
    public function remainingSlots(): int
    {
        return $this->remaining_slots;
    }

    /**
     * Check if a worker has the required skills for this position.
     */
    public function matchesWorkerSkills(User $user): bool
    {
        // If no skills are required, worker matches
        if (empty($this->required_skills)) {
            return true;
        }

        // Get worker's skill IDs
        $workerSkillIds = $user->skills()->pluck('skills.id')->toArray();

        // Check if worker has all required skills
        foreach ($this->required_skills as $requiredSkillId) {
            if (! in_array($requiredSkillId, $workerSkillIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a worker has the required certifications for this position.
     */
    public function matchesWorkerCertifications(User $user): bool
    {
        // If no certifications are required, worker matches
        if (empty($this->required_certifications)) {
            return true;
        }

        // Get worker's verified certification IDs
        $workerCertificationIds = $user->certifications()
            ->wherePivot('verified', true)
            ->pluck('certifications.id')
            ->toArray();

        // Check if worker has all required certifications
        foreach ($this->required_certifications as $requiredCertId) {
            if (! in_array($requiredCertId, $workerCertificationIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a worker meets the minimum experience requirement for this position.
     */
    public function matchesWorkerExperience(User $user): bool
    {
        // If no minimum experience required, worker matches
        if ($this->minimum_experience_hours <= 0) {
            return true;
        }

        // Get worker's total completed shift hours
        $workerHours = $user->shiftAssignments()
            ->where('status', 'completed')
            ->sum('hours_worked') ?? 0;

        return $workerHours >= $this->minimum_experience_hours;
    }

    /**
     * Check if a worker meets all requirements for this position.
     */
    public function workerMeetsAllRequirements(User $user): bool
    {
        return $this->matchesWorkerSkills($user)
            && $this->matchesWorkerCertifications($user)
            && $this->matchesWorkerExperience($user);
    }

    /**
     * Calculate a match score for a worker against this position.
     * Returns a score from 0-100.
     */
    public function calculateWorkerMatchScore(User $user): array
    {
        $scores = [
            'skills' => 0,
            'certifications' => 0,
            'experience' => 0,
        ];

        $weights = [
            'skills' => 40,
            'certifications' => 35,
            'experience' => 25,
        ];

        // Skills score
        if (empty($this->required_skills)) {
            $scores['skills'] = 100;
        } else {
            $workerSkillIds = $user->skills()->pluck('skills.id')->toArray();
            $matchedSkills = array_intersect($this->required_skills, $workerSkillIds);
            $scores['skills'] = (count($matchedSkills) / count($this->required_skills)) * 100;
        }

        // Certifications score
        if (empty($this->required_certifications)) {
            $scores['certifications'] = 100;
        } else {
            $workerCertIds = $user->certifications()
                ->wherePivot('verified', true)
                ->pluck('certifications.id')
                ->toArray();
            $matchedCerts = array_intersect($this->required_certifications, $workerCertIds);
            $scores['certifications'] = (count($matchedCerts) / count($this->required_certifications)) * 100;
        }

        // Experience score
        if ($this->minimum_experience_hours <= 0) {
            $scores['experience'] = 100;
        } else {
            $workerHours = $user->shiftAssignments()
                ->where('status', 'completed')
                ->sum('hours_worked') ?? 0;
            $scores['experience'] = min(100, ($workerHours / $this->minimum_experience_hours) * 100);
        }

        // Calculate weighted final score
        $finalScore = 0;
        foreach ($scores as $category => $score) {
            $finalScore += ($score * $weights[$category]) / 100;
        }

        return [
            'final_score' => round($finalScore, 1),
            'breakdown' => $scores,
            'weights' => $weights,
            'meets_requirements' => $this->workerMeetsAllRequirements($user),
        ];
    }

    /**
     * Check if the position has any slots available.
     */
    public function hasAvailableSlots(): bool
    {
        return $this->remaining_slots > 0
            && in_array($this->status, [self::STATUS_OPEN, self::STATUS_PARTIALLY_FILLED]);
    }

    /**
     * Increment the filled workers count and update status accordingly.
     */
    public function incrementFilledWorkers(): void
    {
        $this->filled_workers++;

        if ($this->filled_workers >= $this->required_workers) {
            $this->status = self::STATUS_FILLED;
        } elseif ($this->filled_workers > 0) {
            $this->status = self::STATUS_PARTIALLY_FILLED;
        }

        $this->save();
    }

    /**
     * Decrement the filled workers count and update status accordingly.
     */
    public function decrementFilledWorkers(): void
    {
        $this->filled_workers = max(0, $this->filled_workers - 1);

        if ($this->filled_workers <= 0) {
            $this->status = self::STATUS_OPEN;
        } elseif ($this->filled_workers < $this->required_workers) {
            $this->status = self::STATUS_PARTIALLY_FILLED;
        }

        $this->save();
    }

    /**
     * Cancel this position.
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }
}
