<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FIN-007: Tax Reporting - Tax Withholding Model
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int $tax_jurisdiction_id
 * @property float $gross_amount
 * @property float $federal_withholding
 * @property float $state_withholding
 * @property float $social_security
 * @property float $medicare
 * @property float $other_withholding
 * @property float $total_withheld
 * @property \Illuminate\Support\Carbon $pay_period_start
 * @property \Illuminate\Support\Carbon $pay_period_end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Shift|null $shift
 * @property-read TaxJurisdiction $taxJurisdiction
 */
class TaxWithholding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'tax_jurisdiction_id',
        'gross_amount',
        'federal_withholding',
        'state_withholding',
        'social_security',
        'medicare',
        'other_withholding',
        'total_withheld',
        'pay_period_start',
        'pay_period_end',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'federal_withholding' => 'decimal:2',
        'state_withholding' => 'decimal:2',
        'social_security' => 'decimal:2',
        'medicare' => 'decimal:2',
        'other_withholding' => 'decimal:2',
        'total_withheld' => 'decimal:2',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
    ];

    /**
     * User who this withholding applies to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shift this withholding is related to.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Tax jurisdiction for this withholding.
     */
    public function taxJurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific tax year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('pay_period_start', $year);
    }

    /**
     * Scope for a specific date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where('pay_period_start', '>=', $startDate)
            ->where('pay_period_end', '<=', $endDate);
    }

    /**
     * Scope for a specific jurisdiction.
     */
    public function scopeForJurisdiction($query, int $jurisdictionId)
    {
        return $query->where('tax_jurisdiction_id', $jurisdictionId);
    }

    /**
     * Get net amount after all withholdings.
     */
    public function getNetAmountAttribute(): float
    {
        return $this->gross_amount - $this->total_withheld;
    }

    /**
     * Get effective tax rate.
     */
    public function getEffectiveTaxRateAttribute(): float
    {
        if ($this->gross_amount <= 0) {
            return 0;
        }

        return round(($this->total_withheld / $this->gross_amount) * 100, 2);
    }

    /**
     * Get detailed breakdown of withholdings.
     */
    public function getWithholdingBreakdown(): array
    {
        return [
            'gross_amount' => $this->gross_amount,
            'withholdings' => [
                'federal' => $this->federal_withholding,
                'state' => $this->state_withholding,
                'social_security' => $this->social_security,
                'medicare' => $this->medicare,
                'other' => $this->other_withholding,
            ],
            'total_withheld' => $this->total_withheld,
            'net_amount' => $this->net_amount,
            'effective_rate' => $this->effective_tax_rate,
            'jurisdiction' => $this->taxJurisdiction?->name,
        ];
    }

    /**
     * Calculate and set total withheld from component values.
     */
    public function calculateTotalWithheld(): self
    {
        $this->total_withheld = $this->federal_withholding
            + $this->state_withholding
            + $this->social_security
            + $this->medicare
            + $this->other_withholding;

        return $this;
    }

    /**
     * Create a withholding record from a calculation array.
     */
    public static function createFromCalculation(
        User $user,
        ?Shift $shift,
        TaxJurisdiction $jurisdiction,
        array $calculation,
        $payPeriodStart,
        $payPeriodEnd
    ): self {
        return self::create([
            'user_id' => $user->id,
            'shift_id' => $shift?->id,
            'tax_jurisdiction_id' => $jurisdiction->id,
            'gross_amount' => $calculation['gross_amount'] ?? 0,
            'federal_withholding' => $calculation['federal'] ?? $calculation['income_tax'] ?? 0,
            'state_withholding' => $calculation['state'] ?? 0,
            'social_security' => $calculation['social_security'] ?? 0,
            'medicare' => $calculation['medicare'] ?? 0,
            'other_withholding' => $calculation['other'] ?? $calculation['withholding'] ?? 0,
            'total_withheld' => $calculation['total_withheld'] ?? array_sum([
                $calculation['federal'] ?? $calculation['income_tax'] ?? 0,
                $calculation['state'] ?? 0,
                $calculation['social_security'] ?? 0,
                $calculation['medicare'] ?? 0,
                $calculation['other'] ?? $calculation['withholding'] ?? 0,
            ]),
            'pay_period_start' => $payPeriodStart,
            'pay_period_end' => $payPeriodEnd,
        ]);
    }
}
