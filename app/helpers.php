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
        try {
            return app(FeatureFlagService::class)->isEnabled($key, $user ?? auth()->user());
        } catch (\Exception $e) {
            // Log but don't crash - return false (feature disabled) as safe default
            \Illuminate\Support\Facades\Log::warning('Feature flag check failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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

if (! function_exists('env_value')) {
    /**
     * Get a value directly from the .env file, bypassing config cache.
     *
     * This is useful for admin settings forms that need to display current .env values
     * even when config is cached in production. Unlike env(), this function always
     * reads from the actual .env file.
     *
     * WARNING: This function reads from the filesystem on every call. It should only
     * be used in admin settings pages where you need to display raw .env values.
     * For regular application usage, always use config() instead.
     *
     * Usage:
     *   // In Blade templates (admin settings forms)
     *   <input value="{{ env_value('APP_URL') }}">
     *   <input value="{{ env_value('STRIPE_KEY', '') }}">
     *
     * @param  string  $key  The environment variable key
     * @param  mixed  $default  Default value if key not found
     * @return mixed The value from .env file or default
     */
    function env_value(string $key, $default = null): mixed
    {
        return \App\Helper::getEnvValue($key, $default);
    }
}
