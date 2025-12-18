<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GLO-002: Tax Jurisdiction Engine - Tax Calculation Audit Trail
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $shift_id
 * @property int|null $shift_payment_id
 * @property int $tax_jurisdiction_id
 * @property float $gross_amount
 * @property float $income_tax
 * @property float $social_security
 * @property float $vat_amount
 * @property float $withholding
 * @property float $net_amount
 * @property array|null $breakdown
 * @property float $effective_tax_rate
 * @property string $currency_code
 * @property string $calculation_type
 * @property bool $is_applied
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Shift|null $shift
 * @property-read ShiftPayment|null $shiftPayment
 * @property-read TaxJurisdiction $taxJurisdiction
 */
class TaxCalculation extends Model
{
    use HasFactory;

    // Calculation types
    public const TYPE_SHIFT_PAYMENT = 'shift_payment';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_ESTIMATE = 'estimate';

    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_payment_id',
        'tax_jurisdiction_id',
        'gross_amount',
        'income_tax',
        'social_security',
        'vat_amount',
        'withholding',
        'net_amount',
        'breakdown',
        'effective_tax_rate',
        'currency_code',
        'calculation_type',
        'is_applied',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'income_tax' => 'decimal:2',
        'social_security' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'withholding' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'breakdown' => 'array',
        'effective_tax_rate' => 'decimal:2',
        'is_applied' => 'boolean',
    ];

    /**
     * The user this calculation is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The shift this calculation is associated with.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * The shift payment this calculation is associated with.
     */
    public function shiftPayment(): BelongsTo
    {
        return $this->belongsTo(ShiftPayment::class);
    }

    /**
     * The tax jurisdiction used for this calculation.
     */
    public function taxJurisdiction(): BelongsTo
    {
        return $this->belongsTo(TaxJurisdiction::class);
    }

    /**
     * Scope for applied calculations.
     */
    public function scopeApplied($query)
    {
        return $query->where('is_applied', true);
    }

    /**
     * Scope for estimates (not applied).
     */
    public function scopeEstimates($query)
    {
        return $query->where('is_applied', false);
    }

    /**
     * Scope for shift payments.
     */
    public function scopeShiftPayments($query)
    {
        return $query->where('calculation_type', self::TYPE_SHIFT_PAYMENT);
    }

    /**
     * Scope for a specific year.
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('created_at', $year);
    }

    /**
     * Scope for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get total deductions.
     */
    public function getTotalDeductionsAttribute(): float
    {
        return (float) $this->income_tax +
               (float) $this->social_security +
               (float) $this->withholding;
    }

    /**
     * Get the take-home percentage.
     */
    public function getTakeHomePercentageAttribute(): float
    {
        if ((float) $this->gross_amount === 0.0) {
            return 100.0;
        }

        return round(((float) $this->net_amount / (float) $this->gross_amount) * 100, 2);
    }

    /**
     * Get formatted breakdown for display.
     */
    public function getFormattedBreakdownAttribute(): array
    {
        $breakdown = [];

        $breakdown[] = [
            'label' => 'Gross Amount',
            'amount' => $this->gross_amount,
            'type' => 'gross',
        ];

        if ((float) $this->income_tax > 0) {
            $breakdown[] = [
                'label' => 'Income Tax',
                'amount' => -$this->income_tax,
                'type' => 'deduction',
            ];
        }

        if ((float) $this->social_security > 0) {
            $breakdown[] = [
                'label' => 'Social Security',
                'amount' => -$this->social_security,
                'type' => 'deduction',
            ];
        }

        if ((float) $this->withholding > 0) {
            $breakdown[] = [
                'label' => 'Withholding Tax',
                'amount' => -$this->withholding,
                'type' => 'deduction',
            ];
        }

        $breakdown[] = [
            'label' => 'Net Amount',
            'amount' => $this->net_amount,
            'type' => 'net',
        ];

        return $breakdown;
    }

    /**
     * Create a calculation from an array of tax components.
     */
    public static function createFromComponents(array $data): self
    {
        $grossAmount = $data['gross_amount'] ?? 0;
        $incomeTax = $data['income_tax'] ?? 0;
        $socialSecurity = $data['social_security'] ?? 0;
        $vatAmount = $data['vat_amount'] ?? 0;
        $withholding = $data['withholding'] ?? 0;

        $netAmount = $grossAmount - $incomeTax - $socialSecurity - $withholding;
        $effectiveRate = $grossAmount > 0
            ? (($incomeTax + $socialSecurity + $withholding) / $grossAmount) * 100
            : 0;

        return self::create([
            'user_id' => $data['user_id'],
            'shift_id' => $data['shift_id'] ?? null,
            'shift_payment_id' => $data['shift_payment_id'] ?? null,
            'tax_jurisdiction_id' => $data['tax_jurisdiction_id'],
            'gross_amount' => $grossAmount,
            'income_tax' => $incomeTax,
            'social_security' => $socialSecurity,
            'vat_amount' => $vatAmount,
            'withholding' => $withholding,
            'net_amount' => $netAmount,
            'breakdown' => $data['breakdown'] ?? null,
            'effective_tax_rate' => round($effectiveRate, 2),
            'currency_code' => $data['currency_code'] ?? 'USD',
            'calculation_type' => $data['calculation_type'] ?? self::TYPE_SHIFT_PAYMENT,
            'is_applied' => $data['is_applied'] ?? true,
        ]);
    }

    /**
     * Get aggregated tax summary for a user and year.
     */
    public static function getAnnualSummary(int $userId, int $year): array
    {
        $calculations = self::forUser($userId)
            ->forYear($year)
            ->applied()
            ->get();

        return [
            'year' => $year,
            'total_gross' => $calculations->sum('gross_amount'),
            'total_income_tax' => $calculations->sum('income_tax'),
            'total_social_security' => $calculations->sum('social_security'),
            'total_vat' => $calculations->sum('vat_amount'),
            'total_withholding' => $calculations->sum('withholding'),
            'total_net' => $calculations->sum('net_amount'),
            'calculation_count' => $calculations->count(),
            'average_effective_rate' => $calculations->avg('effective_tax_rate'),
        ];
    }
}
