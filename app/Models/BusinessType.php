<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BusinessType Model
 * BIZ-REG-003: Master list of business types
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $category
 * @property int $sort_order
 * @property array|null $enabled_features
 * @property array|null $industry_settings
 * @property bool $is_active
 * @property bool $is_featured
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'category',
        'sort_order',
        'enabled_features',
        'industry_settings',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'enabled_features' => 'array',
        'industry_settings' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Common business type codes
     */
    const CODE_RESTAURANT_BAR = 'restaurant_bar';
    const CODE_HOTEL = 'hotel';
    const CODE_EVENT_VENUE = 'event_venue';
    const CODE_RETAIL = 'retail';
    const CODE_WAREHOUSE = 'warehouse';
    const CODE_HEALTHCARE = 'healthcare';
    const CODE_CORPORATE = 'corporate';
    const CODE_MANUFACTURING = 'manufacturing';
    const CODE_LOGISTICS = 'logistics';
    const CODE_EDUCATION = 'education';
    const CODE_GOVERNMENT = 'government';
    const CODE_NON_PROFIT = 'non_profit';
    const CODE_OTHER = 'other';

    /**
     * Get businesses of this type.
     */
    public function businesses()
    {
        return BusinessProfile::where('business_category', $this->code);
    }

    /**
     * Check if a feature is enabled for this business type.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->enabled_features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Get supported shift types.
     */
    public function getSupportedShiftTypes(): array
    {
        $features = $this->enabled_features ?? [];
        return $features['shift_types'] ?? ['on_demand', 'scheduled'];
    }

    /**
     * Check if requires certification.
     */
    public function requiresCertification(): bool
    {
        $features = $this->enabled_features ?? [];
        return $features['requires_certification'] ?? false;
    }

    /**
     * Check if requires background check.
     */
    public function requiresBackgroundCheck(): bool
    {
        $features = $this->enabled_features ?? [];
        return $features['requires_background_check'] ?? false;
    }

    /**
     * Get industry setting.
     */
    public function getIndustrySetting(string $key, $default = null)
    {
        $settings = $this->industry_settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Scope to get active types only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured types.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get for dropdown select.
     */
    public static function forSelect(): array
    {
        return self::active()
            ->ordered()
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Find by code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
