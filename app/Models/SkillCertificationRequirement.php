<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * STAFF-REG-007: SkillCertificationRequirement Model
 *
 * Pivot model for skill-certification requirements with regional settings.
 *
 * @property int $id
 * @property int $skill_id
 * @property int $certification_type_id
 * @property string $requirement_level
 * @property array|null $required_in_countries
 * @property array|null $required_in_states
 * @property array|null $required_at_experience_levels
 * @property string|null $notes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SkillCertificationRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_id',
        'certification_type_id',
        'requirement_level',
        'required_in_countries',
        'required_in_states',
        'required_at_experience_levels',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'required_in_countries' => 'array',
        'required_in_states' => 'array',
        'required_at_experience_levels' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Requirement level constants
     */
    public const LEVEL_REQUIRED = 'required';
    public const LEVEL_RECOMMENDED = 'recommended';
    public const LEVEL_OPTIONAL = 'optional';

    /**
     * Get the skill.
     */
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Get the certification type.
     */
    public function certificationType()
    {
        return $this->belongsTo(CertificationType::class);
    }

    /**
     * Scope: Only required certifications.
     */
    public function scopeRequired($query)
    {
        return $query->where('requirement_level', self::LEVEL_REQUIRED);
    }

    /**
     * Scope: Active requirements only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this requirement applies to a region.
     */
    public function appliesTo(?string $country = null, ?string $state = null): bool
    {
        // If no regional restrictions, applies everywhere
        if (empty($this->required_in_countries) && empty($this->required_in_states)) {
            return true;
        }

        // Check country
        if (!empty($this->required_in_countries) && $country) {
            if (!in_array($country, $this->required_in_countries)) {
                return false;
            }
        }

        // Check state
        if (!empty($this->required_in_states) && $state) {
            if (!in_array($state, $this->required_in_states)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this requirement applies to an experience level.
     */
    public function appliesToExperienceLevel(string $level): bool
    {
        if (empty($this->required_at_experience_levels)) {
            return true;
        }

        return in_array($level, $this->required_at_experience_levels);
    }

    /**
     * Get the requirement level label.
     */
    public function getRequirementLevelLabelAttribute(): string
    {
        return match ($this->requirement_level) {
            self::LEVEL_REQUIRED => 'Required',
            self::LEVEL_RECOMMENDED => 'Recommended',
            self::LEVEL_OPTIONAL => 'Optional',
            default => 'Unknown',
        };
    }
}
