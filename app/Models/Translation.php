<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * GLO-006: Localization Engine - Translation Model
 * Handles dynamic translations stored in the database
 *
 * @property int $id
 * @property string $locale
 * @property string $group
 * @property string $key
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Locale|null $localeModel
 */
class Translation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'locale',
        'group',
        'key',
        'value',
    ];

    /**
     * Cache key prefix for translations.
     */
    protected const CACHE_PREFIX = 'translation:';

    /**
     * Cache duration in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the locale model for this translation.
     */
    public function localeModel(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale', 'code');
    }

    /**
     * Scope a query to filter by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope a query to filter by group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get a translation by locale, group, and key.
     */
    public static function get(string $locale, string $group, string $key): ?string
    {
        $cacheKey = self::CACHE_PREFIX."{$locale}:{$group}:{$key}";

        return cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($locale, $group, $key) {
                $translation = self::where('locale', $locale)
                    ->where('group', $group)
                    ->where('key', $key)
                    ->first();

                return $translation?->value;
            }
        );
    }

    /**
     * Get all translations for a group and locale.
     *
     * @return array<string, string>
     */
    public static function getGroup(string $locale, string $group): array
    {
        $cacheKey = self::CACHE_PREFIX."{$locale}:{$group}";

        return cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => self::where('locale', $locale)
                ->where('group', $group)
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    /**
     * Get all translations for a locale.
     *
     * @return array<string, array<string, string>>
     */
    public static function getAllForLocale(string $locale): array
    {
        $cacheKey = self::CACHE_PREFIX."all:{$locale}";

        return cache()->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($locale) {
                $translations = self::where('locale', $locale)->get();

                $result = [];
                foreach ($translations as $translation) {
                    if (! isset($result[$translation->group])) {
                        $result[$translation->group] = [];
                    }
                    $result[$translation->group][$translation->key] = $translation->value;
                }

                return $result;
            }
        );
    }

    /**
     * Set a translation value (create or update).
     */
    public static function set(string $locale, string $group, string $key, string $value): self
    {
        $translation = self::updateOrCreate(
            [
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ],
            ['value' => $value]
        );

        // Clear relevant caches
        self::clearCache($locale, $group, $key);

        return $translation;
    }

    /**
     * Import translations from an array.
     *
     * @param  array<string, string>  $translations
     */
    public static function import(string $locale, string $group, array $translations): int
    {
        $count = 0;

        foreach ($translations as $key => $value) {
            self::set($locale, $group, $key, $value);
            $count++;
        }

        return $count;
    }

    /**
     * Export translations for a group to an array.
     *
     * @return array<string, string>
     */
    public static function export(string $locale, string $group): array
    {
        return self::getGroup($locale, $group);
    }

    /**
     * Get all available groups.
     *
     * @return Collection<int, string>
     */
    public static function getGroups(?string $locale = null): Collection
    {
        $query = self::query();

        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->distinct()->pluck('group');
    }

    /**
     * Clear cache for a specific translation.
     */
    public static function clearCache(string $locale, ?string $group = null, ?string $key = null): void
    {
        if ($key && $group) {
            cache()->forget(self::CACHE_PREFIX."{$locale}:{$group}:{$key}");
        }

        if ($group) {
            cache()->forget(self::CACHE_PREFIX."{$locale}:{$group}");
        }

        cache()->forget(self::CACHE_PREFIX."all:{$locale}");
    }

    /**
     * Clear all translation cache.
     */
    public static function clearAllCache(): void
    {
        $locales = Locale::getActive()->pluck('code');

        foreach ($locales as $locale) {
            self::clearCache($locale);
        }
    }

    /**
     * Search translations by value.
     */
    public static function search(string $term, ?string $locale = null): Collection
    {
        $query = self::where('value', 'like', "%{$term}%");

        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->get();
    }

    /**
     * Get missing translations (keys that exist in default locale but not in target).
     *
     * @return Collection<int, array<string, string>>
     */
    public static function getMissing(string $targetLocale, string $defaultLocale = 'en'): Collection
    {
        $defaultKeys = self::where('locale', $defaultLocale)
            ->get()
            ->map(fn ($t) => "{$t->group}.{$t->key}");

        $targetKeys = self::where('locale', $targetLocale)
            ->get()
            ->map(fn ($t) => "{$t->group}.{$t->key}");

        $missing = $defaultKeys->diff($targetKeys);

        return $missing->map(function ($fullKey) use ($defaultLocale) {
            [$group, $key] = explode('.', $fullKey, 2);

            return [
                'group' => $group,
                'key' => $key,
                'default_value' => self::get($defaultLocale, $group, $key),
            ];
        });
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Clear cache on model changes
        static::saved(function (self $translation) {
            self::clearCache($translation->locale, $translation->group, $translation->key);
        });

        static::deleted(function (self $translation) {
            self::clearCache($translation->locale, $translation->group, $translation->key);
        });
    }
}
