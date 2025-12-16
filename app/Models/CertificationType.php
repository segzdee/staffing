<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * STAFF-REG-007: CertificationType Model
 *
 * Master list of all certification types available across industries.
 *
 * @property int $id
 * @property string $name
 * @property string|null $short_name
 * @property string $slug
 * @property string|null $description
 * @property string $industry
 * @property string|null $category
 * @property string|null $issuing_organization
 * @property string|null $issuing_organization_url
 * @property array|null $recognized_issuers
 * @property bool $has_expiration
 * @property int|null $default_validity_months
 * @property int $renewal_reminder_days
 * @property bool $auto_verifiable
 * @property string|null $verification_api_provider
 * @property array|null $verification_config
 * @property bool $requires_document_upload
 * @property array|null $required_document_types
 * @property string|null $renewal_instructions
 * @property array|null $available_countries
 * @property array|null $available_states
 * @property string|null $icon
 * @property string|null $color
 * @property int $sort_order
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CertificationType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Category constants
     */
    public const CATEGORY_FOOD_SAFETY = 'food_safety';
    public const CATEGORY_ALCOHOL = 'alcohol';
    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_MEDICAL = 'medical';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_SAFETY = 'safety';
    public const CATEGORY_GENERAL = 'general';

    /**
     * Categories with labels
     */
    public const CATEGORIES = [
        self::CATEGORY_FOOD_SAFETY => 'Food Safety',
        self::CATEGORY_ALCOHOL => 'Alcohol Service',
        self::CATEGORY_EQUIPMENT => 'Equipment Operation',
        self::CATEGORY_MEDICAL => 'Medical/Healthcare',
        self::CATEGORY_SECURITY => 'Security',
        self::CATEGORY_SAFETY => 'General Safety',
        self::CATEGORY_GENERAL => 'General',
    ];

    /**
     * Verification providers
     */
    public const VERIFICATION_PROVIDERS = [
        'checkr' => 'Checkr',
        'certify_me' => 'CertifyMe',
        'servsafe' => 'ServSafe API',
        'tips' => 'TIPS API',
        'manual' => 'Manual Verification',
    ];

    protected $fillable = [
        'name',
        'short_name',
        'slug',
        'description',
        'industry',
        'category',
        'issuing_organization',
        'issuing_organization_url',
        'recognized_issuers',
        'has_expiration',
        'default_validity_months',
        'renewal_reminder_days',
        'auto_verifiable',
        'verification_api_provider',
        'verification_config',
        'requires_document_upload',
        'required_document_types',
        'renewal_instructions',
        'available_countries',
        'available_states',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'recognized_issuers' => 'array',
        'has_expiration' => 'boolean',
        'default_validity_months' => 'integer',
        'renewal_reminder_days' => 'integer',
        'auto_verifiable' => 'boolean',
        'verification_config' => 'array',
        'requires_document_upload' => 'boolean',
        'required_document_types' => 'array',
        'available_countries' => 'array',
        'available_states' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get all worker certifications of this type.
     */
    public function workerCertifications()
    {
        return $this->hasMany(WorkerCertification::class);
    }

    /**
     * Get skills that require this certification.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_certification_requirements')
            ->withPivot('requirement_level', 'required_in_countries', 'required_in_states', 'notes')
            ->withTimestamps();
    }

    /**
     * Get skill certification requirements.
     */
    public function skillRequirements()
    {
        return $this->hasMany(SkillCertificationRequirement::class);
    }

    /**
     * Scope: Filter by industry.
     */
    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Only active certification types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Auto-verifiable certifications.
     */
    public function scopeAutoVerifiable($query)
    {
        return $query->where('auto_verifiable', true);
    }

    /**
     * Scope: Available in a specific country.
     */
    public function scopeAvailableInCountry($query, $country)
    {
        return $query->where(function ($q) use ($country) {
            $q->whereNull('available_countries')
                ->orWhereJsonContains('available_countries', $country);
        });
    }

    /**
     * Scope: Available in a specific state.
     */
    public function scopeAvailableInState($query, $state)
    {
        return $query->where(function ($q) use ($state) {
            $q->whereNull('available_states')
                ->orWhereJsonContains('available_states', $state);
        });
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this certification is available in a region.
     */
    public function isAvailableIn(?string $country = null, ?string $state = null): bool
    {
        // Check country availability
        if ($this->available_countries && $country) {
            if (!in_array($country, $this->available_countries)) {
                return false;
            }
        }

        // Check state availability
        if ($this->available_states && $state) {
            if (!in_array($state, $this->available_states)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Get the industry label.
     */
    public function getIndustryLabelAttribute(): string
    {
        return Skill::INDUSTRIES[$this->industry] ?? $this->industry;
    }

    /**
     * Calculate default expiry date from issue date.
     */
    public function calculateExpiryDate($issueDate): ?\Carbon\Carbon
    {
        if (!$this->has_expiration || !$this->default_validity_months) {
            return null;
        }

        return \Carbon\Carbon::parse($issueDate)->addMonths($this->default_validity_months);
    }

    /**
     * Get all categories.
     */
    public static function getCategoryOptions(): array
    {
        return self::CATEGORIES;
    }
}
