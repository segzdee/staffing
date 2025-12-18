<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PayrollDeduction Model - FIN-005: Payroll Processing System
 *
 * Represents a deduction applied to a payroll item.
 *
 * @property int $id
 * @property int $payroll_item_id
 * @property string $type
 * @property string $description
 * @property float $amount
 * @property bool $is_percentage
 * @property float|null $percentage_rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayrollDeduction extends Model
{
    use HasFactory;

    // Type constants
    public const TYPE_PLATFORM_FEE = 'platform_fee';

    public const TYPE_TAX = 'tax';

    public const TYPE_GARNISHMENT = 'garnishment';

    public const TYPE_ADVANCE_REPAYMENT = 'advance_repayment';

    public const TYPE_UNIFORM = 'uniform';

    public const TYPE_OTHER = 'other';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payroll_item_id',
        'type',
        'description',
        'amount',
        'is_percentage',
        'percentage_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'is_percentage' => 'boolean',
        'percentage_rate' => 'decimal:2',
    ];

    /**
     * Get the payroll item this deduction belongs to.
     */
    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    /**
     * Get the type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PLATFORM_FEE => 'Platform Fee',
            self::TYPE_TAX => 'Tax',
            self::TYPE_GARNISHMENT => 'Garnishment',
            self::TYPE_ADVANCE_REPAYMENT => 'Advance Repayment',
            self::TYPE_UNIFORM => 'Uniform',
            self::TYPE_OTHER => 'Other',
            default => ucfirst($this->type),
        };
    }

    /**
     * Calculate amount from percentage if applicable.
     */
    public static function calculateFromPercentage(float $grossAmount, float $percentageRate): float
    {
        return round($grossAmount * ($percentageRate / 100), 2);
    }

    /**
     * Scope for deductions by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for platform fees.
     */
    public function scopePlatformFees($query)
    {
        return $query->where('type', self::TYPE_PLATFORM_FEE);
    }

    /**
     * Scope for tax deductions.
     */
    public function scopeTaxes($query)
    {
        return $query->where('type', self::TYPE_TAX);
    }
}
