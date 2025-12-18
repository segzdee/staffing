<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature Flag Model
 *
 * ADM-007: Feature Flags System
 * Manages feature flags for gradual rollouts, A/B testing, and feature gating.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property bool $is_enabled
 * @property int $rollout_percentage
 * @property array|null $enabled_for_users
 * @property array|null $enabled_for_roles
 * @property array|null $enabled_for_tiers
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FeatureFlagLog> $logs
 */
class FeatureFlag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'rollout_percentage',
        'enabled_for_users',
        'enabled_for_roles',
        'enabled_for_tiers',
        'starts_at',
        'ends_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'rollout_percentage' => 'integer',
        'enabled_for_users' => 'array',
        'enabled_for_roles' => 'array',
        'enabled_for_tiers' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user who created this feature flag.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the audit logs for this feature flag.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(FeatureFlagLog::class)->orderBy('created_at', 'desc');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope: Get only active feature flags.
     * Active = enabled AND within date range (if specified).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_enabled', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    /**
     * Scope: Find feature flag by key.
     */
    public function scopeForKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope: Get enabled feature flags.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope: Get disabled feature flags.
     */
    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Check if this feature flag is enabled for a specific user.
     * Considers: global enable, user list, roles, tiers, date range, rollout %.
     */
    public function isEnabledForUser(?User $user = null): bool
    {
        // If globally disabled, return false
        if (! $this->is_enabled) {
            return false;
        }

        // Check date range constraints
        if (! $this->isWithinDateRange()) {
            return false;
        }

        // If no user provided, check if globally enabled with 100% rollout
        if ($user === null) {
            return $this->rollout_percentage >= 100;
        }

        // Check if user is specifically enabled
        if ($this->isUserExplicitlyEnabled($user)) {
            return true;
        }

        // Check if user's role is enabled
        if ($this->isUserRoleEnabled($user)) {
            return true;
        }

        // Check if user's tier is enabled
        if ($this->isUserTierEnabled($user)) {
            return true;
        }

        // Fall back to rollout percentage check
        return $this->meetsRolloutPercentage($user);
    }

    /**
     * Check if the current time is within the feature flag's date range.
     */
    public function isWithinDateRange(): bool
    {
        $now = now();

        // Check starts_at constraint
        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        // Check ends_at constraint
        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if user meets rollout percentage using consistent hashing.
     * Uses user ID to ensure the same user always gets the same result.
     */
    public function meetsRolloutPercentage(User $user): bool
    {
        // 0% rollout = disabled for everyone via percentage
        if ($this->rollout_percentage <= 0) {
            return false;
        }

        // 100% rollout = enabled for everyone
        if ($this->rollout_percentage >= 100) {
            return true;
        }

        // Use consistent hashing based on user ID and feature flag key
        // This ensures the same user always gets the same result for the same flag
        $hash = crc32($user->id.':'.$this->key);

        // Convert hash to a percentage (0-99)
        $userPercentile = abs($hash) % 100;

        return $userPercentile < $this->rollout_percentage;
    }

    /**
     * Check if user is explicitly enabled in the users list.
     */
    protected function isUserExplicitlyEnabled(User $user): bool
    {
        if (empty($this->enabled_for_users)) {
            return false;
        }

        return in_array($user->id, $this->enabled_for_users);
    }

    /**
     * Check if user's role is in the enabled roles list.
     */
    protected function isUserRoleEnabled(User $user): bool
    {
        if (empty($this->enabled_for_roles)) {
            return false;
        }

        $userRole = $user->role ?? null;
        $userType = $user->user_type ?? null;

        // Check both role and user_type for flexibility
        return in_array($userRole, $this->enabled_for_roles)
            || in_array($userType, $this->enabled_for_roles);
    }

    /**
     * Check if user's tier/subscription is in the enabled tiers list.
     */
    protected function isUserTierEnabled(User $user): bool
    {
        if (empty($this->enabled_for_tiers)) {
            return false;
        }

        // Get user's subscription tier if available
        $userTier = $this->getUserTier($user);

        if ($userTier === null) {
            return false;
        }

        return in_array($userTier, $this->enabled_for_tiers);
    }

    /**
     * Get user's subscription tier.
     * Override this method to implement your tier logic.
     */
    protected function getUserTier(User $user): ?string
    {
        // Check if user has a subscription with a tier/plan name
        // This can be customized based on your subscription system
        if (method_exists($user, 'subscription') && $user->subscription()) {
            return $user->subscription()->stripe_price ?? null;
        }

        // Check for tier in business/worker profile
        if ($user->isBusiness() && $user->businessProfile) {
            return $user->businessProfile->subscription_tier ?? null;
        }

        if ($user->isWorker() && $user->workerProfile) {
            return $user->workerProfile->membership_tier ?? null;
        }

        return null;
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
    {
        if (! $this->is_enabled) {
            return 'Disabled';
        }

        if (! $this->isWithinDateRange()) {
            if ($this->starts_at && now()->lt($this->starts_at)) {
                return 'Scheduled';
            }

            return 'Expired';
        }

        if ($this->rollout_percentage >= 100) {
            return 'Fully Enabled';
        }

        if ($this->rollout_percentage > 0) {
            return "Rolling Out ({$this->rollout_percentage}%)";
        }

        return 'Limited Access';
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColor(): string
    {
        if (! $this->is_enabled) {
            return 'gray';
        }

        if (! $this->isWithinDateRange()) {
            if ($this->starts_at && now()->lt($this->starts_at)) {
                return 'yellow';
            }

            return 'red';
        }

        if ($this->rollout_percentage >= 100) {
            return 'green';
        }

        if ($this->rollout_percentage > 0) {
            return 'blue';
        }

        return 'purple';
    }
}
