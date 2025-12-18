<?php

/**
 * Global Helper Functions
 *
 * This file contains global helper functions that are autoloaded via composer.
 */

use App\Models\User;
use App\Services\FeatureFlagService;

if (! function_exists('feature')) {
    /**
     * Check if a feature flag is enabled for a user.
     *
     * ADM-007: Feature Flags System
     *
     * Usage:
     *   // Check for current authenticated user
     *   if (feature('new_dashboard')) { ... }
     *
     *   // Check for specific user
     *   if (feature('new_dashboard', $user)) { ... }
     *
     *   // In Blade templates
     *
     *   @feature('new_dashboard')
     *       <div>New dashboard content</div>
     *
     *   @endfeature
     *
     * @param  string  $key  The feature flag key
     * @param  User|null  $user  The user to check for (defaults to authenticated user)
     * @return bool Whether the feature is enabled
     */
    function feature(string $key, ?User $user = null): bool
    {
        return app(FeatureFlagService::class)->isEnabled($key, $user ?? auth()->user());
    }
}

if (! function_exists('feature_enabled')) {
    /**
     * Alias for feature() function.
     *
     * @param  string  $key  The feature flag key
     * @param  User|null  $user  The user to check for
     * @return bool Whether the feature is enabled
     */
    function feature_enabled(string $key, ?User $user = null): bool
    {
        return feature($key, $user);
    }
}

if (! function_exists('feature_disabled')) {
    /**
     * Check if a feature flag is disabled for a user.
     *
     * @param  string  $key  The feature flag key
     * @param  User|null  $user  The user to check for
     * @return bool Whether the feature is disabled
     */
    function feature_disabled(string $key, ?User $user = null): bool
    {
        return ! feature($key, $user);
    }
}

if (! function_exists('feature_flags')) {
    /**
     * Get the feature flag service instance.
     */
    function feature_flags(): FeatureFlagService
    {
        return app(FeatureFlagService::class);
    }
}
