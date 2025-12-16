<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Insurance Carrier Model
 * BIZ-REG-005: Insurance & Compliance
 *
 * Known insurance carriers for validation purposes
 */
class InsuranceCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'naic_code',
        'am_best_rating',
        'am_best_financial_size',
        'country',
        'operating_regions',
        'verification_api_endpoint',
        'verification_api_type',
        'supports_coi_verification',
        'is_active',
    ];

    protected $casts = [
        'operating_regions' => 'array',
        'supports_coi_verification' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==================== SCOPES ====================

    /**
     * Scope to active carriers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by country.
     */
    public function scopeInCountry($query, string $country)
    {
        return $query->where('country', strtoupper($country));
    }

    /**
     * Scope by NAIC code.
     */
    public function scopeByNaicCode($query, string $code)
    {
        return $query->where('naic_code', $code);
    }

    /**
     * Scope to carriers supporting COI verification.
     */
    public function scopeSupportsCOIVerification($query)
    {
        return $query->where('supports_coi_verification', true);
    }

    /**
     * Scope by operating region.
     */
    public function scopeOperatesIn($query, string $region)
    {
        return $query->where(function ($q) use ($region) {
            $q->whereNull('operating_regions')
              ->orWhereJsonContains('operating_regions', $region);
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get certificates from this carrier.
     */
    public function certificates()
    {
        return $this->hasMany(InsuranceCertificate::class, 'carrier_naic_code', 'naic_code');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Search carriers by name.
     */
    public static function search(string $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Find by NAIC code.
     */
    public static function findByNaicCode(string $code): ?self
    {
        return static::byNaicCode($code)->first();
    }

    /**
     * Check if carrier operates in region.
     */
    public function operatesInRegion(string $region): bool
    {
        if (empty($this->operating_regions)) {
            return true; // No restrictions means operates everywhere
        }

        return in_array($region, $this->operating_regions);
    }

    /**
     * Check if carrier has good rating.
     */
    public function hasGoodRating(): bool
    {
        $goodRatings = ['A++', 'A+', 'A', 'A-'];
        return in_array($this->am_best_rating, $goodRatings);
    }

    /**
     * Get rating description.
     */
    public function getRatingDescription(): string
    {
        $descriptions = [
            'A++' => 'Superior',
            'A+' => 'Superior',
            'A' => 'Excellent',
            'A-' => 'Excellent',
            'B++' => 'Good',
            'B+' => 'Good',
            'B' => 'Fair',
            'B-' => 'Fair',
            'C++' => 'Marginal',
            'C+' => 'Marginal',
            'C' => 'Weak',
            'C-' => 'Weak',
            'D' => 'Poor',
        ];

        return $descriptions[$this->am_best_rating] ?? 'Unknown';
    }

    /**
     * Check if carrier supports API verification.
     */
    public function supportsApiVerification(): bool
    {
        return !empty($this->verification_api_endpoint) && $this->supports_coi_verification;
    }

    /**
     * Get verification API type label.
     */
    public function getApiTypeLabel(): string
    {
        $labels = [
            'rest' => 'REST API',
            'soap' => 'SOAP API',
            'manual' => 'Manual Verification',
        ];

        return $labels[$this->verification_api_type] ?? 'Unknown';
    }
}
