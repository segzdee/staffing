<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WKR-006: Earnings Summary Model
 *
 * Cached aggregate summaries for earnings across different time periods.
 * These are refreshed daily by a scheduled command to provide fast dashboard access.
 *
 * @property int $id
 * @property int $user_id
 * @property string $period_type
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property int $shifts_completed
 * @property float $total_hours
 * @property float $gross_earnings
 * @property float $total_fees
 * @property float $total_taxes
 * @property float $net_earnings
 * @property float $avg_hourly_rate
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $worker
 */
class EarningsSummary extends Model
{
    use HasFactory;

    /**
     * Period types
     */
    public const PERIOD_DAILY = 'daily';

    public const PERIOD_WEEKLY = 'weekly';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_YEARLY = 'yearly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'shifts_completed',
        'total_hours',
        'gross_earnings',
        'total_fees',
        'total_taxes',
        'net_earnings',
        'avg_hourly_rate',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'shifts_completed' => 'integer',
        'total_hours' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'total_taxes' => 'decimal:2',
        'net_earnings' => 'decimal:2',
        'avg_hourly_rate' => 'decimal:2',
    ];

    /**
     * Get the worker for this summary.
     */
    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for worker relationship.
     */
    public function user(): BelongsTo
    {
        return $this->worker();
    }

    /**
     * Scope for a specific worker.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWorker($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for a specific period type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope for daily summaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDaily($query)
    {
        return $query->where('period_type', self::PERIOD_DAILY);
    }

    /**
     * Scope for weekly summaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWeekly($query)
    {
        return $query->where('period_type', self::PERIOD_WEEKLY);
    }

    /**
     * Scope for monthly summaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonthly($query)
    {
        return $query->where('period_type', self::PERIOD_MONTHLY);
    }

    /**
     * Scope for yearly summaries.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeYearly($query)
    {
        return $query->where('period_type', self::PERIOD_YEARLY);
    }

    /**
     * Scope for periods starting on or after a date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStartingFrom($query, Carbon $date)
    {
        return $query->where('period_start', '>=', $date->toDateString());
    }

    /**
     * Scope for periods ending on or before a date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEndingBefore($query, Carbon $date)
    {
        return $query->where('period_end', '<=', $date->toDateString());
    }

    /**
     * Scope for periods containing a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeContainingDate($query, Carbon $date)
    {
        return $query->where('period_start', '<=', $date->toDateString())
            ->where('period_end', '>=', $date->toDateString());
    }

    /**
     * Get the period label for display.
     */
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            self::PERIOD_DAILY => $this->period_start->format('M j, Y'),
            self::PERIOD_WEEKLY => 'Week of '.$this->period_start->format('M j, Y'),
            self::PERIOD_MONTHLY => $this->period_start->format('F Y'),
            self::PERIOD_YEARLY => $this->period_start->format('Y'),
            default => $this->period_start->format('M j, Y').' - '.$this->period_end->format('M j, Y'),
        };
    }

    /**
     * Calculate net rate (net earnings / total hours).
     */
    public function getNetHourlyRateAttribute(): float
    {
        if ($this->total_hours <= 0) {
            return 0;
        }

        return round($this->net_earnings / $this->total_hours, 2);
    }

    /**
     * Calculate fee percentage.
     */
    public function getFeePercentageAttribute(): float
    {
        if ($this->gross_earnings <= 0) {
            return 0;
        }

        return round(($this->total_fees / $this->gross_earnings) * 100, 2);
    }

    /**
     * Calculate tax percentage.
     */
    public function getTaxPercentageAttribute(): float
    {
        if ($this->gross_earnings <= 0) {
            return 0;
        }

        return round(($this->total_taxes / $this->gross_earnings) * 100, 2);
    }

    /**
     * Get or create a summary for a specific period.
     */
    public static function getOrCreate(int $userId, string $periodType, Carbon $periodStart, Carbon $periodEnd): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'period_type' => $periodType,
                'period_start' => $periodStart->toDateString(),
            ],
            [
                'period_end' => $periodEnd->toDateString(),
                'shifts_completed' => 0,
                'total_hours' => 0,
                'gross_earnings' => 0,
                'total_fees' => 0,
                'total_taxes' => 0,
                'net_earnings' => 0,
                'avg_hourly_rate' => 0,
            ]
        );
    }

    /**
     * Update the summary with new values.
     */
    public function updateValues(array $data): bool
    {
        return $this->update([
            'shifts_completed' => $data['shifts_completed'] ?? $this->shifts_completed,
            'total_hours' => $data['total_hours'] ?? $this->total_hours,
            'gross_earnings' => $data['gross_earnings'] ?? $this->gross_earnings,
            'total_fees' => $data['total_fees'] ?? $this->total_fees,
            'total_taxes' => $data['total_taxes'] ?? $this->total_taxes,
            'net_earnings' => $data['net_earnings'] ?? $this->net_earnings,
            'avg_hourly_rate' => $data['avg_hourly_rate'] ?? $this->avg_hourly_rate,
        ]);
    }

    /**
     * Get all available period types.
     *
     * @return array<string, string>
     */
    public static function getPeriodTypes(): array
    {
        return [
            self::PERIOD_DAILY => 'Daily',
            self::PERIOD_WEEKLY => 'Weekly',
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_YEARLY => 'Yearly',
        ];
    }
}
