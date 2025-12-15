<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

/**
 * SystemSettings Model - ADM-003 Platform Configuration Management
 *
 * Manages platform-wide configuration settings stored in the system_settings table.
 * Includes caching for performance and type casting based on data_type field.
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string $category
 * @property string|null $description
 * @property string $data_type
 * @property bool $is_public
 * @property int|null $last_modified_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $lastModifiedBy
 * @property-read mixed $typed_value
 */
class SystemSettings extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_settings';

    /**
     * Cache key prefix for settings.
     */
    const CACHE_PREFIX = 'system_settings:';
    const CACHE_ALL_KEY = 'system_settings:all';
    const CACHE_TTL = 3600; // 1 hour in seconds

    /**
     * Valid data types for settings.
     */
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_INTEGER = 'integer';
    const DATA_TYPE_DECIMAL = 'decimal';
    const DATA_TYPE_BOOLEAN = 'boolean';
    const DATA_TYPE_JSON = 'json';

    /**
     * Valid categories for settings.
     */
    const CATEGORY_FEES = 'fees';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_LIMITS = 'limits';
    const CATEGORY_FEATURES = 'features';
    const CATEGORY_EMAIL = 'email';
    const CATEGORY_SLA = 'sla';
    const CATEGORY_MATCHING = 'matching';
    const CATEGORY_GENERAL = 'general';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'description',
        'data_type',
        'is_public',
        'last_modified_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_public' => 'boolean',
        'last_modified_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['typed_value'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when settings are modified
        static::saved(function ($setting) {
            self::clearCache($setting->key);
        });

        static::deleted(function ($setting) {
            self::clearCache($setting->key);
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user who last modified this setting.
     */
    public function lastModifiedBy()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Get the audit trail for this setting.
     */
    public function audits()
    {
        return $this->hasMany(SystemSettingAudit::class, 'setting_id')->orderBy('created_at', 'desc');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get only public settings.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get only private settings.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope to filter by data type.
     */
    public function scopeOfType(Builder $query, string $dataType): Builder
    {
        return $query->where('data_type', $dataType);
    }

    /**
     * Scope to search settings by key or description.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the typed value based on data_type.
     *
     * @return mixed
     */
    public function getTypedValueAttribute()
    {
        return self::castValue($this->value, $this->data_type);
    }

    // =========================================================================
    // STATIC METHODS - CACHING
    // =========================================================================

    /**
     * Get a setting value by key with caching.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->data_type);
        });
    }

    /**
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $userId
     * @return self
     */
    public static function set(string $key, $value, ?int $userId = null): self
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            throw new \InvalidArgumentException("Setting with key '{$key}' does not exist.");
        }

        // Store old value for audit
        $oldValue = $setting->value;

        // Encode JSON values
        if ($setting->data_type === self::DATA_TYPE_JSON && is_array($value)) {
            $value = json_encode($value);
        }

        // Convert boolean to string
        if ($setting->data_type === self::DATA_TYPE_BOOLEAN) {
            $value = $value ? '1' : '0';
        }

        $setting->update([
            'value' => (string) $value,
            'last_modified_by' => $userId ?? auth()->id(),
        ]);

        // Create audit entry
        if ($oldValue !== (string) $value) {
            SystemSettingAudit::create([
                'setting_id' => $setting->id,
                'key' => $setting->key,
                'old_value' => $oldValue,
                'new_value' => (string) $value,
                'changed_by' => $userId ?? auth()->id(),
            ]);
        }

        self::clearCache($key);

        return $setting;
    }

    /**
     * Get all settings, optionally grouped by category.
     *
     * @param bool $grouped
     * @return \Illuminate\Support\Collection
     */
    public static function all($columns = ['*'])
    {
        return Cache::remember(self::CACHE_ALL_KEY, self::CACHE_TTL, function () use ($columns) {
            return parent::all($columns);
        });
    }

    /**
     * Get all settings grouped by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function allGrouped()
    {
        return Cache::remember(self::CACHE_ALL_KEY . ':grouped', self::CACHE_TTL, function () {
            return self::query()->orderBy('category')->orderBy('key')->get()->groupBy('category');
        });
    }

    /**
     * Get all public settings as key-value pairs.
     *
     * @return array
     */
    public static function getPublicSettings(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'public', self::CACHE_TTL, function () {
            return self::public()->get()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })->toArray();
        });
    }

    /**
     * Get settings by category.
     *
     * @param string $category
     * @return \Illuminate\Support\Collection
     */
    public static function getByCategory(string $category)
    {
        return Cache::remember(self::CACHE_PREFIX . 'category:' . $category, self::CACHE_TTL, function () use ($category) {
            return self::category($category)->orderBy('key')->get();
        });
    }

    /**
     * Batch update multiple settings.
     *
     * @param array $settings ['key' => 'value', ...]
     * @param int|null $userId
     * @return array Updated settings
     */
    public static function batchUpdate(array $settings, ?int $userId = null): array
    {
        $updated = [];

        foreach ($settings as $key => $value) {
            try {
                $updated[$key] = self::set($key, $value, $userId);
            } catch (\InvalidArgumentException $e) {
                // Log or handle missing keys
                continue;
            }
        }

        // Clear all caches
        self::clearAllCache();

        return $updated;
    }

    /**
     * Reset a setting to its default value.
     *
     * @param string $key
     * @param int|null $userId
     * @return bool
     */
    public static function resetToDefault(string $key, ?int $userId = null): bool
    {
        $defaults = self::getDefaults();

        if (!isset($defaults[$key])) {
            return false;
        }

        self::set($key, $defaults[$key]['value'], $userId);

        return true;
    }

    /**
     * Reset all settings to defaults.
     *
     * @param int|null $userId
     * @return int Number of settings reset
     */
    public static function resetAllToDefaults(?int $userId = null): int
    {
        $defaults = self::getDefaults();
        $count = 0;

        foreach ($defaults as $key => $data) {
            if (self::resetToDefault($key, $userId)) {
                $count++;
            }
        }

        self::clearAllCache();

        return $count;
    }

    // =========================================================================
    // STATIC METHODS - CACHE MANAGEMENT
    // =========================================================================

    /**
     * Clear cache for a specific setting.
     *
     * @param string $key
     */
    public static function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget(self::CACHE_ALL_KEY);
        Cache::forget(self::CACHE_ALL_KEY . ':grouped');
        Cache::forget(self::CACHE_PREFIX . 'public');

        // Clear category caches
        $setting = self::where('key', $key)->first();
        if ($setting) {
            Cache::forget(self::CACHE_PREFIX . 'category:' . $setting->category);
        }
    }

    /**
     * Clear all settings caches.
     */
    public static function clearAllCache(): void
    {
        // Clear all category caches
        foreach (self::getCategories() as $category => $label) {
            Cache::forget(self::CACHE_PREFIX . 'category:' . $category);
        }

        Cache::forget(self::CACHE_ALL_KEY);
        Cache::forget(self::CACHE_ALL_KEY . ':grouped');
        Cache::forget(self::CACHE_PREFIX . 'public');

        // Clear individual setting caches (iterate through all settings)
        self::query()->pluck('key')->each(function ($key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        });
    }

    // =========================================================================
    // STATIC METHODS - UTILITIES
    // =========================================================================

    /**
     * Cast a value based on data type.
     *
     * @param string $value
     * @param string $dataType
     * @return mixed
     */
    public static function castValue(string $value, string $dataType)
    {
        return match ($dataType) {
            self::DATA_TYPE_INTEGER => (int) $value,
            self::DATA_TYPE_DECIMAL => (float) $value,
            self::DATA_TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::DATA_TYPE_JSON => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Get available categories with labels.
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_FEES => 'Platform Fees',
            self::CATEGORY_PAYMENT => 'Payment Settings',
            self::CATEGORY_LIMITS => 'Limits & Thresholds',
            self::CATEGORY_FEATURES => 'Feature Flags',
            self::CATEGORY_EMAIL => 'Email Settings',
            self::CATEGORY_SLA => 'SLA Thresholds',
            self::CATEGORY_MATCHING => 'Matching Algorithm',
        ];
    }

    /**
     * Get available data types with labels.
     *
     * @return array
     */
    public static function getDataTypes(): array
    {
        return [
            self::DATA_TYPE_STRING => 'Text',
            self::DATA_TYPE_INTEGER => 'Integer',
            self::DATA_TYPE_DECIMAL => 'Decimal',
            self::DATA_TYPE_BOOLEAN => 'Boolean',
            self::DATA_TYPE_JSON => 'JSON',
        ];
    }

    /**
     * Get default settings configuration.
     *
     * @return array
     */
    public static function getDefaults(): array
    {
        return [
            // Fees
            'platform_fee_percentage' => [
                'value' => '10',
                'category' => self::CATEGORY_FEES,
                'description' => 'Platform commission fee percentage taken from each shift payment',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => true,
            ],
            'worker_fee_percentage' => [
                'value' => '5',
                'category' => self::CATEGORY_FEES,
                'description' => 'Fee percentage charged to workers',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => true,
            ],
            'business_fee_percentage' => [
                'value' => '5',
                'category' => self::CATEGORY_FEES,
                'description' => 'Fee percentage charged to businesses',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => true,
            ],
            'agency_commission_percentage' => [
                'value' => '15',
                'category' => self::CATEGORY_FEES,
                'description' => 'Default agency commission percentage',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => false,
            ],
            'minimum_payout_amount' => [
                'value' => '2500',
                'category' => self::CATEGORY_FEES,
                'description' => 'Minimum payout amount in cents (e.g., 2500 = $25.00)',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],

            // Payment
            'default_payment_gateway' => [
                'value' => 'stripe',
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'Default payment gateway for processing',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => false,
            ],
            'escrow_hold_hours' => [
                'value' => '24',
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'Hours to hold payment in escrow after shift completion',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'instant_payout_enabled' => [
                'value' => '1',
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'Enable instant payouts via Stripe Connect',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],
            'supported_currencies' => [
                'value' => '["USD","EUR","GBP","CAD","AUD"]',
                'category' => self::CATEGORY_PAYMENT,
                'description' => 'List of supported currency codes',
                'data_type' => self::DATA_TYPE_JSON,
                'is_public' => true,
            ],

            // SLA Thresholds
            'dispute_response_hours' => [
                'value' => '48',
                'category' => self::CATEGORY_SLA,
                'description' => 'Maximum hours to respond to a dispute',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'verification_review_hours' => [
                'value' => '24',
                'category' => self::CATEGORY_SLA,
                'description' => 'Target hours to review verification requests',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => false,
            ],
            'support_response_hours' => [
                'value' => '4',
                'category' => self::CATEGORY_SLA,
                'description' => 'Target hours for support ticket first response',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'payout_processing_days' => [
                'value' => '2',
                'category' => self::CATEGORY_SLA,
                'description' => 'Business days to process standard payouts',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],

            // Limits
            'max_shifts_per_day_worker' => [
                'value' => '3',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Maximum shifts a worker can be assigned per day',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'max_applications_per_shift' => [
                'value' => '50',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Maximum applications allowed per shift',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => false,
            ],
            'max_shift_duration_hours' => [
                'value' => '12',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Maximum allowed shift duration in hours',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'min_hourly_rate' => [
                'value' => '1000',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Minimum hourly rate in cents (e.g., 1000 = $10.00)',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'max_hourly_rate' => [
                'value' => '50000',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'Maximum hourly rate in cents (e.g., 50000 = $500.00)',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'gps_checkin_radius_meters' => [
                'value' => '200',
                'category' => self::CATEGORY_LIMITS,
                'description' => 'GPS radius in meters for valid check-in',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],

            // Feature Flags
            'feature_instant_claim' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable instant shift claiming for qualified workers',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],
            'feature_shift_swap' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable shift swap feature between workers',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],
            'feature_video_interviews' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable Agora-powered video interviews',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],
            'feature_ai_matching' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable AI-powered shift matching',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],
            'feature_agency_mode' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable agency accounts and features',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => false,
            ],
            'feature_ai_agents' => [
                'value' => '1',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable AI agent API access',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => false,
            ],
            'maintenance_mode' => [
                'value' => '0',
                'category' => self::CATEGORY_FEATURES,
                'description' => 'Enable platform-wide maintenance mode',
                'data_type' => self::DATA_TYPE_BOOLEAN,
                'is_public' => true,
            ],

            // Email Settings
            'email_from_name' => [
                'value' => 'OvertimeStaff',
                'category' => self::CATEGORY_EMAIL,
                'description' => 'Name shown in email From field',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => false,
            ],
            'email_from_address' => [
                'value' => 'noreply@overtimestaff.com',
                'category' => self::CATEGORY_EMAIL,
                'description' => 'Email address for outgoing mail',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => false,
            ],
            'email_support_address' => [
                'value' => 'support@overtimestaff.com',
                'category' => self::CATEGORY_EMAIL,
                'description' => 'Support email address',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => true,
            ],

            // Matching Algorithm
            'matching_radius_default_km' => [
                'value' => '50',
                'category' => self::CATEGORY_MATCHING,
                'description' => 'Default search radius in kilometers',
                'data_type' => self::DATA_TYPE_INTEGER,
                'is_public' => true,
            ],
            'matching_skills_weight' => [
                'value' => '0.4',
                'category' => self::CATEGORY_MATCHING,
                'description' => 'Weight for skills matching (0-1)',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => false,
            ],
            'matching_rating_weight' => [
                'value' => '0.3',
                'category' => self::CATEGORY_MATCHING,
                'description' => 'Weight for rating in matching (0-1)',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => false,
            ],
            'matching_distance_weight' => [
                'value' => '0.2',
                'category' => self::CATEGORY_MATCHING,
                'description' => 'Weight for distance in matching (0-1)',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => false,
            ],
            'matching_reliability_weight' => [
                'value' => '0.1',
                'category' => self::CATEGORY_MATCHING,
                'description' => 'Weight for reliability score in matching (0-1)',
                'data_type' => self::DATA_TYPE_DECIMAL,
                'is_public' => false,
            ],

            // General
            'platform_name' => [
                'value' => 'OvertimeStaff',
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Platform display name',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => true,
            ],
            'platform_tagline' => [
                'value' => 'The Global Shift Marketplace',
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Platform tagline',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => true,
            ],
            'default_timezone' => [
                'value' => 'UTC',
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Default timezone for the platform',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => true,
            ],
            'default_currency' => [
                'value' => 'USD',
                'category' => self::CATEGORY_GENERAL,
                'description' => 'Default currency code',
                'data_type' => self::DATA_TYPE_STRING,
                'is_public' => true,
            ],
        ];
    }
}
