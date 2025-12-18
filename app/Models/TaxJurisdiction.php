<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GLO-002: Tax Jurisdiction Engine
 *
 * @property int $id
 * @property string $country_code
 * @property string|null $state_code
 * @property string|null $city
 * @property string $name
 * @property float $income_tax_rate
 * @property float $social_security_rate
 * @property float $vat_rate
 * @property bool $vat_reverse_charge
 * @property float $withholding_rate
 * @property array|null $tax_brackets
 * @property float $tax_free_threshold
 * @property bool $requires_w9
 * @property bool $requires_w8ben
 * @property string|null $tax_id_format
 * @property string $tax_id_name
 * @property string $currency_code
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TaxCalculation> $taxCalculations
 * @property-read int|null $tax_calculations_count
 */
class TaxJurisdiction extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_code',
        'state_code',
        'city',
        'name',
        'income_tax_rate',
        'social_security_rate',
        'vat_rate',
        'vat_reverse_charge',
        'withholding_rate',
        'tax_brackets',
        'tax_free_threshold',
        'requires_w9',
        'requires_w8ben',
        'tax_id_format',
        'tax_id_name',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'income_tax_rate' => 'decimal:2',
        'social_security_rate' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_reverse_charge' => 'boolean',
        'withholding_rate' => 'decimal:2',
        'tax_brackets' => 'array',
        'tax_free_threshold' => 'decimal:2',
        'requires_w9' => 'boolean',
        'requires_w8ben' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Tax calculations using this jurisdiction.
     */
    public function taxCalculations(): HasMany
    {
        return $this->hasMany(TaxCalculation::class);
    }

    /**
     * Scope for active jurisdictions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific country.
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    /**
     * Scope for a specific state within a country.
     */
    public function scopeForState($query, string $countryCode, string $stateCode)
    {
        return $query->where('country_code', strtoupper($countryCode))
            ->where('state_code', $stateCode);
    }

    /**
     * Scope for federal/national level (no state or city).
     */
    public function scopeFederal($query)
    {
        return $query->whereNull('state_code')->whereNull('city');
    }

    /**
     * Find the most specific jurisdiction matching the given location.
     * Priority: City > State > Country
     */
    public static function findForLocation(string $countryCode, ?string $stateCode = null, ?string $city = null): ?self
    {
        $countryCode = strtoupper($countryCode);

        // Try city-level first
        if ($city && $stateCode) {
            $jurisdiction = self::active()
                ->where('country_code', $countryCode)
                ->where('state_code', $stateCode)
                ->where('city', $city)
                ->first();

            if ($jurisdiction) {
                return $jurisdiction;
            }
        }

        // Try state-level
        if ($stateCode) {
            $jurisdiction = self::active()
                ->where('country_code', $countryCode)
                ->where('state_code', $stateCode)
                ->whereNull('city')
                ->first();

            if ($jurisdiction) {
                return $jurisdiction;
            }
        }

        // Fall back to country-level
        return self::active()
            ->where('country_code', $countryCode)
            ->whereNull('state_code')
            ->whereNull('city')
            ->first();
    }

    /**
     * Get the total tax rate (income + social security).
     */
    public function getTotalEmployeeTaxRate(): float
    {
        return (float) $this->income_tax_rate + (float) $this->social_security_rate;
    }

    /**
     * Get the full jurisdiction name including parent regions.
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->name];

        if ($this->city) {
            $parts[] = $this->state_code;
        }

        $parts[] = $this->country_code;

        return implode(', ', array_filter($parts));
    }

    /**
     * Check if a tax ID matches the expected format.
     */
    public function validateTaxId(string $taxId): bool
    {
        if (empty($this->tax_id_format)) {
            return true; // No format specified, accept any
        }

        return (bool) preg_match($this->tax_id_format, $taxId);
    }

    /**
     * Calculate income tax using progressive brackets if defined.
     */
    public function calculateProgressiveTax(float $grossAmount): float
    {
        if (empty($this->tax_brackets)) {
            // Flat rate
            return $grossAmount * ($this->income_tax_rate / 100);
        }

        // Apply tax-free threshold
        $taxableAmount = max(0, $grossAmount - (float) $this->tax_free_threshold);

        if ($taxableAmount <= 0) {
            return 0;
        }

        $totalTax = 0;
        $remainingAmount = $taxableAmount;
        $previousThreshold = 0;

        foreach ($this->tax_brackets as $bracket) {
            $threshold = $bracket['threshold'] ?? 0;
            $rate = $bracket['rate'] ?? 0;

            $bracketAmount = min($remainingAmount, $threshold - $previousThreshold);

            if ($bracketAmount <= 0) {
                break;
            }

            $totalTax += $bracketAmount * ($rate / 100);
            $remainingAmount -= $bracketAmount;
            $previousThreshold = $threshold;

            if ($remainingAmount <= 0) {
                break;
            }
        }

        // Apply highest bracket rate to remaining amount
        if ($remainingAmount > 0 && ! empty($this->tax_brackets)) {
            $highestBracket = end($this->tax_brackets);
            $totalTax += $remainingAmount * (($highestBracket['rate'] ?? 0) / 100);
        }

        return round($totalTax, 2);
    }

    /**
     * Check if this jurisdiction is in the EU.
     */
    public function isEuMember(): bool
    {
        $euCountries = [
            'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
            'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
            'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        ];

        return in_array($this->country_code, $euCountries);
    }

    /**
     * Get required tax forms for this jurisdiction.
     */
    public function getRequiredForms(): array
    {
        $forms = [];

        if ($this->requires_w9) {
            $forms[] = 'w9';
        }

        if ($this->requires_w8ben) {
            $forms[] = 'w8ben';
        }

        // UK-specific forms
        if ($this->country_code === 'GB') {
            $forms[] = 'self_assessment';
        }

        return $forms;
    }
}
