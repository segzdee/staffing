<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Industry Model
 * BIZ-REG-003: Master list of industries
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $naics_code
 * @property string|null $sic_code
 * @property int|null $parent_id
 * @property int $level
 * @property int $sort_order
 * @property array|null $common_certifications
 * @property array|null $common_skills
 * @property array|null $compliance_requirements
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $business_count
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Industry|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|Industry[] $children
 */
class Industry extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'naics_code',
        'sic_code',
        'parent_id',
        'level',
        'sort_order',
        'common_certifications',
        'common_skills',
        'compliance_requirements',
        'is_active',
        'is_featured',
        'business_count',
    ];

    protected $casts = [
        'common_certifications' => 'array',
        'common_skills' => 'array',
        'compliance_requirements' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Common industry codes
     */
    const CODE_HOSPITALITY = 'hospitality';
    const CODE_HEALTHCARE = 'healthcare';
    const CODE_RETAIL = 'retail';
    const CODE_MANUFACTURING = 'manufacturing';
    const CODE_LOGISTICS = 'logistics';
    const CODE_CONSTRUCTION = 'construction';
    const CODE_EDUCATION = 'education';
    const CODE_PROFESSIONAL_SERVICES = 'professional_services';
    const CODE_EVENTS = 'events';
    const CODE_TECHNOLOGY = 'technology';
    const CODE_FINANCE = 'finance';
    const CODE_GOVERNMENT = 'government';
    const CODE_OTHER = 'other';

    /**
     * Get the parent industry.
     */
    public function parent()
    {
        return $this->belongsTo(Industry::class, 'parent_id');
    }

    /**
     * Get child industries.
     */
    public function children()
    {
        return $this->hasMany(Industry::class, 'parent_id');
    }

    /**
     * Get all descendant industries.
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get businesses in this industry.
     */
    public function businesses()
    {
        return BusinessProfile::where('industry', $this->code);
    }

    /**
     * Get full hierarchy path.
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get common certifications list.
     */
    public function getCommonCertificationsList(): array
    {
        return $this->common_certifications ?? [];
    }

    /**
     * Get common skills list.
     */
    public function getCommonSkillsList(): array
    {
        return $this->common_skills ?? [];
    }

    /**
     * Get compliance requirements.
     */
    public function getComplianceRequirementsList(): array
    {
        return $this->compliance_requirements ?? [];
    }

    /**
     * Increment business count.
     */
    public function incrementBusinessCount(): void
    {
        $this->increment('business_count');

        // Also increment parent's count
        if ($this->parent) {
            $this->parent->incrementBusinessCount();
        }
    }

    /**
     * Decrement business count.
     */
    public function decrementBusinessCount(): void
    {
        if ($this->business_count > 0) {
            $this->decrement('business_count');

            // Also decrement parent's count
            if ($this->parent) {
                $this->parent->decrementBusinessCount();
            }
        }
    }

    /**
     * Scope to get active industries only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured industries.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get top-level industries.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get sub-industries.
     */
    public function scopeSubIndustries($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get for dropdown select (nested format).
     */
    public static function forSelect(): array
    {
        $industries = [];

        $topLevel = self::active()->topLevel()->ordered()->with('children')->get();

        foreach ($topLevel as $industry) {
            $industries[$industry->code] = $industry->name;

            foreach ($industry->children()->active()->ordered()->get() as $child) {
                $industries[$child->code] = "-- {$child->name}";
            }
        }

        return $industries;
    }

    /**
     * Get flat list for select.
     */
    public static function forFlatSelect(): array
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

    /**
     * Find by NAICS code.
     */
    public static function findByNaicsCode(string $naicsCode): ?self
    {
        return self::where('naics_code', $naicsCode)->first();
    }
}
