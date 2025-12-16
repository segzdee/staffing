<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * OnboardingStep Model
 *
 * Configuration model for all onboarding steps.
 * Defines the master list of steps for workers, businesses, and agencies.
 *
 * @property int $id
 * @property string $step_id
 * @property string $user_type
 * @property string $name
 * @property string|null $description
 * @property string|null $help_text
 * @property string|null $help_url
 * @property string $step_type
 * @property string|null $category
 * @property int $order
 * @property array|null $dependencies
 * @property int $weight
 * @property int $estimated_minutes
 * @property int|null $threshold
 * @property int|null $target
 * @property bool $auto_complete
 * @property string|null $auto_complete_event
 * @property string|null $route_name
 * @property array|null $route_params
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_active
 * @property string|null $cohort_variant
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OnboardingStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'step_id',
        'user_type',
        'name',
        'description',
        'help_text',
        'help_url',
        'step_type',
        'category',
        'order',
        'dependencies',
        'weight',
        'estimated_minutes',
        'threshold',
        'target',
        'auto_complete',
        'auto_complete_event',
        'route_name',
        'route_params',
        'icon',
        'color',
        'is_active',
        'cohort_variant',
    ];

    protected $casts = [
        'dependencies' => 'array',
        'route_params' => 'array',
        'auto_complete' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'weight' => 'integer',
        'estimated_minutes' => 'integer',
        'threshold' => 'integer',
        'target' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all progress records for this step
     */
    public function progress()
    {
        return $this->hasMany(OnboardingProgress::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope to active steps only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by user type
     */
    public function scopeForUserType(Builder $query, string $userType): Builder
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope by step type (required, recommended, optional)
     */
    public function scopeOfType(Builder $query, string $stepType): Builder
    {
        return $query->where('step_type', $stepType);
    }

    /**
     * Scope for required steps
     */
    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('step_type', 'required');
    }

    /**
     * Scope for recommended steps
     */
    public function scopeRecommended(Builder $query): Builder
    {
        return $query->where('step_type', 'recommended');
    }

    /**
     * Scope for optional steps
     */
    public function scopeOptional(Builder $query): Builder
    {
        return $query->where('step_type', 'optional');
    }

    /**
     * Scope by category
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by cohort variant (for A/B testing)
     */
    public function scopeForCohort(Builder $query, ?string $cohortVariant): Builder
    {
        return $query->where(function ($q) use ($cohortVariant) {
            $q->whereNull('cohort_variant')
              ->orWhere('cohort_variant', $cohortVariant);
        });
    }

    /**
     * Scope ordered by display order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this step has dependencies
     */
    public function hasDependencies(): bool
    {
        return !empty($this->dependencies);
    }

    /**
     * Get dependency step IDs
     */
    public function getDependencyIds(): array
    {
        return $this->dependencies ?? [];
    }

    /**
     * Check if this step requires a threshold
     */
    public function hasThreshold(): bool
    {
        return $this->threshold !== null && $this->threshold > 0;
    }

    /**
     * Check if this step has a target count
     */
    public function hasTarget(): bool
    {
        return $this->target !== null && $this->target > 0;
    }

    /**
     * Get the route URL for this step
     */
    public function getRouteUrl(): ?string
    {
        if (!$this->route_name) {
            return null;
        }

        try {
            return route($this->route_name, $this->route_params ?? []);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if step can be skipped
     */
    public function canBeSkipped(): bool
    {
        return $this->step_type !== 'required';
    }

    /**
     * Get formatted estimated time string
     */
    public function getEstimatedTimeString(): string
    {
        if ($this->estimated_minutes < 1) {
            return 'Less than a minute';
        }

        if ($this->estimated_minutes === 1) {
            return '1 minute';
        }

        if ($this->estimated_minutes < 60) {
            return "{$this->estimated_minutes} minutes";
        }

        $hours = floor($this->estimated_minutes / 60);
        $minutes = $this->estimated_minutes % 60;

        if ($minutes === 0) {
            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        return "{$hours}h {$minutes}m";
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get all steps for a user type, properly ordered
     */
    public static function getStepsForUserType(string $userType, ?string $cohortVariant = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forUserType($userType)
            ->forCohort($cohortVariant)
            ->ordered()
            ->get();
    }

    /**
     * Get required steps for a user type
     */
    public static function getRequiredSteps(string $userType, ?string $cohortVariant = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forUserType($userType)
            ->required()
            ->forCohort($cohortVariant)
            ->ordered()
            ->get();
    }

    /**
     * Get recommended steps for a user type
     */
    public static function getRecommendedSteps(string $userType, ?string $cohortVariant = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forUserType($userType)
            ->recommended()
            ->forCohort($cohortVariant)
            ->ordered()
            ->get();
    }

    /**
     * Find step by step_id
     */
    public static function findByStepId(string $stepId): ?self
    {
        return static::where('step_id', $stepId)->first();
    }

    /**
     * Get total weight for a user type and step type
     */
    public static function getTotalWeight(string $userType, ?string $stepType = null): int
    {
        $query = static::active()->forUserType($userType);

        if ($stepType) {
            $query->ofType($stepType);
        }

        return $query->sum('weight');
    }

    /**
     * Get all categories for a user type
     */
    public static function getCategories(string $userType): array
    {
        return static::active()
            ->forUserType($userType)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}
