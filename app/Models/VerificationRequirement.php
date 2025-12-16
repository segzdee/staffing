<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Verification Requirement Configuration Model
 * BIZ-REG-004: Business Verification (KYB)
 *
 * Stores jurisdiction-specific document requirements for KYB verification
 */
class VerificationRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'jurisdiction',
        'requirement_type',
        'document_type',
        'document_name',
        'description',
        'is_required',
        'business_types',
        'industries',
        'validation_api',
        'validation_rules',
        'validity_months',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'business_types' => 'array',
        'industries' => 'array',
        'validation_rules' => 'array',
        'is_active' => 'boolean',
    ];

    // ==================== SCOPES ====================

    /**
     * Scope to active requirements only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to KYB requirements.
     */
    public function scopeKyb($query)
    {
        return $query->where('requirement_type', 'kyb');
    }

    /**
     * Scope to insurance requirements.
     */
    public function scopeInsurance($query)
    {
        return $query->where('requirement_type', 'insurance');
    }

    /**
     * Scope by jurisdiction.
     */
    public function scopeForJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', strtoupper($jurisdiction));
    }

    /**
     * Scope to required documents only.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
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

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all documents of this requirement type.
     */
    public function businessDocuments()
    {
        return $this->hasMany(BusinessDocument::class, 'requirement_id');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get requirements for a specific jurisdiction and business.
     */
    public static function getKybRequirements(
        string $jurisdiction,
        ?string $businessType = null,
        ?string $industry = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = static::active()
            ->kyb()
            ->forJurisdiction($jurisdiction)
            ->orderBy('sort_order');

        if ($businessType) {
            $query->forBusinessType($businessType);
        }

        if ($industry) {
            $query->forIndustry($industry);
        }

        return $query->get();
    }

    /**
     * Check if this requirement applies to a business.
     */
    public function appliesTo(?string $businessType, ?string $industry): bool
    {
        // If no restrictions, applies to all
        if (empty($this->business_types) && empty($this->industries)) {
            return true;
        }

        // Check business type
        if (!empty($this->business_types) && $businessType) {
            if (!in_array($businessType, $this->business_types)) {
                return false;
            }
        }

        // Check industry
        if (!empty($this->industries) && $industry) {
            if (!in_array($industry, $this->industries)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get validation rules for this requirement.
     */
    public function getValidationRules(): array
    {
        return $this->validation_rules ?? [];
    }

    /**
     * Check if requirement has an external validation API.
     */
    public function hasValidationApi(): bool
    {
        return !empty($this->validation_api);
    }

    /**
     * Get supported jurisdictions.
     */
    public static function getSupportedJurisdictions(): array
    {
        return ['US', 'UK', 'EU', 'AU', 'UAE', 'SG'];
    }

    /**
     * Get jurisdiction display name.
     */
    public static function getJurisdictionName(string $code): string
    {
        $names = [
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'EU' => 'European Union',
            'AU' => 'Australia',
            'UAE' => 'United Arab Emirates',
            'SG' => 'Singapore',
        ];

        return $names[strtoupper($code)] ?? $code;
    }
}
