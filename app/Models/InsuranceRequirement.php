<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Insurance Requirement Configuration Model
 * BIZ-REG-005: Insurance & Compliance
 *
 * Stores jurisdiction-specific insurance requirements
 */
class InsuranceRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'jurisdiction',
        'insurance_type',
        'insurance_name',
        'description',
        'is_required',
        'is_jurisdiction_dependent',
        'required_in_regions',
        'business_types',
        'industries',
        'minimum_coverage_amount',
        'coverage_currency',
        'minimum_per_occurrence',
        'minimum_aggregate',
        'additional_insured_required',
        'additional_insured_wording',
        'waiver_of_subrogation_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_jurisdiction_dependent' => 'boolean',
        'required_in_regions' => 'array',
        'business_types' => 'array',
        'industries' => 'array',
        'additional_insured_required' => 'boolean',
        'waiver_of_subrogation_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== INSURANCE TYPE CONSTANTS ====================

    const TYPE_GENERAL_LIABILITY = 'general_liability';
    const TYPE_WORKERS_COMP = 'workers_compensation';
    const TYPE_EMPLOYERS_LIABILITY = 'employers_liability';
    const TYPE_PROFESSIONAL_LIABILITY = 'professional_liability';

    // ==================== SCOPES ====================

    /**
     * Scope to active requirements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by jurisdiction.
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', strtoupper($jurisdiction));
    }

    /**
     * Scope to required only.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope by insurance type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('insurance_type', $type);
    }

    /**
     * Scope by business type.
     */
    public function scopeForBusinessType($query, string $businessType)
    {
        return $query->where(function ($q) use ($businessType) {
            $q->whereNull('business_types')
              ->orWhereJsonContains('business_types', $businessType);
        });
    }

    /**
     * Scope by industry.
     */
    public function scopeForIndustry($query, string $industry)
    {
        return $query->where(function ($q) use ($industry) {
            $q->whereNull('industries')
              ->orWhereJsonContains('industries', $industry);
        });
    }

    /**
     * Scope by region (state/province).
     */
    public function scopeForRegion($query, string $region)
    {
        return $query->where(function ($q) use ($region) {
            $q->where('is_jurisdiction_dependent', false)
              ->orWhere(function ($subQ) use ($region) {
                  $subQ->where('is_jurisdiction_dependent', true)
                       ->whereJsonContains('required_in_regions', $region);
              });
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get certificates for this requirement.
     */
    public function certificates()
    {
        return $this->hasMany(InsuranceCertificate::class, 'requirement_id');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get insurance requirements for a business.
     */
    public static function getRequirements(
        string $jurisdiction,
        ?string $businessType = null,
        ?string $industry = null,
        ?string $region = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = static::active()
            ->forJurisdiction($jurisdiction)
            ->orderBy('sort_order');

        if ($businessType) {
            $query->forBusinessType($businessType);
        }

        if ($industry) {
            $query->forIndustry($industry);
        }

        if ($region) {
            $query->forRegion($region);
        }

        return $query->get();
    }

    /**
     * Check if requirement applies to a business.
     */
    public function appliesTo(
        ?string $businessType,
        ?string $industry,
        ?string $region = null
    ): bool {
        // Check business type restriction
        if (!empty($this->business_types) && $businessType) {
            if (!in_array($businessType, $this->business_types)) {
                return false;
            }
        }

        // Check industry restriction
        if (!empty($this->industries) && $industry) {
            if (!in_array($industry, $this->industries)) {
                return false;
            }
        }

        // Check regional requirement
        if ($this->is_jurisdiction_dependent && $region) {
            if (!empty($this->required_in_regions) && !in_array($region, $this->required_in_regions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get minimum coverage formatted.
     */
    public function getMinimumCoverageFormatted(): string
    {
        if (!$this->minimum_coverage_amount) {
            return 'Not specified';
        }

        return $this->formatCurrency($this->minimum_coverage_amount, $this->coverage_currency);
    }

    /**
     * Get minimum per occurrence formatted.
     */
    public function getMinimumPerOccurrenceFormatted(): string
    {
        if (!$this->minimum_per_occurrence) {
            return 'Not specified';
        }

        return $this->formatCurrency($this->minimum_per_occurrence, $this->coverage_currency);
    }

    /**
     * Get minimum aggregate formatted.
     */
    public function getMinimumAggregateFormatted(): string
    {
        if (!$this->minimum_aggregate) {
            return 'Not specified';
        }

        return $this->formatCurrency($this->minimum_aggregate, $this->coverage_currency);
    }

    /**
     * Format currency amount from cents.
     */
    protected function formatCurrency(int $cents, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            'AUD' => 'A$',
            'AED' => 'AED ',
            'SGD' => 'S$',
        ];

        $symbol = $symbols[$currency] ?? $currency . ' ';
        $amount = $cents / 100;

        return $symbol . number_format($amount, 0);
    }

    /**
     * Check if coverage amount meets requirement.
     */
    public function meetsCoverageRequirement(int $coverageAmount): bool
    {
        if (!$this->minimum_coverage_amount) {
            return true;
        }

        return $coverageAmount >= $this->minimum_coverage_amount;
    }

    /**
     * Get insurance type display name.
     */
    public static function getInsuranceTypeName(string $type): string
    {
        $names = [
            self::TYPE_GENERAL_LIABILITY => 'General Liability',
            self::TYPE_WORKERS_COMP => 'Workers\' Compensation',
            self::TYPE_EMPLOYERS_LIABILITY => 'Employers\' Liability',
            self::TYPE_PROFESSIONAL_LIABILITY => 'Professional Liability / E&O',
        ];

        return $names[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Get all insurance types.
     */
    public static function getInsuranceTypes(): array
    {
        return [
            self::TYPE_GENERAL_LIABILITY,
            self::TYPE_WORKERS_COMP,
            self::TYPE_EMPLOYERS_LIABILITY,
            self::TYPE_PROFESSIONAL_LIABILITY,
        ];
    }
}
