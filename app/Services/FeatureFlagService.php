<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\FeatureFlagLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Feature Flag Service
 *
 * ADM-007: Feature Flags System
 * Central service for managing feature flags with caching and audit logging.
 */
class FeatureFlagService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'feature_flag:';

    /**
     * Check if a feature flag is enabled for a user.
     */
    public function isEnabled(string $key, ?User $user = null): bool
    {
        try {
            // Check if the table exists (handles pre-migration state)
            if (! $this->tableExists()) {
                return false;
            }

            $flag = $this->getFlag($key);

            if ($flag === null) {
                return false;
            }

            return $flag->isEnabledForUser($user);

        } catch (\Exception $e) {
            Log::warning("Feature flag check failed for '{$key}'", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Enable a feature flag globally.
     */
    public function enable(string $key): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['is_enabled' => $flag->is_enabled];

        $flag->update(['is_enabled' => true]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_ENABLED, $oldValue, ['is_enabled' => true]);
    }

    /**
     * Disable a feature flag globally.
     */
    public function disable(string $key): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['is_enabled' => $flag->is_enabled];

        $flag->update(['is_enabled' => false]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_DISABLED, $oldValue, ['is_enabled' => false]);
    }

    /**
     * Set rollout percentage for a feature flag.
     */
    public function setRolloutPercentage(string $key, int $percentage): void
    {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException('Rollout percentage must be between 0 and 100.');
        }

        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['rollout_percentage' => $flag->rollout_percentage];

        $flag->update(['rollout_percentage' => $percentage]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_ROLLOUT_CHANGED, $oldValue, ['rollout_percentage' => $percentage]);
    }

    /**
     * Enable feature flag for specific users.
     */
    public function enableForUsers(string $key, array $userIds): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['enabled_for_users' => $flag->enabled_for_users];

        // Merge with existing users, ensuring unique values
        $existingUsers = $flag->enabled_for_users ?? [];
        $mergedUsers = array_unique(array_merge($existingUsers, $userIds));

        $flag->update(['enabled_for_users' => array_values($mergedUsers)]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_USERS_CHANGED, $oldValue, ['enabled_for_users' => $mergedUsers]);
    }

    /**
     * Disable feature flag for specific users.
     */
    public function disableForUsers(string $key, array $userIds): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['enabled_for_users' => $flag->enabled_for_users];

        $existingUsers = $flag->enabled_for_users ?? [];
        $filteredUsers = array_diff($existingUsers, $userIds);

        $flag->update(['enabled_for_users' => array_values($filteredUsers)]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_USERS_CHANGED, $oldValue, ['enabled_for_users' => $filteredUsers]);
    }

    /**
     * Enable feature flag for specific roles.
     */
    public function enableForRoles(string $key, array $roles): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['enabled_for_roles' => $flag->enabled_for_roles];

        // Merge with existing roles
        $existingRoles = $flag->enabled_for_roles ?? [];
        $mergedRoles = array_unique(array_merge($existingRoles, $roles));

        $flag->update(['enabled_for_roles' => array_values($mergedRoles)]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_ROLES_CHANGED, $oldValue, ['enabled_for_roles' => $mergedRoles]);
    }

    /**
     * Disable feature flag for specific roles.
     */
    public function disableForRoles(string $key, array $roles): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['enabled_for_roles' => $flag->enabled_for_roles];

        $existingRoles = $flag->enabled_for_roles ?? [];
        $filteredRoles = array_diff($existingRoles, $roles);

        $flag->update(['enabled_for_roles' => array_values($filteredRoles)]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_ROLES_CHANGED, $oldValue, ['enabled_for_roles' => $filteredRoles]);
    }

    /**
     * Enable feature flag for specific tiers.
     */
    public function enableForTiers(string $key, array $tiers): void
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = ['enabled_for_tiers' => $flag->enabled_for_tiers];

        $existingTiers = $flag->enabled_for_tiers ?? [];
        $mergedTiers = array_unique(array_merge($existingTiers, $tiers));

        $flag->update(['enabled_for_tiers' => array_values($mergedTiers)]);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_TIERS_CHANGED, $oldValue, ['enabled_for_tiers' => $mergedTiers]);
    }

    /**
     * Create a new feature flag.
     */
    public function create(array $data): FeatureFlag
    {
        $flag = FeatureFlag::create([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_enabled' => $data['is_enabled'] ?? false,
            'rollout_percentage' => $data['rollout_percentage'] ?? 0,
            'enabled_for_users' => $data['enabled_for_users'] ?? null,
            'enabled_for_roles' => $data['enabled_for_roles'] ?? null,
            'enabled_for_tiers' => $data['enabled_for_tiers'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->logChange($flag, FeatureFlagLog::ACTION_CREATED, null, $flag->toArray());

        return $flag;
    }

    /**
     * Update a feature flag.
     */
    public function update(string $key, array $data): FeatureFlag
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            throw new \InvalidArgumentException("Feature flag '{$key}' not found.");
        }

        $oldValue = $flag->toArray();

        // Filter out non-fillable fields
        $allowedFields = [
            'name',
            'description',
            'is_enabled',
            'rollout_percentage',
            'enabled_for_users',
            'enabled_for_roles',
            'enabled_for_tiers',
            'starts_at',
            'ends_at',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $flag->update($updateData);

        $this->clearCache($key);

        $this->logChange($flag, FeatureFlagLog::ACTION_UPDATED, $oldValue, $flag->fresh()->toArray());

        return $flag->fresh();
    }

    /**
     * Delete a feature flag.
     */
    public function delete(string $key): bool
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            return false;
        }

        $oldValue = $flag->toArray();

        // Log before deletion (the log will remain even after flag is deleted)
        $this->logChange($flag, FeatureFlagLog::ACTION_DELETED, $oldValue, null);

        $this->clearCache($key);

        return $flag->delete();
    }

    /**
     * Get all feature flags.
     */
    public function getAllFlags(): Collection
    {
        if (! $this->tableExists()) {
            return new Collection;
        }

        return FeatureFlag::orderBy('name')->get();
    }

    /**
     * Get active feature flags.
     */
    public function getActiveFlags(): Collection
    {
        if (! $this->tableExists()) {
            return new Collection;
        }

        return FeatureFlag::active()->orderBy('name')->get();
    }

    /**
     * Get a feature flag by key (with caching).
     */
    public function getFlag(string $key): ?FeatureFlag
    {
        if (! $this->tableExists()) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX.$key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            return FeatureFlag::forKey($key)->first();
        });
    }

    /**
     * Log a feature flag change.
     */
    public function logChange(FeatureFlag $flag, string $action, $oldValue, $newValue): void
    {
        $userId = auth()->id();

        // If no authenticated user, skip logging (e.g., during seeding)
        if ($userId === null) {
            return;
        }

        try {
            FeatureFlagLog::create([
                'feature_flag_id' => $flag->id,
                'user_id' => $userId,
                'action' => $action,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log feature flag change', [
                'flag_key' => $flag->key,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear cache for a specific flag.
     */
    public function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * Clear all feature flag caches.
     */
    public function clearAllCaches(): void
    {
        $flags = FeatureFlag::pluck('key');

        foreach ($flags as $key) {
            $this->clearCache($key);
        }
    }

    /**
     * Check if the feature_flags table exists.
     */
    protected function tableExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            try {
                $exists = Schema::hasTable('feature_flags');
            } catch (\Exception $e) {
                $exists = false;
            }
        }

        return $exists;
    }

    /**
     * Get flag audit history.
     */
    public function getFlagHistory(string $key, int $limit = 50): Collection
    {
        $flag = $this->getFlag($key);

        if ($flag === null) {
            return new Collection;
        }

        return $flag->logs()
            ->with('user:id,name,email')
            ->limit($limit)
            ->get();
    }

    /**
     * Batch enable multiple flags.
     */
    public function batchEnable(array $keys): int
    {
        $count = 0;

        foreach ($keys as $key) {
            try {
                $this->enable($key);
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to enable flag '{$key}'", ['error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * Batch disable multiple flags.
     */
    public function batchDisable(array $keys): int
    {
        $count = 0;

        foreach ($keys as $key) {
            try {
                $this->disable($key);
                $count++;
            } catch (\Exception $e) {
                Log::warning("Failed to disable flag '{$key}'", ['error' => $e->getMessage()]);
            }
        }

        return $count;
    }

    /**
     * Get flags enabled for a specific user.
     */
    public function getFlagsForUser(User $user): Collection
    {
        return $this->getAllFlags()->filter(function ($flag) use ($user) {
            return $flag->isEnabledForUser($user);
        });
    }

    /**
     * Check multiple flags at once for a user.
     */
    public function checkMultiple(array $keys, ?User $user = null): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->isEnabled($key, $user);
        }

        return $results;
    }
}
