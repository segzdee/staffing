<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GLO-003: Labor Law Compliance - Labor Law Rule Model
 *
 * Represents labor law rules for different jurisdictions.
 *
 * @property int $id
 * @property string $jurisdiction
 * @property string $rule_code
 * @property string $name
 * @property string|null $description
 * @property string $rule_type
 * @property array $parameters
 * @property string $enforcement
 * @property bool $is_active
 * @property bool $allows_opt_out
 * @property string|null $opt_out_requirements
 * @property string|null $legal_reference
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LaborLawRule extends Model
{
    use HasFactory;

    // Rule types
    public const TYPE_WORKING_TIME = 'working_time';

    public const TYPE_REST_PERIOD = 'rest_period';

    public const TYPE_BREAK = 'break';

    public const TYPE_OVERTIME = 'overtime';

    public const TYPE_AGE_RESTRICTION = 'age_restriction';

    public const TYPE_WAGE = 'wage';

    public const TYPE_NIGHT_WORK = 'night_work';

    // Enforcement levels
    public const ENFORCEMENT_HARD_BLOCK = 'hard_block';

    public const ENFORCEMENT_SOFT_WARNING = 'soft_warning';

    public const ENFORCEMENT_LOG_ONLY = 'log_only';

    // Common jurisdictions
    public const JURISDICTION_EU = 'EU';

    public const JURISDICTION_UK = 'UK';

    public const JURISDICTION_US_CA = 'US-CA';

    public const JURISDICTION_AU = 'AU';

    public const JURISDICTION_US_NY = 'US-NY';

    public const JURISDICTION_US_FEDERAL = 'US-FEDERAL';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jurisdiction',
        'rule_code',
        'name',
        'description',
        'rule_type',
        'parameters',
        'enforcement',
        'is_active',
        'allows_opt_out',
        'opt_out_requirements',
        'legal_reference',
        'effective_from',
        'effective_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'allows_opt_out' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all violations for this rule.
     */
    public function violations(): HasMany
    {
        return $this->hasMany(ComplianceViolation::class, 'labor_law_rule_id');
    }

    /**
     * Get all exemptions for this rule.
     */
    public function exemptions(): HasMany
    {
        return $this->hasMany(WorkerExemption::class, 'labor_law_rule_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Active rules only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Rules for a specific jurisdiction.
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Scope: Rules by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope: Currently effective rules (within date range).
     */
    public function scopeCurrentlyEffective($query)
    {
        $now = now();

        return $query->where(function ($q) use ($now) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', $now);
        })->where(function ($q) use ($now) {
            $q->whereNull('effective_until')
                ->orWhere('effective_until', '>=', $now);
        });
    }

    /**
     * Scope: Hard blocking rules.
     */
    public function scopeHardBlocking($query)
    {
        return $query->where('enforcement', self::ENFORCEMENT_HARD_BLOCK);
    }

    /**
     * Scope: Opt-outable rules.
     */
    public function scopeOptOutable($query)
    {
        return $query->where('allows_opt_out', true);
    }

    // ==================== ACCESSORS ====================

    /**
     * Check if this rule is currently effective.
     */
    public function isCurrentlyEffective(): bool
    {
        $now = now();

        if ($this->effective_from && $this->effective_from->isFuture()) {
            return false;
        }

        if ($this->effective_until && $this->effective_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if enforcement should block actions.
     */
    public function shouldBlock(): bool
    {
        return $this->enforcement === self::ENFORCEMENT_HARD_BLOCK;
    }

    /**
     * Check if enforcement should warn.
     */
    public function shouldWarn(): bool
    {
        return $this->enforcement === self::ENFORCEMENT_SOFT_WARNING;
    }

    /**
     * Get a specific parameter value.
     */
    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Get max hours parameter (common for working time rules).
     */
    public function getMaxHours(): ?float
    {
        return $this->getParameter('max_hours');
    }

    /**
     * Get min hours parameter (common for rest period rules).
     */
    public function getMinHours(): ?float
    {
        return $this->getParameter('min_hours');
    }

    /**
     * Get period parameter (daily, weekly, etc.).
     */
    public function getPeriod(): ?string
    {
        return $this->getParameter('period');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get rules for a country, falling back to regional rules.
     */
    public static function getApplicableRules(string $country, ?string $state = null): \Illuminate\Database\Eloquent\Collection
    {
        $jurisdictions = [];

        // Build jurisdiction list in priority order (most specific first)
        if ($state) {
            $jurisdictions[] = "{$country}-{$state}"; // e.g., US-CA
        }
        $jurisdictions[] = $country; // e.g., US

        // Add regional fallbacks
        if (in_array($country, ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'])) {
            $jurisdictions[] = self::JURISDICTION_EU;
        }

        return self::active()
            ->currentlyEffective()
            ->whereIn('jurisdiction', $jurisdictions)
            ->orderByRaw("FIELD(jurisdiction, '".implode("','", $jurisdictions)."')")
            ->get();
    }

    /**
     * Find rule by code.
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('rule_code', $code)->first();
    }
}
