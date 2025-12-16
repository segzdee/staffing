<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * STAFF-REG-007: Enhanced Skill Model
 *
 * Master list of skills categorized by industry with certification requirements.
 *
 * @property int $id
 * @property string $name
 * @property string|null $industry
 * @property string|null $category
 * @property string|null $subcategory
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property bool $requires_certification
 * @property array|null $required_certification_ids
 * @property string|null $icon
 * @property string|null $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Skill extends Model
{
    use HasFactory;

    /**
     * Industry constants
     */
    public const INDUSTRY_HOSPITALITY = 'hospitality';
    public const INDUSTRY_WAREHOUSING = 'warehousing';
    public const INDUSTRY_HEALTHCARE = 'healthcare';
    public const INDUSTRY_RETAIL = 'retail';
    public const INDUSTRY_EVENTS = 'events';
    public const INDUSTRY_ADMINISTRATIVE = 'administrative';

    /**
     * Industry labels for display
     */
    public const INDUSTRIES = [
        self::INDUSTRY_HOSPITALITY => 'Hospitality',
        self::INDUSTRY_WAREHOUSING => 'Warehousing & Logistics',
        self::INDUSTRY_HEALTHCARE => 'Healthcare',
        self::INDUSTRY_RETAIL => 'Retail',
        self::INDUSTRY_EVENTS => 'Events & Entertainment',
        self::INDUSTRY_ADMINISTRATIVE => 'Administrative',
    ];

    /**
     * Experience level constants
     */
    public const LEVEL_ENTRY = 'entry';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';
    public const LEVEL_EXPERT = 'expert';

    /**
     * Experience levels with labels
     */
    public const EXPERIENCE_LEVELS = [
        self::LEVEL_ENTRY => 'Entry Level (0-1 years)',
        self::LEVEL_INTERMEDIATE => 'Intermediate (1-3 years)',
        self::LEVEL_ADVANCED => 'Advanced (3-5 years)',
        self::LEVEL_EXPERT => 'Expert (5+ years)',
    ];

    protected $fillable = [
        'name',
        'industry',
        'category',
        'subcategory',
        'description',
        'is_active',
        'sort_order',
        'requires_certification',
        'required_certification_ids',
        'icon',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'requires_certification' => 'boolean',
        'required_certification_ids' => 'array',
    ];

    /**
     * Get workers who have this skill.
     */
    public function workers()
    {
        return $this->belongsToMany(User::class, 'worker_skills', 'skill_id', 'worker_id')
            ->withPivot('proficiency_level', 'experience_level', 'years_experience', 'verified', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get worker skills for this skill.
     */
    public function workerSkills()
    {
        return $this->hasMany(WorkerSkill::class);
    }

    /**
     * Get required certification types for this skill.
     */
    public function requiredCertifications()
    {
        return $this->belongsToMany(CertificationType::class, 'skill_certification_requirements')
            ->withPivot('requirement_level', 'required_in_countries', 'required_in_states', 'notes')
            ->withTimestamps();
    }

    /**
     * Get certification requirements with full pivot details.
     */
    public function certificationRequirements()
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
     * Scope: Only active skills.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Skills requiring certification.
     */
    public function scopeRequiresCertification($query)
    {
        return $query->where('requires_certification', true);
    }

    /**
     * Scope: Order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this skill requires any certifications.
     */
    public function hasCertificationRequirements(): bool
    {
        return $this->requires_certification ||
               ($this->required_certification_ids && count($this->required_certification_ids) > 0);
    }

    /**
     * Get required certifications for a specific region.
     */
    public function getRequiredCertificationsForRegion(?string $country = null, ?string $state = null)
    {
        return $this->certificationRequirements()
            ->where('is_active', true)
            ->where(function ($query) use ($country, $state) {
                $query->whereNull('required_in_countries')
                    ->orWhereJsonContains('required_in_countries', $country);
            })
            ->where(function ($query) use ($state) {
                $query->whereNull('required_in_states')
                    ->orWhereJsonContains('required_in_states', $state);
            })
            ->with('certificationType')
            ->get();
    }

    /**
     * Get the industry label.
     */
    public function getIndustryLabelAttribute(): string
    {
        return self::INDUSTRIES[$this->industry] ?? $this->industry;
    }

    /**
     * Get all industries as options array.
     */
    public static function getIndustryOptions(): array
    {
        return self::INDUSTRIES;
    }

    /**
     * Get all experience levels as options array.
     */
    public static function getExperienceLevelOptions(): array
    {
        return self::EXPERIENCE_LEVELS;
    }
}
