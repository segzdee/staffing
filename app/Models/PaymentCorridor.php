<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * GLO-008: Cross-Border Payments - Payment Corridor
 *
 * Defines available payment routes between countries with fees, timing, and limits.
 *
 * @property int $id
 * @property string $source_country
 * @property string $destination_country
 * @property string $source_currency
 * @property string $destination_currency
 * @property string $payment_method
 * @property int $estimated_days_min
 * @property int $estimated_days_max
 * @property float $fee_fixed
 * @property float $fee_percent
 * @property float|null $min_amount
 * @property float|null $max_amount
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PaymentCorridor extends Model
{
    use HasFactory;

    /**
     * Payment method constants.
     */
    public const METHOD_SEPA = 'sepa';

    public const METHOD_SWIFT = 'swift';

    public const METHOD_ACH = 'ach';

    public const METHOD_FASTER_PAYMENTS = 'faster_payments';

    public const METHOD_LOCAL = 'local';

    /**
     * Available payment methods.
     */
    public const PAYMENT_METHODS = [
        self::METHOD_SEPA,
        self::METHOD_SWIFT,
        self::METHOD_ACH,
        self::METHOD_FASTER_PAYMENTS,
        self::METHOD_LOCAL,
    ];

    /**
     * SEPA countries (EU + EEA).
     */
    public const SEPA_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU',
        'MT', 'MC', 'NL', 'NO', 'PL', 'PT', 'RO', 'SM', 'SK', 'SI',
        'ES', 'SE', 'CH', 'GB', 'VA',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'source_country',
        'destination_country',
        'source_currency',
        'destination_currency',
        'payment_method',
        'estimated_days_min',
        'estimated_days_max',
        'fee_fixed',
        'fee_percent',
        'min_amount',
        'max_amount',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'fee_fixed' => 'decimal:2',
        'fee_percent' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope for active corridors.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for corridors by source country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('source_country', strtoupper($countryCode));
    }

    /**
     * Scope for corridors by destination country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToCountry($query, string $countryCode)
    {
        return $query->where('destination_country', strtoupper($countryCode));
    }

    /**
     * Scope for corridors by payment method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope for corridors within amount limits.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinAmountLimits($query, float $amount)
    {
        return $query->where(function ($q) use ($amount) {
            $q->whereNull('min_amount')
                ->orWhere('min_amount', '<=', $amount);
        })->where(function ($q) use ($amount) {
            $q->whereNull('max_amount')
                ->orWhere('max_amount', '>=', $amount);
        });
    }

    /**
     * Calculate the total fee for a given amount.
     */
    public function calculateFee(float $amount): float
    {
        $percentageFee = $amount * ($this->fee_percent / 100);

        return round($this->fee_fixed + $percentageFee, 2);
    }

    /**
     * Get the estimated delivery range as a formatted string.
     */
    public function getEstimatedDeliveryRange(): string
    {
        if ($this->estimated_days_min === $this->estimated_days_max) {
            return $this->estimated_days_min === 1
                ? '1 business day'
                : "{$this->estimated_days_min} business days";
        }

        return "{$this->estimated_days_min}-{$this->estimated_days_max} business days";
    }

    /**
     * Check if the corridor supports the given amount.
     */
    public function supportsAmount(float $amount): bool
    {
        if ($this->min_amount !== null && $amount < $this->min_amount) {
            return false;
        }

        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get a human-readable payment method name.
     */
    public function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::METHOD_SEPA => 'SEPA Transfer',
            self::METHOD_SWIFT => 'SWIFT/Wire Transfer',
            self::METHOD_ACH => 'ACH Transfer',
            self::METHOD_FASTER_PAYMENTS => 'Faster Payments',
            self::METHOD_LOCAL => 'Local Bank Transfer',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Check if this is a SEPA corridor.
     */
    public function isSepa(): bool
    {
        return $this->payment_method === self::METHOD_SEPA;
    }

    /**
     * Check if this is a domestic corridor.
     */
    public function isDomestic(): bool
    {
        return $this->source_country === $this->destination_country;
    }

    /**
     * Get the corridor description.
     */
    public function getDescription(): string
    {
        return sprintf(
            '%s to %s via %s (%s)',
            $this->source_country,
            $this->destination_country,
            $this->getPaymentMethodLabel(),
            $this->getEstimatedDeliveryRange()
        );
    }
}
