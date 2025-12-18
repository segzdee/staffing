<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * GLO-006: Localization Engine - Locale Model
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property string|null $flag_emoji
 * @property bool $is_rtl
 * @property string $date_format
 * @property string $time_format
 * @property string $datetime_format
 * @property string $number_decimal_separator
 * @property string $number_thousands_separator
 * @property string $currency_position
 * @property int $translation_progress
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Translation> $translations
 */
class Locale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_emoji',
        'is_rtl',
        'date_format',
        'time_format',
        'datetime_format',
        'number_decimal_separator',
        'number_thousands_separator',
        'currency_position',
        'translation_progress',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_rtl' => 'boolean',
        'is_active' => 'boolean',
        'translation_progress' => 'integer',
    ];

    /**
     * Cache key prefix for locale data.
     */
    protected const CACHE_PREFIX = 'locale:';

    /**
     * Cache duration in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the translations for this locale.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }

    /**
     * Scope a query to only include active locales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include RTL locales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRtl($query)
    {
        return $query->where('is_rtl', true);
    }

    /**
     * Scope a query to only include LTR locales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLtr($query)
    {
        return $query->where('is_rtl', false);
    }

    /**
     * Get a locale by its code with caching.
     */
    public static function findByCode(string $code): ?self
    {
        return cache()->remember(
            self::CACHE_PREFIX.'code:'.$code,
            self::CACHE_TTL,
            fn () => self::where('code', $code)->first()
        );
    }

    /**
     * Get all active locales with caching.
     */
    public static function getActive(): Collection
    {
        return cache()->remember(
            self::CACHE_PREFIX.'active',
            self::CACHE_TTL,
            fn () => self::active()->orderBy('name')->get()
        );
    }

    /**
     * Get active locales as options for dropdowns.
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        return self::getActive()
            ->pluck('native_name', 'code')
            ->toArray();
    }

    /**
     * Get active locales with flag emojis for display.
     *
     * @return array<string, string>
     */
    public static function getOptionsWithFlags(): array
    {
        return self::getActive()
            ->mapWithKeys(fn ($locale) => [
                $locale->code => $locale->flag_emoji
                    ? "{$locale->flag_emoji} {$locale->native_name}"
                    : $locale->native_name,
            ])
            ->toArray();
    }

    /**
     * Check if a locale code is valid and active.
     */
    public static function isValid(string $code): bool
    {
        $locale = self::findByCode($code);

        return $locale !== null && $locale->is_active;
    }

    /**
     * Get the default locale code.
     */
    public static function getDefaultCode(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Get the default locale.
     */
    public static function getDefault(): ?self
    {
        return self::findByCode(self::getDefaultCode());
    }

    /**
     * Get the display name with native name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name === $this->native_name) {
            return $this->name;
        }

        return "{$this->name} ({$this->native_name})";
    }

    /**
     * Get the display name with flag.
     */
    public function getDisplayNameWithFlagAttribute(): string
    {
        $name = $this->display_name;

        return $this->flag_emoji ? "{$this->flag_emoji} {$name}" : $name;
    }

    /**
     * Clear the cache for this locale.
     */
    public function clearCache(): void
    {
        cache()->forget(self::CACHE_PREFIX.'code:'.$this->code);
        cache()->forget(self::CACHE_PREFIX.'active');
    }

    /**
     * Clear all locale cache.
     */
    public static function clearAllCache(): void
    {
        cache()->forget(self::CACHE_PREFIX.'active');

        // Clear individual locale caches
        self::all()->each(fn ($locale) => $locale->clearCache());
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Clear cache on model changes
        static::saved(function (self $locale) {
            $locale->clearCache();
            cache()->forget(self::CACHE_PREFIX.'active');
        });

        static::deleted(function (self $locale) {
            $locale->clearCache();
            cache()->forget(self::CACHE_PREFIX.'active');
        });
    }
}
