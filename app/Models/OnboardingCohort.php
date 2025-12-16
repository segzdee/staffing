<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * OnboardingCohort Model
 *
 * A/B testing cohorts for onboarding experiments.
 * Tracks different onboarding variations and their performance.
 *
 * @property int $id
 * @property string $cohort_id
 * @property string $name
 * @property string|null $description
 * @property string $experiment_name
 * @property string $user_type
 * @property string $variant
 * @property int $allocation_percentage
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string $status
 * @property bool $is_winner
 * @property int $total_users
 * @property int $completed_users
 * @property float $completion_rate
 * @property float|null $avg_time_to_activation_hours
 * @property float $dropout_rate
 * @property array|null $step_completion_rates
 * @property array|null $step_dropout_rates
 * @property array|null $step_avg_times
 * @property float|null $statistical_significance
 * @property array|null $comparison_data
 * @property array|null $configuration
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $declared_winner_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OnboardingCohort extends Model
{
    use HasFactory;

    protected $fillable = [
        'cohort_id',
        'name',
        'description',
        'experiment_name',
        'user_type',
        'variant',
        'allocation_percentage',
        'start_date',
        'end_date',
        'status',
        'is_winner',
        'total_users',
        'completed_users',
        'completion_rate',
        'avg_time_to_activation_hours',
        'dropout_rate',
        'step_completion_rates',
        'step_dropout_rates',
        'step_avg_times',
        'statistical_significance',
        'comparison_data',
        'configuration',
        'created_by',
        'declared_winner_at',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'declared_winner_at' => 'datetime',
        'is_winner' => 'boolean',
        'allocation_percentage' => 'integer',
        'total_users' => 'integer',
        'completed_users' => 'integer',
        'completion_rate' => 'float',
        'avg_time_to_activation_hours' => 'float',
        'dropout_rate' => 'float',
        'statistical_significance' => 'float',
        'step_completion_rates' => 'array',
        'step_dropout_rates' => 'array',
        'step_avg_times' => 'array',
        'comparison_data' => 'array',
        'configuration' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get users assigned to this cohort
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'onboarding_user_cohorts')
            ->withTimestamps()
            ->withPivot('assigned_at');
    }

    /**
     * Get events for this cohort
     */
    public function events()
    {
        return $this->hasMany(OnboardingEvent::class, 'cohort_id', 'cohort_id');
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to active cohorts
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to running experiments (active and within date range)
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope by experiment name
     */
    public function scopeForExperiment(Builder $query, string $experimentName): Builder
    {
        return $query->where('experiment_name', $experimentName);
    }

    /**
     * Scope by user type
     */
    public function scopeForUserType(Builder $query, string $userType): Builder
    {
        return $query->where(function ($q) use ($userType) {
            $q->where('user_type', $userType)
                ->orWhere('user_type', 'all');
        });
    }

    /**
     * Scope to winners
     */
    public function scopeWinners(Builder $query): Builder
    {
        return $query->where('is_winner', true);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ==================== STATUS METHODS ====================

    /**
     * Check if cohort is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if cohort is running (active and within date range)
     */
    public function isRunning(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->start_date && $this->start_date > now()) {
            return false;
        }

        if ($this->end_date && $this->end_date < now()) {
            return false;
        }

        return true;
    }

    /**
     * Check if cohort is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if cohort is the winner
     */
    public function isWinner(): bool
    {
        return $this->is_winner;
    }

    // ==================== ACTION METHODS ====================

    /**
     * Activate the cohort
     */
    public function activate(): self
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    /**
     * Pause the cohort
     */
    public function pause(): self
    {
        $this->update(['status' => 'paused']);
        return $this;
    }

    /**
     * Complete the cohort
     */
    public function complete(): self
    {
        $this->update(['status' => 'completed']);
        return $this;
    }

    /**
     * Declare this cohort as winner
     */
    public function declareWinner(): self
    {
        // Mark other cohorts in the same experiment as not winner
        static::forExperiment($this->experiment_name)
            ->where('id', '!=', $this->id)
            ->update(['is_winner' => false, 'status' => 'completed']);

        $this->update([
            'is_winner' => true,
            'status' => 'winner',
            'declared_winner_at' => now(),
        ]);

        return $this;
    }

    /**
     * Assign a user to this cohort
     */
    public function assignUser(User $user): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['assigned_at' => now()]
        ]);

        $this->increment('total_users');

        // Log the event
        OnboardingEvent::logWithCohort(
            $user->id,
            OnboardingEvent::EVENT_COHORT_ASSIGNED,
            null,
            ['cohort_name' => $this->name, 'variant' => $this->variant],
            $this->cohort_id,
            $this->variant
        );
    }

    /**
     * Update metrics from current data
     */
    public function refreshMetrics(): self
    {
        $userIds = $this->users()->pluck('users.id');

        // Calculate completion rate
        $completed = OnboardingEvent::forCohort($this->cohort_id)
            ->ofType(OnboardingEvent::EVENT_ONBOARDING_COMPLETED)
            ->distinct('user_id')
            ->count('user_id');

        // Calculate average time to activation
        $avgTime = OnboardingEvent::getAverageTimeBetweenEvents(
            OnboardingEvent::EVENT_ONBOARDING_STARTED,
            OnboardingEvent::EVENT_ONBOARDING_COMPLETED,
            $this->start_date ?? now()->subYear(),
            $this->end_date ?? now()
        );

        // Calculate dropout rate
        $abandoned = OnboardingEvent::forCohort($this->cohort_id)
            ->ofType(OnboardingEvent::EVENT_ONBOARDING_ABANDONED)
            ->distinct('user_id')
            ->count('user_id');

        $this->update([
            'total_users' => $userIds->count(),
            'completed_users' => $completed,
            'completion_rate' => $userIds->count() > 0
                ? round(($completed / $userIds->count()) * 100, 2)
                : 0,
            'avg_time_to_activation_hours' => $avgTime ? $avgTime / 3600 : null,
            'dropout_rate' => $userIds->count() > 0
                ? round(($abandoned / $userIds->count()) * 100, 2)
                : 0,
        ]);

        return $this;
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get an active cohort for a user type (random assignment based on allocation)
     */
    public static function assignCohort(User $user): ?self
    {
        $cohorts = static::running()
            ->forUserType($user->user_type)
            ->get();

        if ($cohorts->isEmpty()) {
            return null;
        }

        // Group by experiment
        $experiments = $cohorts->groupBy('experiment_name');

        foreach ($experiments as $experimentName => $experimentCohorts) {
            $rand = mt_rand(1, 100);
            $cumulative = 0;

            foreach ($experimentCohorts as $cohort) {
                $cumulative += $cohort->allocation_percentage;
                if ($rand <= $cumulative) {
                    $cohort->assignUser($user);
                    return $cohort;
                }
            }
        }

        return null;
    }

    /**
     * Get cohort for a user
     */
    public static function getForUser(int $userId): ?self
    {
        return static::whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })->running()->first();
    }

    /**
     * Get all cohorts for an experiment
     */
    public static function getExperimentCohorts(string $experimentName): \Illuminate\Database\Eloquent\Collection
    {
        return static::forExperiment($experimentName)
            ->orderBy('variant')
            ->get();
    }

    /**
     * Compare cohorts in an experiment
     */
    public static function compareExperimentCohorts(string $experimentName): array
    {
        $cohorts = static::getExperimentCohorts($experimentName);

        return [
            'experiment' => $experimentName,
            'cohorts' => $cohorts->map(function ($cohort) {
                return [
                    'id' => $cohort->cohort_id,
                    'name' => $cohort->name,
                    'variant' => $cohort->variant,
                    'total_users' => $cohort->total_users,
                    'completed_users' => $cohort->completed_users,
                    'completion_rate' => $cohort->completion_rate,
                    'avg_time_hours' => $cohort->avg_time_to_activation_hours,
                    'dropout_rate' => $cohort->dropout_rate,
                    'is_winner' => $cohort->is_winner,
                ];
            })->toArray(),
        ];
    }
}
