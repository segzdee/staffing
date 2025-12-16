<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait to add caching capabilities to User model.
 */
trait CachesUserProfile
{
    /**
     * Cache key prefix for user profiles.
     */
    const CACHE_PREFIX = 'user_profile:';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Boot the trait.
     */
    protected static function bootCachesUserProfile()
    {
        // Clear cache when user is updated
        static::saved(function ($user) {
            static::clearUserCache($user->id);
        });

        static::deleted(function ($user) {
            static::clearUserCache($user->id);
        });
    }

    /**
     * Get user with cached profile data.
     *
     * @param int $userId
     * @return \App\Models\User|null
     */
    public static function getCached(int $userId): ?self
    {
        $cacheKey = static::CACHE_PREFIX . $userId;

        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($userId) {
            return static::with([
                'workerProfile',
                'businessProfile',
                'agencyProfile',
            ])->find($userId);
        });
    }

    /**
     * Clear cache for a specific user.
     *
     * @param int $userId
     */
    public static function clearUserCache(int $userId): void
    {
        Cache::forget(static::CACHE_PREFIX . $userId);
    }

    /**
     * Clear cache for multiple users.
     *
     * @param array $userIds
     */
    public static function clearUsersCache(array $userIds): void
    {
        foreach ($userIds as $userId) {
            static::clearUserCache($userId);
        }
    }
}
